<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyClaim;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\TestAttempt;
use App\Models\VideoProgress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PetController extends Controller
{
    public static array $presetNames = [
        'Dento',
        'Gigi Hebat',
        'Si Putih',
        'Pahlawan Cilik',
        'Bintang Senyum',
    ];

    /**
     * Memilih nama hewan peliharaan dari daftar preset (tanpa input bebas untuk menjaga privasi).
     */
    public function setName(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pet_name' => ['required', 'string', 'in:'.implode(',', self::$presetNames)],
        ]);

        $student = Auth::guard('student')->user();
        $student->update([
            'pet_name' => $validated['pet_name'],
        ]);

        return response()->json([
            'message' => 'Nama Dento berhasil dipilih!',
            'pet_name' => $student->pet_name,
        ]);
    }

    /**
     * Data Hub / Rumah Dento: peluruhan status server-side, perlengkapan, tes, dan video.
     */
    public function home(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $pet = $student->petState;

        if ($pet) {
            $pet->applyDecay();
            $pet->refresh();
        }

        // Perlengkapan aktif
        $equipped = [];
        $activeInventories = $student->inventories()->where('equipped', true)->with('item')->get();
        foreach ($activeInventories as $inv) {
            if ($inv->item) {
                $equipped[$inv->item->slot] = $inv->item->svg_key;
            }
        }

        // Status Pre-Test & Post-Test
        $preTest = TestAttempt::where('student_id', $student->id)->where('jenis', 'pre')->first();
        $postTest = TestAttempt::where('student_id', $student->id)->where('jenis', 'post')->first();

        // Cek syarat post-test unlock
        $postTestUnlocked = false;
        if ($postTest) {
            $postTestUnlocked = true;
        } else {
            $postConfig = config('dento.post_test_unlock');
            if (($postConfig['mode'] ?? 'auto') === 'auto') {
                $activeDays = $student->active_days_count;
                $videosDone = VideoProgress::where('student_id', $student->id)->whereNotNull('selesai_at')->count();
                $petLevel = $pet->level ?? 1;
                $checkinsDone = $student->completed_checkins_count;

                $postTestUnlocked = $activeDays >= ($postConfig['min_active_days'] ?? 3)
                    && $videosDone >= ($postConfig['min_videos'] ?? 4)
                    && $petLevel >= ($postConfig['min_level'] ?? 3)
                    && $checkinsDone >= ($postConfig['min_checkins'] ?? 3);
            }
        }

        // Status Jurnal Kebiasaan hari ini
        $todayCheckin = $student->today_checkin;
        $isPreFinished = $preTest && $preTest->finished_at !== null;
        $todayStatus = $todayCheckin ? $todayCheckin->status : 'belum';
        $dilewatiCount = $todayCheckin ? $todayCheckin->dilewati_count : 0;
        $promptToday = $isPreFinished
            && config('dento.habit.enabled', true)
            && config('dento.habit.prompt_on_first_login_of_day', true)
            && $todayStatus !== 'selesai'
            && $dilewatiCount === 0;

        return response()->json([
            'student' => [
                'id' => $student->id,
                'kode' => $student->kode,
                'pet_name' => $student->pet_name,
            ],
            'pet' => $pet,
            'equipped' => $equipped,
            'pre_test' => [
                'started' => $preTest !== null,
                'finished' => $isPreFinished,
            ],
            'post_test' => [
                'unlocked' => $postTestUnlocked,
                'started' => $postTest !== null,
                'finished' => $postTest && $postTest->finished_at !== null,
            ],
            'habit' => [
                'enabled' => config('dento.habit.enabled', true),
                'today_done' => $todayStatus === 'selesai',
                'today_status' => $todayStatus,
                'prompt_today' => $promptToday,
                'dilewati_count' => $dilewatiCount,
                'streak' => $student->school_days_streak,
            ],
        ]);
    }

    /**
     * Memasang atau melepas aksesori toko.
     */
    public function equip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slot' => ['required', 'string', 'in:topi,kacamata,jubah,latar'],
            'item_id' => ['nullable', 'exists:items,id'],
        ]);

        $student = Auth::guard('student')->user();
        $slot = $validated['slot'];
        $itemId = $validated['item_id'];

        // Lepas semua item di slot ini terlebih dahulu
        $slotItemIds = Item::where('slot', $slot)->pluck('id');
        $student->inventories()->whereIn('item_id', $slotItemIds)->update(['equipped' => false]);

        if ($itemId) {
            $inventory = $student->inventories()->where('item_id', $itemId)->first();
            if (! $inventory) {
                return response()->json(['message' => 'Kamu belum memiliki aksesori ini.'], 403);
            }

            $inventory->update(['equipped' => true]);
        }

        return response()->json([
            'message' => 'Aksesori berhasil diperbarui!',
            'success' => true,
        ]);
    }

    /**
     * Daftar item di toko beserta status kepemilikan.
     */
    public function shop(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $ownedInventories = $student->inventories()->with('item')->get()->keyBy('item_id');

        $allItems = Item::orderBy('slot')->orderBy('harga')->get()->map(function ($item) use ($ownedInventories) {
            $inv = $ownedInventories->get($item->id);

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'slot' => $item->slot,
                'harga' => $item->harga,
                'svg_key' => $item->svg_key,
                'is_owned' => $inv !== null,
                'is_equipped' => $inv ? $inv->equipped : false,
            ];
        });

        return response()->json([
            'koin' => $student->petState->koin ?? 0,
            'items' => $allItems,
        ]);
    }

    /**
     * Membeli aksesori menggunakan koin.
     */
    public function buyShopItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $student = Auth::guard('student')->user();
        $item = Item::findOrFail($validated['item_id']);

        // Cek kepemilikan ganda
        $alreadyOwned = $student->inventories()->where('item_id', $item->id)->exists();
        if ($alreadyOwned) {
            return response()->json(['message' => 'Kamu sudah memiliki aksesori ini!'], 400);
        }

        $petState = $student->petState;
        if (($petState->koin ?? 0) < $item->harga) {
            return response()->json(['message' => 'Koinmu belum cukup untuk membeli aksesori ini. Kumpulkan koin lewat aktivitas ya!'], 400);
        }

        // Potong koin server-side
        $petState->decrement('koin', $item->harga);

        $inventory = Inventory::create([
            'student_id' => $student->id,
            'item_id' => $item->id,
            'equipped' => false,
        ]);

        return response()->json([
            'message' => "Berhasil membeli {$item->nama}!",
            'koin' => $petState->koin,
            'inventory' => $inventory,
        ]);
    }

    /**
     * Pesan Harian edukatif Dento.
     */
    public function dailyMessage(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();

        $alreadyClaimed = DailyClaim::where('student_id', $student->id)
            ->whereDate('tanggal', Carbon::today())
            ->exists();

        $dailyFile = database_path('content/daily_messages.json');
        $messages = File::exists($dailyFile) ? (json_decode(File::get($dailyFile), true) ?? []) : [];

        // Pilih pesan berdasarkan hari dalam tahun (modulo 20)
        $dayOfYear = Carbon::now()->dayOfYear;
        $messageIndex = count($messages) > 0 ? ($dayOfYear % count($messages)) : 0;
        $selectedMessage = $messages[$messageIndex]['pesan'] ?? 'Tetap rajin menyikat gigi 2 kali sehari ya!';

        return response()->json([
            'tanggal' => Carbon::today()->toDateString(),
            'pesan' => $selectedMessage,
            'is_claimed' => $alreadyClaimed,
            'reward_koin' => 5,
        ]);
    }

    /**
     * Klaim koin pesan harian (5 koin, sekali per hari).
     */
    public function claimDailyMessage(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();

        $alreadyClaimed = DailyClaim::where('student_id', $student->id)
            ->whereDate('tanggal', Carbon::today())
            ->exists();

        if ($alreadyClaimed) {
            return response()->json(['message' => 'Kamu sudah membaca pesan dan mengklaim koin hari ini. Datang lagi besok ya!'], 400);
        }

        try {
            DailyClaim::create([
                'student_id' => $student->id,
                'tanggal' => Carbon::today()->toDateString(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Kamu sudah membaca pesan dan mengklaim koin hari ini. Datang lagi besok ya!'], 400);
        }

        $petState = $student->petState;
        if ($petState) {
            $petState->increment('koin', 5);
        }

        return response()->json([
            'message' => 'Hebat! Kamu mendapatkan 5 koin hadiah harian!',
            'koin' => $petState->koin ?? 5,
            'reward' => 5,
        ]);
    }
}
