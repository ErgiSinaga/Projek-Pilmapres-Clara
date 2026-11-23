<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HabitManagementController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\StudentManagementController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\StudentAuthController;
use App\Http\Controllers\Api\TestEngineController;
use App\Http\Controllers\Api\VideoController;
use Illuminate\Support\Facades\Route;

// Tampilan Tunggal SPA Murid
Route::get('/', function () {
    return view('app');
})->name('home');

if (app()->environment('local')) {
    Route::get('/dev/mascot', function () {
        return view('app');
    })->name('dev.mascot');
}

// API Murid (Session + CSRF)
Route::prefix('api')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login'])->name('api.login');
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [StudentAuthController::class, 'me'])->name('api.me');

    // Rumah & Hewan Dento
    Route::get('/home', [PetController::class, 'home'])->name('api.home');
    Route::post('/pet/name', [PetController::class, 'setName'])->name('api.pet.name');
    Route::post('/pet/equip', [PetController::class, 'equip'])->name('api.pet.equip');

    // Toko Aksesori
    Route::get('/shop', [PetController::class, 'shop'])->name('api.shop');
    Route::post('/shop/buy', [PetController::class, 'buyShopItem'])->name('api.shop.buy');

    // Pesan Harian
    Route::get('/daily-message', [PetController::class, 'dailyMessage'])->name('api.daily.message');
    Route::post('/daily-message/claim', [PetController::class, 'claimDailyMessage'])->name('api.daily.claim');

    // Mini Game Edukasi
    Route::post('/games/{kode}/start', [GameController::class, 'start'])->name('api.games.start');
    Route::post('/games/{kode}/event', [GameController::class, 'event'])->name('api.games.event');
    Route::post('/games/{kode}/finish', [GameController::class, 'finish'])->name('api.games.finish');

    // Video & Kelas Dokter Gigi
    Route::get('/videos', [VideoController::class, 'index'])->name('api.videos.index');
    Route::post('/videos/{id}/progress', [VideoController::class, 'progress'])->name('api.videos.progress');
    Route::post('/videos/{id}/complete', [VideoController::class, 'complete'])->name('api.videos.complete');
    Route::post('/videos/{id}/challenge', [VideoController::class, 'challenge'])->name('api.videos.challenge');

    // Mesin Tes (Pre-Test & Post-Test)
    Route::get('/test/{jenis}/start', [TestEngineController::class, 'start'])->name('api.test.start');
    Route::post('/test/{jenis}/answer', [TestEngineController::class, 'answer'])->name('api.test.answer');
    Route::post('/test/{jenis}/finish', [TestEngineController::class, 'finish'])->name('api.test.finish');

    // Kuesioner Kepuasan Murid
    Route::get('/survey', [TestEngineController::class, 'getSurvey'])->name('api.survey.get');
    Route::post('/survey', [TestEngineController::class, 'submitSurvey'])->name('api.survey.post');

    // Jurnal Gigi (Habit Tracker)
    Route::get('/jurnal/hari-ini', [HabitController::class, 'hariIni'])->name('api.jurnal.hari-ini');
    Route::post('/jurnal/jawab', [HabitController::class, 'jawab'])->name('api.jurnal.jawab');
    Route::post('/jurnal/lewati', [HabitController::class, 'lewati'])->name('api.jurnal.lewati');
    Route::post('/jurnal/selesai', [HabitController::class, 'selesai'])->name('api.jurnal.selesai');
    Route::post('/jurnal/rekomendasi/{id}/terima', [HabitController::class, 'terimaRekomendasi'])->name('api.jurnal.rekomendasi.terima');
    Route::post('/jurnal/rekomendasi/{id}/tolak', [HabitController::class, 'tolakRekomendasi'])->name('api.jurnal.rekomendasi.tolak');
    Route::get('/jurnal/ringkasan', [HabitController::class, 'ringkasan'])->name('api.jurnal.ringkasan');
});

// Panel Admin (Peneliti & Guru)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:web')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Sekolah (Peneliti)
        Route::resource('schools', SchoolController::class)->except(['create', 'edit', 'show']);

        // Kelas
        Route::resource('classes', SchoolClassController::class)->except(['create', 'edit', 'show']);

        // Manajemen Murid
        Route::get('/students', [StudentManagementController::class, 'index'])->name('students.index');
        Route::post('/students/generate', [StudentManagementController::class, 'generate'])->name('students.generate');
        Route::get('/students/cards', [StudentManagementController::class, 'printCards'])->name('students.cards');
        Route::get('/students/{student}', [StudentManagementController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/reset-pin', [StudentManagementController::class, 'resetPin'])->name('students.reset-pin');
        Route::post('/students/{student}/unlock', [StudentManagementController::class, 'unlock'])->name('students.unlock');
        Route::post('/students/{student}/consent', [StudentManagementController::class, 'toggleConsent'])->name('students.consent');
        Route::post('/students/{student}/unlock-post-test', [StudentManagementController::class, 'unlockPostTest'])->name('students.unlock-post-test');
        Route::post('/students/{student}/reopen-checkin', [StudentManagementController::class, 'reopenCheckin'])->name('students.reopen-checkin');

        // Hasil Pre vs Post
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');

        // Laporan & Analisis Kebiasaan Gigi
        Route::get('/habits', [HabitManagementController::class, 'index'])->name('habits.index');

        // Ekspor CSV Penelitian (UTF-8 BOM)
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/hasil-tes', [ExportController::class, 'hasilTes'])->name('hasil-tes');
            Route::get('/jawaban-butir', [ExportController::class, 'jawabanButir'])->name('jawaban-butir');
            Route::get('/aktivitas', [ExportController::class, 'aktivitas'])->name('aktivitas');
            Route::get('/pilihan-makanan', [ExportController::class, 'pilihanMakanan'])->name('pilihan-makanan');
            Route::get('/survei', [ExportController::class, 'survei'])->name('survei');
            Route::get('/login-hari-aktif', [ExportController::class, 'loginHariAktif'])->name('login-hari-aktif');
            Route::get('/kebiasaan-harian', [ExportController::class, 'kebiasaanHarian'])->name('kebiasaan-harian');
            Route::get('/kebiasaan-ringkas', [ExportController::class, 'kebiasaanRingkas'])->name('kebiasaan-ringkas');
            Route::get('/rekomendasi-kebiasaan', [ExportController::class, 'rekomendasiKebiasaan'])->name('rekomendasi-kebiasaan');
        });
    });
});
