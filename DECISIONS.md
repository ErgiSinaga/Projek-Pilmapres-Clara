# DECISIONS.md: Keputusan Teknis dan Arsitektur "Dento"

Dokumen ini mencatat seluruh keputusan teknis dan arsitektural yang diambil dalam perancangan dan implementasi sistem edukasi kesehatan gigi anak "Dento" (Fase 0 hingga Fase 7).

---

## 1. Versi PHP dan Framework Laravel (Fase 0)
- **PHP**: PHP 8.4.20 (CLI / NTS)
  - *Alasan*: Rilis stabil modern yang didukung penuh oleh Laravel. Menjamin keamanan, type safety, dan performa eksekusi optimal.
- **Laravel Framework**: Laravel 12 (`laravel/framework` v13.43.0)
  - *Alasan*: Versi stabil terbaru dari Laravel. Memiliki struktur bootstrap modern (`bootstrap/app.php`), routing terpadu, dan integrasi Vite cepat.
- **Database**: MySQL / MariaDB 10.4.32 (via PDO MySQL)
  - *Alasan*: Standar industri yang sangat kompatibel dengan server sekolah dan shared hosting di Indonesia.

---

## 2. Frontend Murid: Vanilla JavaScript (ES Modules) + Vite SPA (Fase 0, 3, 4, 5)
- **Tanpa Framework Berat (No React/Vue/Angular)**:
  - *Alasan*: Target pengguna adalah murid SD kelas 3-5 dengan laptop sekolah berspesifikasi beragam dan koneksi bersama. Vanilla JS dengan dynamic imports membuat initial bundle hanya ~6.7 KB (~2.2 KB gzip), loading instan, dan memori sangat hemat.
- **Router Hash Sendiri (`router.js`)**:
  - *Alasan*: Rute berbasis `#` (`#login`, `#home`, `#game/sikat-bersih`, `#videos`, `#certificate`) berjalan mulus di server sekolah tanpa memerlukan konfigurasi URL rewriting kompleks di Apache/Nginx.
- **Store Sederhana (`store.js`) & Audio Engine Mandiri (`audio.js`)**:
  - *Alasan*: Pengelolaan state murid dan perlengkapan secara reaktif tanpa library pihak ketiga. Efek suara disintesis via Web Audio API (jump, pop, wobble) sehingga tidak bergantung pada aset audio eksternal yang rawan gagal muat.

---

## 3. Font dan Aset Mandiri (Zero External CDN) (Fase 0, 3)
- **Font Baloo 2 (`@fontsource/baloo-2`)**:
  - *Alasan*: Dilarang menggunakan Google Fonts atau CDN pihak ketiga demi keandalan saat internet sekolah lambat. Font di-bundle dan di-host lokal.
- **Maskot & Aset Hand-Crafted SVG**:
  - *Alasan*: Seluruh bagian tubuh Dento (badan, mata, mulut, cermin kepala, jubah berkibar, lencana bintang, sepatu biru, sikat gigi ajaib), ekspresi wajah (senang, sedih, semangat, bangga, bingung, netral, tidur), pose, aksesori toko, dan latar ruangan/taman/langit digambar dengan kode SVG murni sesuai spesifikasi Lampiran A & B.

---

## 4. Keamanan, Autentikasi, dan Privasi Riset (Zero PII) (Fase 1)
- **Dilarang Menyimpan Nama Asli / PII Murid**:
  - *Alasan*: Menjaga privasi etika penelitian anak SD. Database hanya menyimpan `kode` peserta (`{SEKOLAH}-{NN}`) dan `pin_hash` (bcrypt). Guru memegang kartu fisik pemetaan kode ke nama murid di luar sistem.
- **Multi-Guard Session Authentication**:
  - *Guard `web`*: Login email + password untuk Peneliti dan Guru.
  - *Guard `student`*: Session berbasis kode peserta + PIN 4-digit dengan proteksi 5x salah percobaan menghasilkan kunci sementara 5 menit (`failed_attempts` & `locked_until`).
- **Pemisahan Hak Akses Guru vs Peneliti**:
  - Diterapkan melalui Global Scopes di model Eloquent (`Student`, `School`, `SchoolClass`) dan Policy Laravel, memastikan guru hanya dapat melihat dan mengekspor data murid di sekolahnya sendiri.

---

## 5. Mesin Tes & Integritas Data Evaluasi (Fase 2)
- **Pencegahan Kebocoran Kunci Jawaban**:
  - Field `benar` tidak pernah dikirimkan ke frontend murid pada pre-test maupun post-test.
  - Urutan opsi diacak secara deterministik per percobaan (`opsi_urutan`) dan disimpan di database `test_answers`.
- **Penguncian Pre-Test**:
  - Pre-test wajib diselesaikan sebelum masuk Rumah Dento, dan terkunci permanen setelah selesai. Post-test memiliki aturan unlock fleksibel (otomatis berdasar hari aktif/video/level, atau manual dibuka oleh guru).

---

## 6. Mini Game & Pembatasan Hadiah Server-Side (Fase 4)
- **Server Authority on Rewards**:
  - Nilai skor yang dikirim oleh klien selalu di-clamp ke batas maksimum yang valid (misal: skor 999 di-clamp ke 10 pada Pilah Makanan).
  - Poin kebersihan, kebahagiaan, XP, dan koin dihitung di server.
- **Batas Koin Harian (*Daily Coin Cap*)**:
  - Maksimal 30 koin per aktivitas per hari (`coin_cap_per_activity_per_day = 30`). Jika batas tercapai, koin berhenti bertambah namun XP dan status kebersihan/kebahagiaan tetap diberikan agar anak tetap termotivasi belajar.
- **Bank Soal Kuis Kilat Terpisah**:
  - Bank soal mini game `kuis-kilat` disimpan di `quiz_bank.json` dan dipastikan terpisah dari `test_items.json` agar tidak membocorkan butir instrumen penelitian pre/post-test.

---

## 7. Video Pembelajaran & Kelas Dokter Gigi (Fase 5)
- **Player Kustom Tanpa Seek Maju**:
  - Murid tidak dapat menyeret *progress bar* video maju untuk melewati materi, namun diizinkan menggunakan tombol rewind 10 detik untuk mengulang bagian yang belum dipahami.
- **Verifikasi Durasi Tonton**:
  - Penyelesaian video mensyaratkan tontonan kumulatif $\ge 90\%$ durasi, atau melalui tombol konfirmasi pendampingan guru (*teacher-assisted*) jika menggunakan proyektor kelas bersama.
- **Hadiah Kuis Tantangan Satu Kali**:
  - Kuis tantangan video memberikan hadiah +15 koin, +20 XP, +5 kebersihan, +5 kebahagiaan hanya pada penyelesaian pertama. Menonton ulang tidak menggandakan koin.

---

## 8. Evaluasi Hasil, Ekspor CSV & Sertifikat (Fase 6)
- **Formula $N$-Gain**:
  - Dihitung menggunakan rumus standar Hake: $g = \frac{\text{Post} - \text{Pre}}{\text{Total} - \text{Pre}}$.
  - Uji signifikansi statistik (paired t-test / Wilcoxon) ditegaskan dilakukan di luar sistem (SPSS/JASP/R).
- **Encoding CSV UTF-8 dengan BOM (`\xEF\xBB\xBF`)**:
  - 6 format ekspor CSV dilengkapi Byte Order Mark agar dapat langsung dibuka secara rapi di Microsoft Excel versi bahasa Indonesia maupun internasional tanpa masalah pemisahan kolom atau karakter rusak.
- **Isolasi Chart.js**:
  - Library `chart.js` dari npm di-bundle dalam file `admin-chart.js` terpisah (~70 KB gzip) yang hanya dimuat di halaman admin hasil evaluasi, sehingga tidak membebani frontend murid sama sekali.
- **Sertifikat Cetak (@media print)**:
  - Didesain dengan CSS print khusus yang menyembunyikan tombol antarmuka dan menyesuaikan margin halaman agar langsung siap dicetak atau disimpan sebagai PDF penghargaan.

---

## 9. Aksesibilitas dan Standar Ramah Anak (Fase 7)
- **Target Klik $\ge 56$ px**:
  - Seluruh tombol utama, opsi kuis, keranjang pilah makanan, dan pilihan survei memiliki tinggi minimal 56px untuk memudahkan interaksi dengan touchpad laptop anak SD.
- **Kontras Warna Tinggi ($\ge 4.5:1$)**:
  - Teks menggunakan warna *Deep Navy* (`#25435C`) di atas latar terang (`#FFFFFF` / `#E8F6FB`) memenuhi pedoman WCAG AA.
- **Dukungan Keyboard Lengkap**:
  - Angka 1..6 untuk zona sikat gigi dan nomor urutan sikat, angka 1..3 untuk pilihan kuis dan keranjang makanan.
