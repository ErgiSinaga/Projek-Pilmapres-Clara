<?php

return [
    'mascot_name' => 'Dento',
    'grade_range' => [3, 5],          // asumsi: SD kelas 3-5; memengaruhi tingkat bahasa konten
    'pin_length' => 4,
    'max_failed_attempts' => 5,       // setelah itu terkunci sementara
    'lock_minutes' => 5,              // guru juga bisa reset kapan saja
    'require_consent' => false,       // true: murid hanya bisa login jika consent_confirmed_at terisi
    'test_set_pre' => 'A',
    'test_set_post' => 'A',           // bisa 'B' jika dosen menyiapkan soal setara
    'post_test_unlock' => [
        'mode' => 'auto',             // 'auto' | 'manual' (manual = hanya guru yang membuka)
        'min_active_days' => 3,
        'min_videos' => 4,
        'min_level' => 3,
        'min_checkins' => 3,          // syarat check-in jurnal selesai
    ],
    'habit' => [
        'enabled' => true,
        'school_days_only' => true,      // streak hanya hitung Sen-Jum
        'streak_grace_days' => 1,        // toleransi 1 hari bolong
        'prompt_on_first_login_of_day' => true,
        'allow_skip' => true,            // murid bisa klik "Nanti dulu"
        'max_recommendations_per_day' => 1,
        'reward' => [
            'coins' => 8,
            'xp' => 10,
            'happiness' => 5,
        ],
        'micro_video_reward' => [
            'coins' => 5,
            'xp' => 5,
        ],
    ],
    'coin_cap_per_activity_per_day' => 30,
    'decay' => [
        'kebersihan_per_hour' => 0.4,
        'kebahagiaan_per_hour' => 0.5,
        'floor' => 30,
        'max_drop_per_absence' => 40,
    ],
    'xp' => [
        'activity' => 10,
        'video' => 20,
        'test' => 30,
    ],
    'level_thresholds' => [0, 50, 120, 220, 350, 500],
    'audio_enabled' => true,          // tombol "dengarkan" hanya tampil jika file audio ada
];
