<?php

namespace Database\Seeders;

use App\Models\TestItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $testFile = database_path('content/test_items.json');
        if (File::exists($testFile)) {
            $items = json_decode(File::get($testFile), true) ?? [];
            foreach ($items as $itemData) {
                TestItem::updateOrCreate(
                    [
                        'set' => $itemData['set'] ?? 'A',
                        'urutan' => $itemData['urutan'],
                    ],
                    [
                        'tp' => $itemData['tp'],
                        'teks' => $itemData['teks'],
                        'opsi' => $itemData['opsi'],
                        'benar' => $itemData['benar'],
                        'gambar' => $itemData['gambar'] ?? null,
                        'audio' => $itemData['audio'] ?? null,
                        'penjelasan_internal' => $itemData['penjelasan_internal'] ?? null,
                        'status' => $itemData['status'] ?? 'DRAFT',
                    ]
                );
            }
        }
    }
}
