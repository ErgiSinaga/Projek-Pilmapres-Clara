# ADDENDUM 01 PLAN: Rencana Audit & Implementasi Jurnal Gigi & Penempatan Video

Dokumen rencana kerja teknis untuk implementasi fitur **Jurnal Gigi (Habit Tracker)** dan **Penempatan Video Terstruktur** berdasarkan spesifikasi [docs/SPEC_ADDENDUM_01.md](file:///c:/Users/Redmibook/Documents/projek_pilmapres/docs/SPEC_ADDENDUM_01.md).

---

## 1. Ringkasan Kondisi Proyek & Perbedaan Asumsi vs Kode Nyata

| Aspek | Asumsi dalam Addendum 01 | Kondisi Kode Nyata Saat Ini | Penyesuaian & Solusi |
|---|---|---|---|
| **Video IDs & Konten** | Mengacu ke `V1` sampai `V4` sebagai video inti. | Berkas `database/content/videos.json` menggunakan format ID `"video-1"`, `"video-2"`, `"video-3"`, `"video-4"`. | Sesuai aturan A7: *Pertahankan ID, file, dan data inti yang sudah ada*. ID eksisting dipertahankan, entri baru menggunakan `V0-pembuka`, `M1-sikat-malam`, `M2-sikat-pagi`, `M3-manis`, `M4-kumur`, dan `VP-penutup`. |
| **Tabel `video_progress`** | Perlu kolom `sumber` untuk mencatat asal pemutaran video. | Migration lama `2026_01_01_000060_create_videos_and_games_tables.php` hanya memiliki `student_id`, `video_id`, `detik_ditonton`, `selesai_at`, `skor_tantangan`. | Dibuat migration baru `2026_01_02_000020_add_sumber_to_video_progress_table.php` tanpa memodifikasi migration lama. |
| **Unlock Post-Test** | Menambahkan syarat `min_checkins` (default 3) pada mode auto. | Logika `checkPostTestUnlocked` berada di 2 tempat: `TestEngineController.php` dan `PetController.php` (mengecek `min_active_days`, `min_videos`, `min_level`). | Konsolidasikan logika atau sinkronkan kedua tempat untuk memeriksa `habitCheckins()->where('status', 'selesai')->count() >= $config['min_checkins']` jika mode `auto`. Buka manual oleh guru tetap meng-override syarat ini. |
| **Ekspor `aktivitas.csv`** | Harus memuat riwayat aktivitas video beserta kolom `sumber`. | `ExportController::aktivitas()` saat ini hanya membaca dari tabel `game_sessions`. | Diperbarui untuk menggabungkan `game_sessions` dan `video_progress` dengan kolom `sumber` (`kelas`, `rekomendasi`, `petunjuk_game`, `pembuka`, `penutup`). |
| **Dok Hub Navigasi** | Menampung tombol ke-7 **Jurnal**. | `home.js` saat ini memiliki 6 tombol dok: Makan, Sikat Gigi, Main, Kelas, Toko, Pesan. | Menambahkan tombol **Jurnal** dengan penataan fleksibel (flex/wrap responsif) agar tetap rapi pada resolusi 1024px. |
| **Status Hewan vs Jurnal** | Tanggapan jurnal tidak boleh mengubah `kebersihan` dan `kebahagiaan` secara manipulatif. | Controller mini game dan pet saat ini mengubah `kebersihan` & `kebahagiaan`. | Dipastikan kontroler jurnal (`HabitController`) memberikan reward tetap (koin 8, XP 10, kebahagiaan +5) tanpa membedakan jawaban baik/belum, dan status `kebersihan` **tidak disentuh**. |

---

## 2. Rencana Implementasi per Fase (H1 - H5)

### Fase H1: Backend Jurnal (Habit Tracker)
- **Database Migrations (Baru)**:
  - `database/migrations/2026_01_02_000010_create_habit_tracker_tables.php`:
    - `habit_checkins`: `id`, `student_id`, `tanggal` (date), `status` (`draft|selesai|dilewati`), `dilewati_count`, `started_at`, `finished_at`, `durasi_detik`, unique(`student_id`, `tanggal`).
    - `habit_answers`: `id`, `checkin_id`, `item_kode`, `nilai` (string), `nilai_num` (decimal nullable), `ms` (integer), `answered_at`, unique(`checkin_id`, `item_kode`).
    - `habit_recommendations`: `id`, `checkin_id`, `student_id`, `tipe` (`video|game`), `target_kode`, `alasan_item`, `shown_at`, `accepted_at` (nullable), `completed_at` (nullable).
  - `database/migrations/2026_01_02_000020_add_sumber_to_video_progress_table.php`:
    - Menambah kolom `sumber` (string nullable) pada `video_progress`.
- **Konfigurasi `config/dento.php`**:
  - Menambahkan array konfigurasi `'habit'` (enabled, school_days_only, streak_grace_days, prompt_on_first_login_of_day, allow_skip, max_recommendations_per_day, reward koin/xp/kebahagiaan, micro_video_reward).
  - Menambahkan `'min_checkins' => 3` pada `'post_test_unlock'`.
- **Konten JSON**:
  - `database/content/habits.json` (pertanyaan sikat_pagi, sikat_malam, manis, kumur, air_putih, acuan waktu, aturan `tampil_jika`).
  - `database/content/habit_tips.json` (kalimat tip bernada ajakan netral, berbasis whitelist A15).
  - `database/content/habit_rules.json` (aturan prioritas rekomendasi adaptif).
- **Model Eloquent**:
  - `App\Models\HabitCheckin.php`
  - `App\Models\HabitAnswer.php`
  - `App\Models\HabitRecommendation.php`
  - Relasi pada `App\Models\Student.php` (`habitCheckins`, `habitAnswers`, `habitRecommendations`).
- **Controller & Endpoints API**:
  - `App\Http\Controllers\Api\HabitController.php`:
    - `GET /api/jurnal/hari-ini` (syarat: pre-test harus selesai, 403 jika belum; evaluasi `tampil_jika`).
    - `POST /api/jurnal/jawab` (autosave per item, tolak jika sudah selesai/409, validasi opsi aktif).
    - `POST /api/jurnal/lewati` (catat `dilewati_count + 1`).
    - `POST /api/jurnal/selesai` (pastikan wajib terjawab, set selesai, beri reward flat, pilih maks 1 rekomendasi).
    - `POST /api/jurnal/rekomendasi/{id}/terima` & `/tolak`.
    - `GET /api/jurnal/ringkasan` (kalender 14 hari, agregat 7 hari, runtun lapor hari sekolah, lencana Pencerita).
  - Daftarkan rute di `routes/web.php`.
- **Testing**:
  - `tests/Feature/HabitTrackerTest.php`: verifikasi pra-syarat pre-test, autosave, immutabilitas setelah selesai, aturan `tampil_jika`, kesetaraan reward apa pun jawabannya, runtun lapor hari sekolah, dan isolasi reward.

---

### Fase H2: Frontend Jurnal & Maskot
- **Layar Check-in (`resources/js/screens/jurnal.js`)**:
  - Satu pertanyaan per layar dengan nomor & indikator titik kemajuan.
  - Kartu jawaban besar ($\ge 56$px) dengan ikon SVG buatan sendiri (tanpa emoji pihak ketiga).
  - Integrasi maskot Dento: ekspresi `senang` + `tunjuk`, balon bicara teks pertanyaan, tombol "dengarkan" jika audio aktif.
  - Autosave seketika saat kartu diklik, jeda umpan balik netral 1,5 detik (dapat dilewati dengan klik).
  - Layar penyelesaian: ucapan terima kasih jujur, klaim reward, dan maksimal 1 kartu rekomendasi opsional (tombol "Ayo lihat" vs "Nanti saja").
- **Layar Ringkasan Jurnal (`resources/js/screens/jurnal-ringkasan.js`)**:
  - Kalender 2 minggu terakhir yang hanya menandai tanggal sudah melapor (tanpa silang/merah/gagal).
  - Ringkasan refleksi 7 hari diri sendiri dengan bahasa netral satu warna merek.
  - Tampilan runtun lapor (streak hari sekolah) dan lencana "Pencerita" (3, 7, 14 check-in).
- **Pemicu Hub & Dok Menu (`resources/js/screens/home.js`)**:
  - Pemicu login pertama: dialog sapaan Dento ("Yuk cerita tentang gigimu hari ini" dengan pilihan "Ayo cerita" / "Nanti dulu").
  - Penambahan tombol dok ke-7 **Jurnal** dengan badge titik merah jika hari ini belum selesai check-in.
- **Registrasi Rute**:
  - `resources/js/app.js`: register rute `jurnal` dan `jurnal-ringkasan`.
- **Maskot Behavior**:
  - Tanggapan terhadap jawaban "belum" atau "makan manis" tetap `senang` + `lambai` (dilarang sedih/bingung).

---

### Fase H3: Penempatan Video & Struktur Baru
- **Pengayaan `videos.json`**:
  - Pertahankan `video-1` s/d `video-4` (tipe: `inti`, tetap memiliki kuis `tantangan`).
  - Tambahkan video mikro (`M1-sikat-malam`, `M2-sikat-pagi`, `M3-manis`, `M4-kumur`) dengan durasi 30-60 detik tanpa tantangan.
  - Tambahkan video pembuka `V0-pembuka` (30-45 detik, pemicu sekali setelah pre-test) dan penutup `VP-penutup` (30-45 detik, pemicu sekali setelah survei).
- **Layar Kelas (`resources/js/screens/videos.js`)**:
  - Dua tab navigasi: **Pelajaran Inti** (V1-V4, terbuka bertahap) dan **Video Singkat** (M1-M4, selalu terbuka setelah pre-test).
  - Parameter `sumber` dicatat ke backend saat memulai pemutaran (`kelas`, `rekomendasi`, `petunjuk_game`, `pembuka`, `penutup`).
- **Alur Transisi Khusus**:
  - Setelah Pre-Test tuntas $\rightarrow$ buka `V0-pembuka` (dengan tombol Lewati setelah 5 detik) $\rightarrow$ masuk ke Rumah Dento.
  - Setelah Post-Test & Survei tuntas $\rightarrow$ buka `VP-penutup` $\rightarrow$ masuk ke halaman Sertifikat.
- **Pemicu dari Mini Game**:
  - Game *Pilah Makanan* (3x salah berturut) $\rightarrow$ tawarkan tombol "Penjelasan" membuka `M3-manis`.
  - Game *Urutan Sikat* (2x gagal) $\rightarrow$ tawarkan tombol "Penjelasan" membuka `M1-sikat-malam`.
- **Spesifikasi & Placeholder**:
  - Dokumen panduan produksi Clara: `docs/PANDUAN_VIDEO.md`.
  - Pembaruan skrip `scripts/make_placeholder_videos.sh`.
- **Syarat `min_checkins` pada Unlock Post-Test**:
  - Perbarui `checkPostTestUnlocked` di `TestEngineController.php` dan `PetController.php`.

---

### Fase H4: Admin Panel & Ekspor Riset Kebiasaan
- **Menu Baru Admin: Kebiasaan**:
  - `App\Http\Controllers\Admin\HabitManagementController.php` & view `resources/views/admin/habits/index.blade.php`.
  - Kebijakan otorisasi: Peneliti dapat melihat seluruh sekolah/kelas; Guru terbatas pada sekolahnya.
  - Metrik: persentase selesai vs dilewati, distribusi jawaban per butir, grafik tren mingguan (Chart.js lokal), perbandingan awal (3 hari pertama) vs akhir (3 hari terakhir) berpasangan per murid.
  - Catatan metodologis permanen: *Data ini adalah laporan diri murid, bukan observasi langsung.*
- **Detail Murid (`admin/students/show.blade.php`)**:
  - Tab Kebiasaan: garis waktu check-in, detail jawaban, dan tombol aksi "Buka lagi jurnal hari ini" jika murid perlu mengoreksi jawaban.
- **Dashboard Admin**:
  - Tambah kartu ringkasan "Check-in selesai hari ini" dan "Murid belum pernah lapor".
- **Ekspor CSV UTF-8 BOM**:
  - `kebiasaan_harian.csv`
  - `kebiasaan_ringkas.csv`
  - `rekomendasi_kebiasaan.csv`
  - Pembaruan `aktivitas.csv` memuat kolom `sumber`.
  - Seluruh CSV hanya memuat `kode_peserta`, tanpa nama asli murid.

---

### Fase H5: QA, Audit Larangan, & Dokumentasi Final
- **Pengujian Regresi Menyeluruh**:
  - Menjalankan `php artisan test` untuk memastikan 100% tes lama dan tes baru lulus hijau.
- **Audit Larangan Spesifikasi (A14 & A19)**:
  - Verifikasi: tidak ada hadiah berbasis jawaban "baik"; kebersihan Dento tidak berubah karena jurnal; tidak ada ekspresi sedih/bingung di jurnal; tidak ada kata "deteksi"; V0 tidak pernah muncul sebelum pre-test.
- **Dokumentasi Lengkap**:
  - `README.md`: dokumentasi Jurnal Gigi, konfigurasi `habit`, panduan ekspor baru, catatan persetujuan orang tua untuk data kebiasaan kesehatan anak.
  - `DECISIONS.md`: pencatatan arsitektur habit tracker, isolasi reward, penempatan video mikro/interstitial.
  - `README_KONTEN.md`: daftar draf baru (`habits.json`, `habit_tips.json`, `habit_rules.json`, video baru) dengan status `DRAFT`.
  - `docs/PANDUAN_VIDEO.md`: panduan teknis video untuk Clara.

---

## 3. Matriks Risiko Regresi & Rencana Pengendalian

| Komponen Rawan | Risiko Regresi | Strategi Pengendalian & Verifikasi |
|---|---|---|
| **Alur Pre-Test** | Murid dapat mengakses Jurnal atau Video Pembuka sebelum menyelesaikan Pre-Test. | Pasang proteksi server (HTTP 403) pada endpoint `/api/jurnal/*` jika status pre-test belum `finished`. Di frontend, rute `#jurnal` otomatis diarahkan ke `#test?jenis=pre`. |
| **Integritas Reward & Koin** | Kuis atau check-in jurnal memberikan koin ganda jika diakses berulang kali. | Pengecekan idempotensi di backend: reward jurnal hanya diberikan 1x per hari kalender saat check-in pertama kali berstatus `selesai`. Penyelesaian ulang hari yang sama tidak menambah koin/XP. |
| **Unlock Otomatis Post-Test** | Post-test terbuka terlalu cepat atau macet tidak mau terbuka jika `min_checkins` aktif. | Pengujian feature test spesifik untuk evaluasi `min_checkins`. Opsi override guru lewat tombol "Buka Post-Test Manual" tetap berfungsi independen dari syarat otomatis. |
| **Pemutar Video (Video Player)** | Player crash jika file video fisik MP4 belum diunggah. | Komponen player kustom mempertahankan mekanisme fallback kartu ramah teks dokter gigi dengan tombol "Tandai sudah tonton bersama guru" yang tetap mencatat durasi dan `sumber`. |
| **Server Background Process** | Server latar belakang tertinggal aktif di environment developer. | Sesuai aturan: setiap pengujian server hanya dijalankan sesaat untuk verifikasi lalu segera dihentikan (`manage_task kill`). |
