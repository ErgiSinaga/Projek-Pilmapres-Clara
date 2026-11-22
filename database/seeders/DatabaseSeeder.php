<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PetState;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Sekolah & Kelas
        $school = School::firstOrCreate(
            ['kode' => 'S1'],
            ['nama' => 'SD Negeri 1 Magelang']
        );

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'nama' => 'Kelas 4A'],
            ['tingkat' => 4]
        );

        // 2. Akun Peneliti & Guru
        User::updateOrCreate(
            ['email' => 'peneliti@dento.test'],
            [
                'name' => 'Tim Peneliti Pilmapres',
                'password' => Hash::make('password123'),
                'role' => 'peneliti',
                'school_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'guru@dento.test'],
            [
                'name' => 'Guru Pendamping SD 1',
                'password' => Hash::make('password123'),
                'role' => 'guru',
                'school_id' => $school->id,
            ]
        );

        // 3. Konten Tes & Toko Aksesori
        $this->call(ContentSeeder::class);

        $shopFile = database_path('content/shop_items.json');
        if (File::exists($shopFile)) {
            $items = json_decode(File::get($shopFile), true) ?? [];
            foreach ($items as $itemData) {
                Item::updateOrCreate(
                    ['kode' => $itemData['kode']],
                    [
                        'nama' => $itemData['nama'],
                        'slot' => $itemData['slot'],
                        'harga' => $itemData['harga'],
                        'svg_key' => $itemData['svg_key'],
                    ]
                );
            }
        }

        // 4. Data Murid Demo (Hanya di local environment, DEMO-01 s/d DEMO-10, PIN 1234)
        if (app()->environment('local', 'testing')) {
            for ($i = 1; $i <= 10; $i++) {
                $kode = sprintf('DEMO-%02d', $i);
                $student = Student::firstOrCreate(
                    ['kode' => $kode],
                    [
                        'school_id' => $school->id,
                        'class_id' => $class->id,
                        'pin_hash' => Hash::make('1234'),
                        'pet_name' => $i === 1 ? 'Gigiku' : null,
                        'failed_attempts' => 0,
                        'locked_until' => null,
                        'consent_confirmed_at' => Carbon::now(),
                    ]
                );

                PetState::firstOrCreate(
                    ['student_id' => $student->id],
                    [
                        'kebersihan' => 100,
                        'kebahagiaan' => 100,
                        'koin' => 50,
                        'xp' => 0,
                        'level' => 1,
                        'last_seen_at' => Carbon::now(),
                        'last_decay_at' => Carbon::now(),
                    ]
                );
            }
        }
    }
}
