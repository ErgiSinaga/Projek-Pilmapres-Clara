# Dento - Sistem Edukasi Kesehatan Gigi Anak

Sistem web game edukasi kesehatan gigi interaktif untuk murid Sekolah Dasar (SD kelas 3-5). Dikembangkan untuk mendukung penelitian Pilmapres (Pemilihan Mahasiswa Berprestasi) dengan metodologi eksperimen pre-test dan post-test, menghasilkan data riset empiris yang bersih, terstandar, dan bebas dari kebocoran instrumen.

---

## Daftar Isi
- [Fitur Utama](#fitur-utama)
- [Akun Demo Pengujian](#akun-demo-pengujian)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Panduan Instalasi Lokal](#panduan-instalasi-lokal)
- [Struktur Mini Game Edukasi](#struktur-mini-game-edukasi)
- [Kelas Dokter Gigi (Video & Tantangan)](#kelas-dokter-gigi-video--tantangan)
- [Panel Peneliti, Guru & Ekspor Riset](#panel-peneliti-guru--ekspor-riset)
- [Panduan Deployment ke Shared Hosting (cPanel / DirectAdmin)](#panduan-deployment-ke-shared-hosting-cpanel--directadmin)
- [Pengujian Otomatis (Testing)](#pengujian-otomatis-testing)
- [Prosedur Validasi Konten Klinis](#prosedur-validasi-konten-klinis)
- [Dokumentasi Terkait](#dokumentasi-terkait)

---

## Fitur Utama

1. **Rumah Dento (Virtual Pet Game)**:
   - Maskot Dento berbentuk gigi dengan kostum pahlawan kesehatan gigi (jubah merah, lencana emas, cermin kepala dokter gigi, sepatu biru, dan sikat gigi ajaib).
   - Animasi SVG hidup: mengambang, berkedip, jubah berkibar, melambai, tatapan mata mengikuti kursor mouse, dan timer tidur saat idle.
   - Status: `kebersihan` (0-100), `kebahagiaan` (0-100), `koin`, `xp`, `level`.
   - Mekanik peluruhan suportif dihitung server: batas bawah (*floor*) = 30, penurunan maksimal per absen = 40. Dento tidak pernah "mati" atau "sakit parah".

2. **Instrumen Pre-Test & Post-Test Terstandar**:
   - Pre-test wajib diselesaikan sebelum masuk ke Rumah Dento; otomatis terkunci permanen setelah selesai.
   - Post-test terbuka sesuai aturan konfigurasi (otomatis berdasarkan hari aktif, video selesai, level; atau manual dibuka oleh guru/peneliti).
   - Kunci jawaban tidak pernah dibocorkan ke browser murid. Urutan opsi diacak secara deterministik per murid dan dicatat di database.
   - Pencatatan durasi berpikir per butir soal dalam milidetik (`ms`).

3. **5 Mini Game Edukasi (Aksesibel & Keyboard Friendly)**:
   - **Pilah Makanan** (TP3): Mengelompokkan 10 makanan ke keranjang "Gigi Senang" atau "Gigi Sedih".
   - **Urutan Sikat** (TP2): Menyusun 6 langkah menyikat gigi yang benar.
   - **Sikat Bersih** (TP1, TP2): Menggosok plak kekuningan pada 6 zona gigi dengan timer 2 menit positif.
   - **Memory Fakta** (TP1, TP4, TP5): Mencocokkan 6 pasang kartu konsep dan fakta kesehatan gigi.
   - **Kuis Kilat** (Semua TP): 5 soal acak dari bank kuis yang **terpisah** dari soal pre/post-test.

4. **Kelas Dokter Gigi (Video Player & Tantangan)**:
   - 4 materi video bertahap (Video N terbuka setelah Video N-1 selesai).
   - Player kustom: tanpa seek maju (mencegah skip materi), boleh rewind untuk mengulang.
   - Fallback ramah jika video belum diunggah: opsi pendampingan "Tandai sudah tonton bersama guru".
   - Kuis tantangan pemahaman berhadiah koin & XP (hanya 1x hadiah per video).

5. **Jurnal Gigi (Habit Tracker & Indikator Perilaku)**:
   - **Sesi Ideal Pemicu Otomatis**: Muncul dialog sapaan ramah Dento pada login pertama setiap hari di Rumah Dento (*"Yuk cerita tentang gigimu hari ini!"*) dengan opsi *"Ayo Cerita"* atau *"Nanti Dulu"*.
   - **Akses Mandiri Navigasi Dok**: Tombol ke-7 **Jurnal** di dok bawah Rumah Dento dengan badge titik merah jika hari ini belum mengisi.
   - **Kualitas Data & Kejujuran Anak**: Hadiah lapor tetap flat (+8 Koin, +10 XP, +5 Kebahagiaan) untuk jawaban apa pun. Status `kebersihan` Dento **sama sekali tidak dipengaruhi** isi laporan. Dento selalu merespons ramah dan suportif (tidak pernah sedih/bingung).
   - **Rekomendasi Adaptif**: Maksimal 1 rekomendasi kontekstual per hari dari aturan prioritas `habit_rules.json`.
   - **Ringkasan Jurnal**: Kalender 14 hari lapor (hanya menandai hari sudah lapor, tanpa tanda silang/merah/gagal), rekap 7 hari diri sendiri, runtun lapor hari sekolah (streak), dan lencana *Pencerita*.

6. **Sertifikat Pahlawan Gigi & Kuesioner Kepuasan**:
   - Kuesioner kepuasan 6 butir menggunakan skala wajah SVG ramah anak.
   - Halaman sertifikat resmi memuat kode peserta, nama karakter Dento, capaian level, stempel verifikasi Pilmapres, dan layout cetak rapi (`@media print`).

7. **Privasi Penuh (Zero PII)**:
   - Sistem **sama sekali tidak menyimpan nama asli, email, atau foto murid** di database.
   - Murid masuk hanya dengan **Kode Peserta** (`{SEKOLAH}-{NN}`) dan **PIN 4 digit**.

---

## Akun Demo Pengujian

Aplikasi telah dilengkapi data awal (seeder) untuk pengujian:

| Peran | Login / URL | Kredensial | Keterangan |
|---|---|---|---|
| **Peneliti** | `/admin/login` | Email: `peneliti@dento.test`<br>Password: `password123` | Akses seluruh sekolah, kelas, metrik hasil, dan ekspor CSV lengkap |
| **Guru** | `/admin/login` | Email: `guru@dento.test`<br>Password: `password123` | Akses khusus SD Negeri 1 Magelang (Kelas 4A) |
| **Murid Demo** | `/` (`#login`) | Kode: `DEMO-01` s/d `DEMO-10`<br>PIN: `1234` | Sesi murid (pre-test, Rumah Dento, mini game, video, post-test) |

---

## Persyaratan Sistem

- **PHP**: >= 8.2 (Sangat disarankan PHP 8.4)
  - Ekstensi wajib: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
- **Database**: MySQL >= 8.0 atau MariaDB >= 10.4
- **Node.js**: >= 18.x
- **Composer**: >= 2.x

---

## Panduan Instalasi Lokal

1. **Clone dan Masuk ke Folder Proyek**:
   ```bash
   cd c:/Users/Redmibook/Documents/projek_pilmapres
   ```

2. **Pasang Dependensi PHP (Composer)**:
   ```bash
   composer install
   ```

3. **Pasang Dependensi Frontend (Node.js)**:
   ```bash
   npm install
   ```

4. **Konfigurasi Lingkungan (`.env`)**:
   Salin berkas `.env.example` ke `.env`:
   ```bash
   cp .env.example .env
   ```
   Sesuaikan pengaturan koneksi MySQL di `.env`:
   ```ini
   APP_NAME=Dento
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=dento
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Generate App Key & Migrasi Database**:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

6. **Kompilasi Aset Frontend (Vite)**:
   ```bash
   npm run build
   ```
   *Atau jalankan `npm run dev` untuk hot module replacement selama pengembangan.*

7. **Jalankan Web Server**:
   ```bash
   php artisan serve --port=8000
   ```
   Akses di peramban: `http://localhost:8000`

---

## Panel Peneliti, Guru & Ekspor Riset

Masuk melalui tautan `/admin/login`.

### Fitur Panel Admin:
1. **Manajemen Sekolah & Kelas**: Pengelompokan data sekolah dan rombongan belajar.
2. **Generate Massal Murid & Kartu PIN Cetak**: Menghasilkan akun murid anonim siap cetak (hanya tampil sekali demi privasi).
3. **Reset PIN & Unlock Lockout**: Membantu murid yang lupa PIN atau terkunci karena 5x salah input.
4. **Pembukaan Post-Test Manual**: Guru dapat membuka post-test serentak di kelas.
5. **Evaluasi Pre vs Post-Test**:
   - Ringkasan $N$ pasangan lengkap, rata-rata skor pre-test, post-test, selisih ($\Delta$), dan $N$-Gain.
   - Grafik batang interaktif Chart.js per Tujuan Pembelajaran (TP1-TP5) dimuat secara lokal.
   - Catatan eksplisit: Uji signifikansi paired t-test/Wilcoxon dilakukan di luar sistem (SPSS/JASP/R).

### 6 Format Ekspor CSV (UTF-8 BOM untuk Microsoft Excel):
1. `hasil_tes.csv`: `kode_peserta`, `sekolah`, `kelas`, `pre_skor`, `pre_selesai_at`, `post_skor`, `post_selesai_at`, `selisih`, `n_gain`.
2. `jawaban_butir.csv`: `kode_peserta`, `jenis_tes`, `nomor_soal`, `tp`, `jawaban_dipilih`, `kunci_jawaban`, `is_benar`, `durasi_detik`.
3. `aktivitas.csv`: `kode_peserta`, `aktivitas_kode`, `waktu_mulai`, `waktu_selesai`, `skor`, `durasi_detik`.
4. `pilihan_makanan.csv`: `kode_peserta`, `sesi_id`, `food_id`, `nama_makanan`, `kategori`, `pilihan_murid`, `is_benar`, `waktu_pikir_ms`.
5. `survei.csv`: `kode_peserta`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `skor_total`, `diisi_at`.
6. `login_hari_aktif.csv`: `kode_peserta`, `tanggal`, `jumlah_login`, `total_durasi_menit`.

---

## Panduan Deployment ke Shared Hosting (cPanel / DirectAdmin)

1. **Persiapan di Komputer Lokal**:
   - Pastikan build asset production sudah dijalankan:
     ```bash
     npm run build
     ```
   - Kompres seluruh isi folder proyek ke dalam berkas `dento.zip`, **kecuali** folder `node_modules`, `.git`, dan isi `storage/logs/*.log`.

2. **Unggah dan Ekstrak di Hosting**:
   - Masuk ke cPanel File Manager.
   - Buat folder di luar `public_html`, misalnya `~/dento_core/`.
   - Ekstrak isi berkas `dento.zip` ke dalam folder `~/dento_core/`.

3. **Pengaturan Dokumen Root (`public_html`)**:
   - Pindahkan seluruh berkas dari `~/dento_core/public/` ke `~/public_html/` (atau direktori subdomain target).
   - Sunting berkas `~/public_html/index.php`:
     ```php
     // Arahkan ke lokasi autoload dan app.php di dento_core
     require __DIR__.'/../dento_core/vendor/autoload.php';
     $app = require_once __DIR__.'/../dento_core/bootstrap/app.php';
     ```

4. **Konfigurasi Database di cPanel**:
   - Buat database MySQL baru di cPanel (misal: `u123_dento`).
   - Buat user database dan berikan izin ALL PRIVILEGES.
   - Salin `.env.example` menjadi `~/dento_core/.env` dan isi kredensial MySQL cPanel tersebut.
   - Atur `APP_ENV=production` dan `APP_DEBUG=false`.

5. **Migrasi Database di Hosting**:
   - Lewat SSH / Terminal cPanel:
     ```bash
     cd ~/dento_core
     php artisan migrate --seed --force
     php artisan optimize
     ```
   - Atau lewat phpMyAdmin dengan mengekspor struktur & data dari lokal lalu diimpor ke database cPanel.

6. **Izin Folder Storage**:
   - Pastikan direktori `storage/` dan `bootstrap/cache/` memiliki izin tulis (permission `775` atau `755`).
   - Jalankan `php artisan storage:link` jika diperlukan.

---

## Pengujian Otomatis (Testing)

Proyek ini memiliki unit dan feature tests menyeluruh mencakup autentikasi murid/guru, mesin tes, batasan koin, video progress, kalkulasi skor server-side, dan ekspor data penelitian.

Jalankan test suite lengkap:
```bash
php artisan test
```

Format kode sesuai standar Laravel Pint:
```bash
vendor/bin/pint --format agent
```

---

## Prosedur Validasi Konten Klinis

Seluruh butir tes, video tantangan, dan klasifikasi makanan awal berstatus **`DRAFT` (Placeholder)**.
Sebelum digunakan untuk pengambilan data penelitian skripsi/lomba Pilmapres:
1. Konsultasikan isi `README_KONTEN.md` dengan dokter gigi spesialis anak / pembimbing.
2. Perbarui konten pada berkas JSON di `database/content/`:
   - `test_items.json`
   - `foods.json`
   - `quiz_bank.json`
   - `videos.json`
   - `daily_messages.json`
3. Sinkronkan ke database dengan:
   ```bash
   php artisan db:seed --class=ContentSeeder
   ```

---

## Dokumentasi Terkait

- [Spesifikasi Sistem Lengkap (SPEC.md)](docs/SPEC.md)
- [Catatan Keputusan Desain & Arsitektur (DECISIONS.md)](DECISIONS.md)
- [Daftar Konten Klinis & Panduan Validasi (README_KONTEN.md)](README_KONTEN.md)
