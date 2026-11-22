# BAGIAN A: SPEC (konteks utama, baca sebelum mengerjakan apa pun)

## A1. Ringkasan produk

Web game edukasi kesehatan gigi untuk murid SD, dipakai di sekolah di Magelang lewat guru, di laptop murid, dengan internet tersedia. Bagian dari proposal Pilmapres tingkat universitas (penelitian pre-test/post-test), jadi sistem **harus menghasilkan data penelitian yang bersih** selain menyenangkan.

Konsep: **Rumah Dento**, game perawatan hewan peliharaan ala Pou. Murid merawat **Dento**, maskot berbentuk gigi. Semua aksi perawatan adalah aktivitas belajar: makan = memilah makanan, sikat gigi = urutan dan gosok plak, kelas dokter gigi = video + tantangan. Ada koin, level, toko aksesori, pesan harian, pre-test, post-test, kuesioner kepuasan, dan sertifikat. Guru/peneliti punya panel admin untuk kelola murid dan melihat serta mengekspor data.

Maskot memandu di titik-titik penting dan menenangkan murid saat tes. Dento adalah gigi kecil berkostum **pahlawan gigi sehat**: jubah merah yang berkibar, cermin kepala dokter gigi di ikat kepala, lencana bintang emas, sepatu biru, dan sikat gigi ajaib. Ia hidup: mengambang pelan, berkedip, jubahnya berkibar, melambai, matanya mengikuti kursor, dan bereaksi pada aksi murid (kecuali saat tes, lihat Lampiran B). Nama maskot disimpan di `config/dento.php` (`mascot_name`, default "Dento", nama sementara) agar mudah diganti.

## A2. Asumsi yang bisa diubah lewat `config/dento.php`

Semua nilai ini harus berupa konfigurasi, bukan hardcode:

```php
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
  ],
  'coin_cap_per_activity_per_day' => 30,
  'decay' => ['kebersihan_per_hour' => 0.4, 'kebahagiaan_per_hour' => 0.5, 'floor' => 30, 'max_drop_per_absence' => 40],
  'xp' => ['activity' => 10, 'video' => 20, 'test' => 30],
  'level_thresholds' => [0, 50, 120, 220, 350, 500],
  'audio_enabled' => true,          // tombol "dengarkan" hanya tampil jika file audio ada
];
```

Mode penggunaan fleksibel: sistem harus jalan baik untuk uji coba satu sesi singkat (guru membuka post-test manual) maupun beberapa hari (unlock otomatis).

## A3. Stack dan aturan teknis

- Backend: **Laravel versi stabil terbaru** (buat dengan `composer create-project`), PHP >= 8.2, **MySQL**. Cek kompatibilitas versi PHP dengan hosting target sebelum memilih versi Laravel, dan catat di `DECISIONS.md`.
- Frontend murid: **JavaScript vanilla (ES modules) + Vite**, tanpa framework. Satu halaman (SPA) dengan router hash buatan sendiri dan store sederhana, supaya perpindahan video, game, dan transisi mulus. Dilayani oleh satu Blade view.
- Frontend admin: Blade biasa (+ sedikit JS), login email/password lewat Laravel Breeze (stack Blade) atau auth bawaan.
- Auth murid: guard terpisah `student` berbasis session, login dengan **kode peserta + PIN** (bukan email, bukan nama).
- Tanpa CDN dan tanpa layanan pihak ketiga. Font di-host sendiri (`@fontsource/baloo-2`). Grafik admin pakai Chart.js dari npm, dimuat hanya di halaman admin.
- Semua teks UI **Bahasa Indonesia**, `lang="id"`, zona waktu `Asia/Jakarta`.
- Tulis `DECISIONS.md` (keputusan teknis dan alasannya) dan `README.md` (instalasi, seed, build, deploy, cara mengganti konten).
- Jangan bertanya balik. Ambil keputusan masuk akal, catat di `DECISIONS.md`, lanjut.

## A4. Peran dan akun

| Peran | Login | Hak |
|---|---|---|
| `peneliti` | email + password | Semua sekolah/kelas, semua data, ekspor, pengaturan unlock |
| `guru` | email + password | Hanya sekolah/kelasnya: kelola murid, reset PIN, buka post-test, lihat progres |
| `student` | kode peserta + PIN | Hanya data dirinya |

Kode peserta format `{KODE_SEKOLAH}-{NN}` (contoh `S1-07`). Tidak ada kolom nama asli murid di database sama sekali. Daftar kode ke nama disimpan guru di kertas atau berkas sendiri di luar sistem.

## A5. Alur murid (end to end)

1. **Login** (kode + PIN). Salah PIN: pesan ramah yang menjelaskan apa yang harus dilakukan. Lewat batas gagal: terkunci sementara, tampilkan "Minta bantuan guru ya".
2. **Kunjungan pertama:** pilih nama hewan dari daftar nama preset (tidak ada input bebas supaya tidak berisi nama asli), cerita pembuka singkat Dento, tutorial 3 langkah.
3. **Pre-test** (hanya sekali, otomatis wajib sebelum akses fitur lain). Setelah selesai terkunci permanen.
4. **Rumah Dento** (hub utama, lihat A6).
5. Aktivitas: Makan, Sikat Gigi, Main, Kelas (video), Toko, Pesan hari ini.
6. **Post-test** terbuka sesuai aturan unlock (A2). Muncul sebagai undangan dari Dento di hub, hanya setelah terbuka.
7. **Kuesioner kepuasan** setelah post-test, lalu **sertifikat**. Hewan tetap bisa dimainkan setelahnya.

Pre-test dan post-test: soal sama (atau set setara sesuai config), urutan opsi diacak per percobaan dan urutan itu disimpan.

## A6. Mekanik Rumah Dento

**Status:** `kebersihan` (0-100), `kebahagiaan` (0-100), `koin`, `xp`, `level`.

**Peluruhan:** dihitung di server saat murid membuka `/api/home`, berdasarkan selisih waktu dari `last_decay_at`. Turun pelan, tidak pernah di bawah `floor`, dan total penurunan per absen dibatasi `max_drop_per_absence`. Hewan tidak boleh "mati" atau "sakit parah", tidak ada rasa bersalah, tidak ada notifikasi paksa.

**Tampilan Dento mengikuti kebersihan:** >= 70 normal; 40-69 plak kekuningan tipis; < 40 bercak gelap kecil dan ekspresi sedih lembut. Tidak boleh menakutkan; selalu ada jalan pulih lewat aktivitas.

**Efek aktivitas selesai:**

| Aktivitas | Kebersihan | Kebahagiaan | Koin | XP |
|---|---|---|---|---|
| Pilah Makanan | 0 | +10 | 2 per benar | 10 |
| Urutan Sikat | +10 | 0 | maks 12 | 10 |
| Sikat Bersih | +30 | 0 | maks 20 | 10 |
| Memory Fakta | 0 | +10 | maks 12 | 10 |
| Kuis Kilat | 0 | +10 | 2 per benar | 10 |
| Video + tantangan | +5 | +5 | 15 | 20 |
| Pesan harian (klaim) | 0 | 0 | 5 | 0 |

Batas koin per aktivitas per hari = `coin_cap_per_activity_per_day`. XP dan efek status tetap diberikan setelah batas koin tercapai, hanya koinnya yang berhenti. **Server yang menghitung hadiah**, nilai dari klien dipakai hanya sebagai skor yang di-clamp ke maksimum yang mungkin.

**Toko (8 item, harga koin):** topi dokter 40, topi merah 30, kacamata bulat 50, kacamata bintang 60, jubah biru 45, jubah hijau 45, latar taman 80, latar langit 100. Kostum dasar (jubah merah, cermin kepala, lencana, sepatu, sikat gigi ajaib) selalu terpasang dan gratis. Slot: `topi` (jika dipakai, ikat kepala dan cermin kepala disembunyikan), `kacamata`, `jubah` (hanya mengganti warna jubah), `latar` (default: ruangan Dento). Semua digambar sebagai SVG buatan sendiri di titik jangkar Lampiran A.7. Satu item per slot.

## A7. Mini game (spesifikasi)

Semua game: bisa diselesaikan dengan **mouse dan keyboard**, area klik minimal 56px, tidak ada hukuman koin, umpan balik salah selalu suportif ("Coba lagi ya") dengan alasan singkat. Setiap game dimuat lewat dynamic import. Setiap sesi dicatat di `game_sessions`, dan kejadian penting di `game_events`.

1. **`pilah-makanan`** (TP3). 10 putaran acak dari `foods.json` (min 20 item; tiap item: `id`, `nama`, `svg_key`, `kategori` = `baik|merusak`, `alasan`). Satu kartu makanan di tengah, dua keranjang: "Gigi senang" dan "Gigi sedih". Bisa klik keranjang **atau** seret kartu. Salah: Dento bingung + alasan, item muncul lagi satu kali di akhir. Event `food_choice` {food_id, pilihan, benar, ms}. Skor = benar/10.
2. **`urutan-sikat`** (TP2). 6 langkah menyikat gigi tersusun acak, murid klik kartu berurutan untuk mengisi slot 1-6 (click-to-place, bukan drag), tombol "Periksa". Boleh 3 kali coba; log jumlah percobaan.
3. **`sikat-bersih`** (TP1, TP2). Gigi besar dengan 6 zona (SVG), plak sebagai gelembung kekuningan. Murid menggerakkan kursor di atas zona (pointermove) untuk menggosok sampai bersih. Cincin timer 2 menit total (timer bersifat positif, bukan menakut-nakuti). Selesai jika semua zona bersih atau waktu habis. Skor = persen bersih. Log durasi per zona.
4. **`memory-fakta`** (TP1, TP4, TP5). 6 pasang kartu (gambar + kata), 12 kartu. Skor berdasarkan jumlah langkah.
5. **`kuis-kilat`** (semua TP). 5 soal acak dari `quiz_bank.json` (min 20 soal) dengan umpan balik langsung dan penjelasan singkat. **Penting: bank kuis ini harus BERBEDA dari soal pre/post-test** supaya tidak bocor ke instrumen penelitian.

## A8. Video

- File MP4 H.264+AAC di `public/media/videos/`, 480p, dengan `faststart`. Metadata di `database/content/videos.json`: `id`, `judul`, `file`, `poster`, `durasi_detik`, `tp`, `tantangan` (2-3 soal pendek tentang isi video), `caption_vtt` (opsional).
- Terbuka bertahap (video N terbuka setelah video N-1 selesai). 4 video draft: (1) Kenalan dengan gigi, (2) Makanan baik dan merusak, (3) Cara menyikat gigi, (4) Gigi berlubang dan dokter gigi.
- Pemutar sendiri: tombol putar/jeda besar, ulangi, **tanpa seek maju** (clamp posisi). Dianggap selesai jika waktu yang benar-benar ditonton >= 90% durasi (akumulasi dari `timeupdate`, bukan hanya event `ended`). Setelah selesai: tantangan video, lalu hadiah.
- Autoplay setelah interaksi pengguna (tombol "Mulai" dari hub dihitung). **Wajib ada tombol "Putar" besar sebagai cadangan** kalau autoplay diblokir. Pakai `playsinline`. `preload="metadata"`; video berikutnya baru dimuat ketika video aktif mencapai 80%.
- Transisi fade halus antar layar. Hormati `prefers-reduced-motion`.
- Jika file video tidak ada (placeholder), tampilkan kartu "Video belum tersedia" dan **jangan** crash. Sediakan `scripts/make_placeholder_videos.sh` (ffmpeg, video uji 5 detik) bila ffmpeg tersedia; kalau tidak, cukup README cara menaruh video.

## A9. Tes (instrumen penelitian)

- `test_items` di-seed dari `database/content/test_items.json`: 10 butir draft, 2 per tujuan pembelajaran, pilihan ganda 3 opsi, dengan `set` ('A'), `tp`, `teks`, `opsi`, `benar` (indeks), `gambar` (opsional), `audio` (opsional), `status: "DRAFT"`.
- Satu soal per layar. Tombol Dento "dengarkan" hanya jika file audio ada. **Tidak ada umpan balik benar/salah dan tidak ada penjelasan selama tes** (menjaga validitas). Pesan netral dan menenangkan dari maskot ("Ini bukan ujian, jawab sesuai yang kamu tahu ya").
- Jawaban disimpan **per butir saat itu juga**. Jika halaman di-refresh atau murid keluar, lanjut dari soal terakhir yang belum dijawab. Tidak bisa kembali ke soal sebelumnya. Satu percobaan per jenis (`pre`/`post`) per murid; setelah selesai terkunci.
- Catat: pilihan, benar/salah, waktu per soal (ms), waktu mulai/selesai, urutan opsi yang ditampilkan.
- Layar akhir hanya menampilkan bintang (bukan skor angka) agar tidak membebani anak. Skor tersimpan utuh di database.
- **Kuesioner kepuasan** (setelah post-test): 6 pernyataan skala 1-5 dengan wajah SVG sederhana buatan sendiri, bukan emoji font. Draft pernyataan: aku suka bermain bersama Dento; aku mengerti isi video; aku mau main lagi; game mudah dipakai; aku jadi tahu cara menyikat gigi; aku suka Dento.

## A10. Panel admin

Rute `/admin`:

- **Dashboard:** jumlah murid, pre selesai, post selesai, rata-rata hari aktif.
- **Sekolah dan Kelas** (peneliti): CRUD.
- **Murid:** daftar dengan filter kelas; **generate massal** (jumlah murid per kelas → kode otomatis `{KODE_SEKOLAH}-{NN}` + PIN acak). PIN **hanya ditampilkan sekali** di halaman cetak kartu kode (satu kartu per murid, siap cetak, tanpa nama), di database hanya hash. **Reset PIN** membuat PIN baru yang ditampilkan sekali. Tombol buka kunci setelah terkunci, tandai consent (`consent_confirmed_at`), **buka post-test manual**.
- **Detail murid:** garis waktu (login, tes, video, game), status hewan, level.
- **Hasil:** tabel pre vs post per sekolah/kelas/per tujuan pembelajaran: rata-rata pre, rata-rata post, selisih, N pasangan lengkap, kolom N-Gain (opsional, label jelas). Grafik batang (Chart.js). **Sistem tidak menghitung uji signifikansi**; analisis statistik dilakukan di luar (SPSS/JASP/dll.). Tulis catatan itu di halaman.
- **Ekspor CSV** (UTF-8 BOM, aman untuk Excel): `hasil_tes.csv` (ringkas per murid), `jawaban_butir.csv`, `aktivitas.csv` (video dan game), `pilihan_makanan.csv`, `survei.csv`, `login_hari_aktif.csv`. Hanya berisi kode peserta, tidak ada nama.
- Guru hanya melihat data sekolahnya (pakai Policy dan global scope, dengan test).

## A11. Model data (migrations)

`users` (admin: name, email, password, role `peneliti|guru`, school_id null) · `schools` (kode, nama) · `classes` (school_id, nama, tingkat) · `students` (class_id, school_id, kode unik, pin_hash, pet_name, failed_attempts, locked_until, consent_confirmed_at) · `pet_states` (student_id unik, kebersihan, kebahagiaan, koin, xp, level, last_seen_at, last_decay_at) · `items` (kode, nama, slot, harga, svg_key) · `inventories` (student_id, item_id, equipped) · `test_items` (set, urutan, tp, teks, opsi json, benar, gambar, audio, penjelasan_internal, status) · `test_attempts` (student_id, jenis `pre|post`, started_at, finished_at, skor, total, durasi_detik; unik student_id+jenis) · `test_answers` (attempt_id, test_item_id, pilihan, benar, ms, opsi_urutan json) · `video_progress` (student_id, video_id, detik_ditonton, selesai_at, skor_tantangan) · `game_sessions` (student_id, game_kode, started_at, finished_at, skor, detail json) · `game_events` (game_session_id, student_id, tipe, payload json, created_at) · `daily_claims` (student_id, tanggal, unik student_id+tanggal) · `survey_responses` (student_id, butir_kode, nilai) · `login_logs` (student_id, login_at). Hari aktif = jumlah tanggal berbeda di `login_logs`.

## A12. API murid (JSON, session + CSRF)

`POST /api/login` · `POST /api/logout` · `GET /api/me` · `GET /api/home` (menerapkan peluruhan, mengembalikan status hewan, item terpasang, status tes dan video) · `POST /api/pet/name` · `POST /api/pet/equip` · `GET /api/shop` · `POST /api/shop/buy` · `GET /api/test/{jenis}/start` (buat atau lanjutkan, tanpa kunci jawaban) · `POST /api/test/{jenis}/answer` · `POST /api/test/{jenis}/finish` · `GET /api/videos` · `POST /api/videos/{id}/progress` · `POST /api/videos/{id}/complete` · `POST /api/videos/{id}/challenge` · `POST /api/games/{kode}/start` · `POST /api/games/{kode}/event` · `POST /api/games/{kode}/finish` · `GET /api/daily-message` · `POST /api/daily-message/claim` · `GET|POST /api/survey`.

Kunci jawaban tes dan kuis **tidak pernah** dikirim ke klien sebelum dijawab. Validasi semua input dengan Form Request. Rate limit pada `/api/login`.

## A13. Struktur frontend

```
resources/js/
  app.js  router.js  store.js  api.js  audio.js
  mascot/                 (parts.js, accessories.js, mascot.js, mascot.css: SVG berkostum, ekspresi, pose, animasi)
  dev/mascot-gallery.js   (galeri uji maskot, hanya APP_ENV=local)
  ui/                     (tombol, modal, bubble bicara, progress ring, status bar)
  screens/                (login, onboarding, test, home, videos, shop, daily, survey, certificate)
  games/                  (pilah-makanan, urutan-sikat, sikat-bersih, memory-fakta, kuis-kilat)
database/content/         (foods.json, quiz_bank.json, test_items.json, videos.json, daily_messages.json, steps.json, shop_items.json)
public/media/{videos,audio,img}
```

Konten dibaca dari file JSON dan di-seed ke database; mengganti konten = mengganti JSON lalu `php artisan db:seed --class=ContentSeeder`. Jelaskan di README.

## A14. UI/UX dan desain

Audiens: murid SD memakai laptop di kelas. Tugas utama layar: menarik tapi jelas, setiap layar punya satu aksi utama.

- **Palet dari maskot:** biru langit `#4AA8D8`, biru muda `#E8F6FB`, putih gigi `#FFFDF7`, pink pipi `#FFB3C1`, emas lencana `#FFD65A` (garis `#D9A400`), merah jubah `#E85D5D` (garis `#B93A3A`), biru sepatu `#2F7FAE`, perak cermin `#D9E2EA` (garis `#7F97AA`), navy teks `#25435C`. Teks biru gelap `#1F6E9C` untuk judul. Merah jubah dipakai hemat sebagai aksen hadiah dan naik level, bukan warna dasar antarmuka. Jangan menambah palet krem/terakota atau hitam pekat dengan aksen neon.
- **Huruf:** Baloo 2 (500-700), self-host. Teks isi minimal 20px, judul besar. Kalimat pendek, sentence case, kata kerja aktif, tanpa huruf kapital semua, tanpa label kecil di atas judul. Pesan error menyebut apa yang terjadi dan apa yang harus dilakukan, tanpa meminta maaf berlebihan.
- **Rumah Dento:** adegan ruangan (SVG), bukan dashboard kartu. Dento di tengah dengan animasi diam pelan (CSS), bar status di atas, dok ikon bulat di bawah dengan label ("Makan", "Sikat gigi", "Main", "Kelas", "Toko", "Pesan"). Ikon digambar SVG sendiri, **tanpa emoji** di UI.
- Satu momen animasi terarah (transisi login ke rumah). Sisanya diam. Motion yang menjawab aksi murid (benar, salah, hadiah) boleh. `prefers-reduced-motion` dihormati.
- Area klik >= 56px, fokus keyboard terlihat jelas, kontras teks >= 4.5:1, `aria-label` pada ikon tanpa teks, semua game bisa dioperasikan keyboard. Tata letak landscape laptop, tetap rapi sampai lebar 1024px dan tidak pecah di 768px.
- Suara: efek ringan dan tombol bisu yang selalu terlihat. Audio baru diputar setelah interaksi pertama. SpeechSynthesis tidak dipakai kecuali di-flag mati secara default.
- Perasaan: suportif. Tidak ada kata atau gambar yang menakut-nakuti, tidak ada "game over", tidak ada penalti koin, tidak ada iklan, tidak ada pembelian.

## A15. Konten draft (aturan)

Semua konten buatan Anda adalah **placeholder** dengan `"status": "DRAFT"` dan harus tampil di `README_KONTEN.md` sebagai daftar yang wajib divalidasi dokter gigi. Hanya pakai fakta umum berikut (jangan menambah fakta klinis lain; semuanya tetap harus divalidasi):

- Sikat gigi 2 kali sehari: sesudah sarapan dan sebelum tidur; sekitar 2 menit.
- Pasta gigi berfluorida, sebesar biji kacang polong untuk anak.
- Ganti sikat gigi tiap sekitar 3 bulan atau saat bulu sikat mengembang.
- Plak adalah lapisan bakteri; bakteri mengubah gula menjadi asam yang merusak lapisan luar gigi.
- Makanan manis dan lengket serta minuman manis menambah risiko gigi berlubang; air putih, sayur, buah segar berserat, susu, dan keju lebih baik.
- Berkumur dengan air putih sesudah makan.
- Periksa ke dokter gigi sekitar 6 bulan sekali.

Tujuan pembelajaran (TP): TP1 kapan dan seberapa sering menyikat gigi; TP2 langkah menyikat gigi yang benar; TP3 makanan dan minuman baik vs merusak gigi; TP4 penyebab gigi berlubang (plak, gula, bakteri); TP5 pentingnya periksa ke dokter gigi. Setiap butir tes, tantangan video, dan game ditandai dengan TP-nya.

Tingkat bahasa konten: SD kelas 3-5 (`grade_range`), kalimat pendek. Tulis `daily_messages.json` berisi 20 pesan harian pendek.

## A16. Keamanan dan privasi

- **Tidak ada data pribadi murid** di database: tanpa nama, email, foto, IP, atau nomor telepon. `login_logs` hanya `student_id` dan waktu.
- PIN di-hash (bcrypt/argon). Batas gagal login dan kunci sementara sesuai config. CSRF aktif, cookie session `HttpOnly` dan `SameSite=Lax`, `Secure` di produksi.
- Policy otorisasi untuk guru vs peneliti, diuji. Ekspor CSV hanya untuk yang berhak.
- Skor dan progres disimpan **di server**, bukan localStorage.
- Tanpa analytics, tanpa font/skrip/gambar dari luar. Cache header untuk aset statis, gzip aktif (catat di README untuk hosting).

## A17. Performa (laptop sekolah dan jaringan bersama)

Aset kecil: gambar SVG, tanpa library berat. Kode game dimuat lazy (dynamic import). Maskot: hanya satu instance beranimasi per layar, animasi hanya `transform` dan `opacity`, instance kecil statis (Lampiran A.8). Target ukuran JS awal murid < ~150 KB gzip. Video 480p ringan. Hindari memuat semua video sekaligus. Chart.js hanya di halaman admin.

## A18. Pengujian dan seed

Feature test (Pest atau PHPUnit) minimal untuk: login benar/salah dan penguncian; guru tidak bisa melihat murid sekolah lain; pre-test hanya sekali dan terkunci; tes bisa dilanjutkan setelah refresh; kunci jawaban tidak bocor di response; post-test terkunci sampai syarat unlock terpenuhi atau dibuka guru; perhitungan peluruhan (batas floor dan max drop); batas koin harian; pembelian toko (koin cukup/kurang, tidak bisa beli dua kali); klaim pesan harian sekali per hari; ekspor CSV berformat benar.

Seeder: 1 peneliti, 1 guru, 1 sekolah (`S1`), 1 kelas, 10 murid demo (`DEMO-01` sampai `DEMO-10`, PIN `1234`, **hanya di environment lokal**), plus semua konten dari JSON.

## A19. Larangan

Jangan: memakai nama asli atau email murid; menambah analytics atau CDN; menyimpan skor di localStorage; membuat hewan "mati" atau mekanik rasa bersalah; menampilkan benar/salah selama tes; memakai soal tes sebagai soal kuis; mengarang fakta klinis di luar daftar A15; menandai konten draft sebagai final; mengubah bentuk, proporsi, atau palet maskot di luar Lampiran A tanpa dicatat di `DECISIONS.md`; memakai aset gambar maskot dari luar; memberi atribut `transform` pada elemen SVG yang juga dianimasikan CSS `transform`.

---

## Lampiran A: Spesifikasi maskot "Dento" (SVG berkostum, hidup)

### A.1 Karakter

Dento adalah **gigi geraham kecil berkostum pahlawan gigi sehat**. Proporsi: badan gigi sedikit lebih tinggi dari lebarnya, dua akar menjadi kaki bersepatu, tangan bulat sederhana tanpa jari. Gaya: garis tepi biru langit tebal, sudut membulat, gradasi lembut dari putih ke biru sangat muda, tanpa bayangan keras.

Kostum (selalu terpasang dan gratis):
- **Jubah merah** yang berkibar pelan, dengan dua kancing emas di bahu.
- **Ikat kepala navy dengan cermin kepala dokter gigi** (lingkaran perak dengan lubang tengah dan kilau putih).
- **Lencana bintang emas** di dada.
- **Sepatu biru** dengan sol putih.
- **Sikat gigi ajaib** (gagang biru, kepala putih-pink, gelembung busa), hanya muncul di pose `sikat`.

Kepribadian: ceria, sabar, penyemangat, tidak pernah menghukum atau menakut-nakuti. Maskot tidak boleh berubah bentuk, proporsi, atau palet di luar spesifikasi ini tanpa dicatat di `DECISIONS.md`. Semua gambar dibuat sendiri sebagai SVG; jangan memakai aset gambar maskot dari luar.

### A.2 Konvensi gambar (penting, baca sebelum menulis kode)

- `viewBox="-30 -28 260 300"`. Koordinat badan memakai ruang 0..200 (x) dan 0..230 (y); sisanya adalah ruang untuk jubah, sikat gigi, dan bintang.
- **Jangan memberi atribut `transform` pada elemen yang juga dianimasikan dengan CSS `transform`.** CSS akan menimpa atributnya dan elemen melompat ke titik asal (bug nyata yang pernah muncul pada bintang berkilau). Bungkus dengan `<g transform="...">` dan animasikan elemen di dalamnya.
- Semua animasi memakai `transform-box: fill-box` (kelas `.m-a`) agar `transform-origin` relatif terhadap bentuk itu sendiri.
- ID gradasi harus unik per instance (`uid`), karena beberapa maskot bisa tampil di satu halaman.
- Semua warna dari objek `C`. Tidak ada teks di dalam SVG; teks selalu HTML.

### A.3 `resources/js/mascot/parts.js`

```js
export const C = {
  sky: '#4AA8D8', skyDark: '#2F7FAE', skyLight: '#D8EEF9', tooth: '#FFFDF7', navy: '#25435C',
  cheek: '#FFB3C1', gold: '#FFD65A', goldDark: '#D9A400',
  mirror: '#D9E2EA', mirrorDark: '#7F97AA', mirrorLight: '#F4FAFD',
  mouth: '#C2415D', tongue: '#FF8FA3', tear: '#8FD3F4', cavity: '#8C7B5E', plak: '#E8D77A',
};

export const CAPES = {
  merah: { fill: '#E85D5D', dark: '#B93A3A' },
  biru:  { fill: '#5B8DEF', dark: '#3A63B8' },
  hijau: { fill: '#4FBF8A', dark: '#2E8A5F' },
};

const BODY_D = 'M100 12C70 0 28 8 24 52C21 82 40 98 46 128C52 160 52 205 72 205C90 205 88 170 100 170C112 170 110 205 128 205C148 205 148 160 154 128C160 98 179 82 176 52C172 8 130 0 100 12Z';
const STAR = '100,135 103.8,144.7 114.3,145.4 106.2,152 108.8,162.1 100,156.5 91.2,162.1 93.8,152 85.7,145.4 96.2,144.7';
const SPARK_D = 'M0 -12L3 -3L12 0L3 3L0 12L-3 3L-12 0L-3 -3Z';

const line = (d, color, w) => `<path d="${d}" fill="none" stroke="${color}" stroke-width="${w}" stroke-linecap="round"/>`;

// Bintang berkilau: <g> membawa transform posisi, <path> di dalamnya yang dianimasikan.
export const sparkle = (x, y, s = 1, delay = 0) =>
  `<g transform="translate(${x} ${y}) scale(${s})"><path class="m-a m-tw" style="animation-delay:${delay}s" d="${SPARK_D}" fill="${C.gold}"/></g>`;

export const cape = (k = 'merah') => {
  const { fill, dark } = CAPES[k] ?? CAPES.merah;
  return `<g class="m-a m-cape"><path d="M40 104C14 140 0 196 18 232Q38 222 54 236Q76 224 100 238Q124 224 146 236Q162 222 182 232C200 196 186 140 160 104Z" fill="${fill}" stroke="${dark}" stroke-width="4" stroke-linejoin="round"/>` +
         `<path d="M100 238Q92 190 70 140M100 238Q108 190 130 140" fill="none" stroke="${dark}" stroke-width="3" opacity=".55" stroke-linecap="round"/></g>`;
};

export const SHOES =
  `<ellipse cx="72" cy="208" rx="21" ry="10" fill="${C.sky}" stroke="${C.skyDark}" stroke-width="3"/>` +
  `<ellipse cx="128" cy="208" rx="21" ry="10" fill="${C.sky}" stroke="${C.skyDark}" stroke-width="3"/>` +
  `<path d="M54 213Q72 220 90 213M110 213Q128 220 146 213" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round"/>`;

export const body = (uid) =>
  `<defs><linearGradient id="g${uid}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset=".7" stop-color="#F3F9FD"/><stop offset="1" stop-color="#D6E9F5"/></linearGradient></defs>` +
  `<path d="${BODY_D}" fill="url(#g${uid})" stroke="${C.sky}" stroke-width="6" stroke-linejoin="round"/>` +
  line('M44 58Q46 38 62 30M52 140Q50 160 56 178', C.skyLight, 6);

// Lapisan status kebersihan (di atas badan, di bawah kostum)
export const overlay = (kebersihan) =>
  kebersihan >= 70 ? '' :
  kebersihan >= 40 ? `<ellipse cx="100" cy="44" rx="52" ry="22" fill="${C.plak}" opacity=".35"/>` :
  `<ellipse cx="132" cy="40" rx="13" ry="9" fill="${C.cavity}" opacity=".85"/><circle cx="116" cy="46" r="4" fill="${C.cavity}" opacity=".85"/>`;

export const CLASPS =
  `<circle cx="38" cy="110" r="6" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2"/>` +
  `<circle cx="162" cy="110" r="6" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2"/>`;

export const BADGE = `<polygon points="${STAR}" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2.5" stroke-linejoin="round"/>`;

export const HEADGEAR =
  line('M27 52Q100 10 173 52', C.navy, 7) +
  `<circle cx="100" cy="28" r="16" fill="${C.mirror}" stroke="${C.mirrorDark}" stroke-width="4"/>` +
  `<circle cx="100" cy="28" r="10" fill="${C.mirrorLight}"/>` +
  line('M93 24Q96 19 102 20', '#fff', 3) +
  `<circle cx="100" cy="28" r="3" fill="${C.mirrorDark}"/>`;

// ---------- Wajah ----------
const brow = (d) => line(d, C.navy, 4.5);
const dot = (cx, cy) =>
  `<circle cx="${cx}" cy="${cy}" r="11" fill="${C.navy}"/><circle cx="${cx + 4}" cy="${cy - 5}" r="4" fill="#fff"/><circle cx="${cx - 4}" cy="${cy + 5}" r="2" fill="#fff"/>`;
const arcUp   = (x, y) => line(`M${x - 10} ${y}Q${x} ${y - 14} ${x + 10} ${y}`, C.navy, 5);
const arcDown = (x, y) => line(`M${x - 10} ${y}Q${x} ${y + 8} ${x + 10} ${y}`, C.navy, 5);
const cheeks = (y = 98, rx = 11) =>
  `<ellipse cx="58" cy="${y}" rx="${rx}" ry="7" fill="${C.cheek}"/><ellipse cx="142" cy="${y}" rx="${rx}" ry="7" fill="${C.cheek}"/>`;
const tongue = (d) => `<path d="${d}" fill="${C.tongue}"/>`;

export const MOUTH_CLOSED = line('M84 100Q100 116 116 100', C.navy, 5);

export const EXPR = {
  senang: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: dot(78, 76) + dot(122, 76), cheeks: cheeks(98),
    mouth: `<path d="M78 98Q100 128 122 98Z" fill="${C.mouth}"/>` + tongue('M90 108Q100 117 110 108Q100 103 90 108Z'),
  },
  semangat: {
    brows: brow('M66 56Q78 48 90 56M110 56Q122 48 134 56'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(98),
    mouth: `<path d="M74 94Q100 134 126 94Z" fill="${C.mouth}"/>` + tongue('M89 108Q100 118 111 108Q100 102 89 108Z'),
  },
  bingung: {
    brows: brow('M66 54L90 60M110 58L134 48'),
    eyes: dot(78, 78) + dot(122, 78), cheeks: cheeks(100),
    mouth: line('M84 106Q92 98 100 106T116 106', C.navy, 5),
  },
  sedih: {
    brows: brow('M66 66L90 56M110 56L134 66'),
    eyes: dot(78, 80) + dot(122, 80), cheeks: cheeks(102),
    mouth: line('M86 114Q100 100 114 114', C.navy, 5),
    extra: `<path d="M68 92Q63 102 68 106Q73 102 68 92Z" fill="${C.tear}"/>`,
  },
  makan: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(100, 13),
    mouth: `<g class="m-a m-chew"><ellipse cx="100" cy="108" rx="13" ry="10" fill="${C.mouth}"/><ellipse cx="100" cy="112" rx="7" ry="4" fill="${C.tongue}"/></g>`,
  },
  tidur: {
    brows: '',
    eyes: arcDown(78, 78) + arcDown(122, 78), cheeks: cheeks(98),
    mouth: line('M92 106Q100 111 108 106', C.navy, 4),
    extra: `<path class="m-zz" d="M150 40h14l-14 14h14M170 22h10l-10 10h10" fill="none" stroke="${C.navy}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>`,
  },
  bersih: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(98),
    mouth: line('M82 98Q100 120 118 98', C.navy, 5),
    extra: sparkle(152, 58, .7, 0) + sparkle(50, 46, .6, .5) + sparkle(160, 126, .5, 1),
  },
};

// ---------- Pose tangan ----------
const armPath = (d) => line(d, C.sky, 14) + line(d, C.tooth, 8);
const hand = (x, y) => `<circle cx="${x}" cy="${y}" r="9" fill="${C.tooth}" stroke="${C.sky}" stroke-width="4"/>`;
const arm = (d, x, y) => armPath(d) + hand(x, y);

const L_DOWN = arm('M44 124Q30 150 36 168', 37, 172);
const R_DOWN = arm('M156 124Q170 150 164 168', 163, 172);
const L_WAVE = `<g class="m-a m-wave">${arm('M44 124Q22 120 12 98', 11, 93)}` +
  line('M-6 80Q-13 90 -6 100M-14 76Q-23 90 -14 104', C.sky, 3).replace('<path ', '<path opacity=".6" ') + `</g>`;

const BRUSH =
  line('M188 98L208 42', C.navy, 12) + line('M188 98L208 42', C.sky, 7) +
  `<g transform="translate(210 36) rotate(20)"><rect x="-9" y="-30" width="18" height="30" rx="6" fill="${C.tooth}" stroke="${C.navy}" stroke-width="3"/>` +
  `<rect x="-6" y="-27" width="12" height="13" rx="3" fill="${C.cheek}"/>` +
  `<path d="M-3 -26V-16M3 -26V-16" stroke="#fff" stroke-width="2" stroke-linecap="round"/></g>` +
  `<circle cx="224" cy="22" r="6" fill="#fff" stroke="${C.tear}" stroke-width="2"/>` +
  `<circle cx="216" cy="6" r="4" fill="#fff" stroke="${C.tear}" stroke-width="2"/>` +
  `<circle cx="230" cy="42" r="4" fill="#fff" stroke="${C.tear}" stroke-width="2"/>`;

export const POSES = {
  turun:  { l: L_DOWN, r: R_DOWN },
  lambai: { l: L_WAVE, r: R_DOWN },
  atas:   { l: arm('M44 124Q22 106 20 80', 20, 75), r: arm('M156 124Q178 106 180 80', 180, 75) },
  tunjuk: { l: L_DOWN, r: arm('M156 124Q180 108 178 74', 178, 69) },
  sikat:  { l: L_WAVE, r: armPath('M156 124Q176 122 186 102') + BRUSH + hand(188, 98) },
};

export const SPARKLES_HERO = sparkle(4, 30, 1, 0) + sparkle(196, -6, .8, .6) + sparkle(-4, 176, .7, 1.1);
```

### A.4 `resources/js/mascot/mascot.js`

```js
import { C, CAPES, EXPR, POSES, MOUTH_CLOSED, SPARKLES_HERO, cape, SHOES, body, overlay, CLASPS, BADGE, HEADGEAR } from './parts.js';
import { ACCESSORIES } from './accessories.js';   // topi, kacamata (SVG buatan sendiri, lihat A.7)
import './mascot.css';

const LABEL = { senang: 'senang', semangat: 'bersemangat', bingung: 'bingung', sedih: 'sedih', makan: 'sedang makan', tidur: 'tidur', bersih: 'bersih berkilau' };
const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
let uid = 0;

export function mascotSVG(state = {}, { animated = true, size = 400 } = {}) {
  const { expression = 'senang', pose = 'sikat', kebersihan = 100, equipped = {} } = state;
  const e = EXPR[expression] ?? EXPR.senang;
  const p = POSES[pose] ?? POSES.turun;
  const id = ++uid;
  const hat = equipped.topi ? (ACCESSORIES.topi[equipped.topi] ?? HEADGEAR) : HEADGEAR;
  const glasses = equipped.kacamata ? (ACCESSORIES.kacamata[equipped.kacamata] ?? '') : '';
  return `<svg class="mascot${animated ? '' : ' is-static'}" viewBox="-30 -28 260 300" width="${size}" height="${Math.round(size * 300 / 260)}" role="img" aria-label="Dento, pahlawan gigi sehat, ${LABEL[expression] ?? 'senang'}">` +
    `<ellipse cx="100" cy="236" rx="62" ry="8" fill="${C.navy}" opacity=".12"/>` +
    `<g class="m-a m-react"><g class="m-a m-bob">` +
      cape(equipped.jubah ?? 'merah') + SHOES + body(id) + overlay(kebersihan) + CLASPS + BADGE + hat +
      (e.brows ?? '') +
      `<g class="m-look"><g class="m-a m-blink">${e.eyes}</g></g>` + glasses +
      e.cheeks + `<g class="m-mouth">${e.mouth}</g>` + (e.extra ?? '') +
      p.l + p.r +
    `</g></g>` +
    (animated && expression !== 'bersih' ? SPARKLES_HERO : '') +
    `</svg>`;
}

// Komponen hidup. host = elemen kontainer. Hanya SATU instance beranimasi per layar; instance kecil pakai animated:false.
export function createMascot(host, initial = {}, { animated = true, size = 400, track = true, sleepAfter = 0 } = {}) {
  let state = { expression: 'senang', pose: 'sikat', kebersihan: 100, equipped: {}, ...initial };
  let last = '', speakTimer = null, idleTimer = null, raf = 0;

  const render = () => {
    const key = JSON.stringify(state);
    if (key === last) return;           // jangan render ulang jika tidak berubah (animasi tidak restart)
    last = key;
    host.innerHTML = mascotSVG(state, { animated, size });
  };

  const set = (patch) => { state = { ...state, ...patch }; render(); };

  // Reaksi sesaat: 'jump' (jawaban benar), 'wobble' (salah, lembut), 'pop' (hadiah)
  const react = (type) => {
    const g = host.querySelector('.m-react'); if (!g || reduced()) return;
    g.classList.remove('react-jump', 'react-wobble', 'react-pop');
    void g.getBoundingClientRect();
    g.classList.add(`react-${type}`);
    g.addEventListener('animationend', () => g.classList.remove(`react-${type}`), { once: true });
  };

  // Berbicara: mulut membuka-menutup selama teks diketik atau audio diputar
  const speak = (on) => {
    clearInterval(speakTimer);
    const mouth = () => host.querySelector('.m-mouth');
    if (!on) { if (mouth()) mouth().innerHTML = EXPR[state.expression].mouth; return; }
    let open = true;
    speakTimer = setInterval(() => {
      if (mouth()) mouth().innerHTML = open ? EXPR.senang.mouth : MOUTH_CLOSED;
      open = !open;
    }, 170);
  };

  // Mata mengikuti kursor (maks 3 unit), dibatasi per frame
  const onMove = (ev) => {
    if (raf || reduced()) return;
    raf = requestAnimationFrame(() => {
      raf = 0;
      const look = host.querySelector('.m-look'); if (!look) return;
      const r = host.getBoundingClientRect();
      const dx = ev.clientX - (r.left + r.width / 2), dy = ev.clientY - (r.top + r.height * 0.3);
      const d = Math.hypot(dx, dy) || 1, k = Math.min(3, d / 60);
      look.style.transform = `translate(${(dx / d) * k}px, ${(dy / d) * k}px)`;
    });
  };

  // Tidur otomatis jika lama tidak ada interaksi (hanya di hub), bangun saat disentuh
  const resetIdle = () => {
    if (!sleepAfter) return;
    clearTimeout(idleTimer);
    if (state._autoSleep) set({ expression: 'senang', pose: 'sikat', _autoSleep: false });
    idleTimer = setTimeout(() => set({ expression: 'tidur', pose: 'turun', _autoSleep: true }), sleepAfter);
  };

  // Klik pada maskot: tertawa kecil
  const onClick = () => { const prev = { expression: state.expression, pose: state.pose }; set({ expression: 'semangat', pose: 'atas' }); react('jump'); setTimeout(() => set(prev), 900); };

  render();
  if (animated && track) addEventListener('pointermove', onMove, { passive: true });
  if (sleepAfter) { ['pointerdown', 'keydown'].forEach((t) => addEventListener(t, resetIdle)); resetIdle(); }
  host.addEventListener('click', onClick);

  return {
    set, react, speak,
    destroy() {
      clearInterval(speakTimer); clearTimeout(idleTimer); cancelAnimationFrame(raf);
      removeEventListener('pointermove', onMove);
      ['pointerdown', 'keydown'].forEach((t) => removeEventListener(t, resetIdle));
      host.removeEventListener('click', onClick); host.innerHTML = '';
    },
  };
}
```

### A.5 `resources/js/mascot/mascot.css`

```css
.mascot { display: block; overflow: visible; }
.m-a { transform-box: fill-box; transform-origin: center; }
@keyframes bob   { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-6px) } }
@keyframes blink { 0%,92%,100% { transform: scaleY(1) } 96% { transform: scaleY(.08) } }
@keyframes flap  { 0%,100% { transform: rotate(-2deg) } 50% { transform: rotate(3deg) } }
@keyframes wave  { 0%,100% { transform: rotate(0) } 50% { transform: rotate(-14deg) } }
@keyframes tw    { 0%,100% { opacity: .35; transform: scale(.7) } 50% { opacity: 1; transform: scale(1.1) } }
@keyframes chew  { 0%,100% { transform: scaleY(1) } 50% { transform: scaleY(.7) } }
@keyframes zz    { 0% { opacity: 0; transform: translate(0,0) } 50% { opacity: .8 } 100% { opacity: 0; transform: translate(8px,-10px) } }
@keyframes jump  { 0% { transform: translateY(0) scale(1,1) } 25% { transform: translateY(0) scale(1.06,.92) } 55% { transform: translateY(-26px) scale(.96,1.05) } 100% { transform: translateY(0) scale(1,1) } }
@keyframes wobble{ 0%,100% { transform: rotate(0) } 25% { transform: rotate(-4deg) } 75% { transform: rotate(4deg) } }
@keyframes pop   { 0%,100% { transform: scale(1) } 50% { transform: scale(1.12) } }

.m-bob   { animation: bob 2.6s ease-in-out infinite; }
.m-blink { animation: blink 4.5s infinite; }
.m-cape  { transform-origin: 50% 0; animation: flap 2.2s ease-in-out infinite; }
.m-wave  { transform-origin: 100% 100%; animation: wave 1.3s ease-in-out infinite; }
.m-tw    { animation: tw 1.8s ease-in-out infinite; }
.m-chew  { transform-origin: 50% 0; animation: chew .4s ease-in-out infinite; }
.m-zz    { animation: zz 2.4s ease-in-out infinite; }
.m-look  { transition: transform .12s ease-out; }
.react-jump   { transform-origin: 50% 100%; animation: jump .6s ease-out; }
.react-wobble { transform-origin: 50% 100%; animation: wobble .5s ease-in-out; }
.react-pop    { animation: pop .35s ease-out; }

.is-static *, .is-static { animation: none !important; }
@media (prefers-reduced-motion: reduce) { .mascot * { animation: none !important; transition: none !important; } }
```

### A.6 Pose dan ekspresi yang tersedia

- Ekspresi: `senang`, `semangat`, `bingung`, `sedih` (lembut, bukan menakutkan), `makan` (mengunyah), `tidur` (zzz), `bersih` (bintang berkilau).
- Pose tangan: `turun`, `lambai` (tangan kiri melambai terus), `atas` (kedua tangan terangkat, perayaan), `tunjuk` (tangan kanan terangkat, memberi petunjuk), `sikat` (melambai dan memegang sikat gigi ajaib; pose default hub).
- Status kebersihan (otomatis dari `kebersihan`): >= 70 normal; 40-69 plak kekuningan tipis; < 40 bercak kecil. Jika < 40 dan tidak ada aktivitas yang sedang tampil, paksa `expression: 'sedih'` dan `pose: 'turun'`.

### A.7 Aksesori toko dan jangkar

File `resources/js/mascot/accessories.js` mengekspor `ACCESSORIES = { topi: {...}, kacamata: {...} }` berisi string SVG buatan sendiri, memakai warna dari `C` dan koordinat badan A.2:

- **Topi** (slot `topi`, **menggantikan** ikat kepala dan cermin kepala): jangkar dasar topi di y = 20, lebar sekitar 110 (x 45 sampai 155), tinggi sekitar 40, menutupi dip di (100, 12).
  - `dokter`: topi putih dengan pita biru langit dan tanda plus kecil merah.
  - `merah`: topi merah dengan bintang emas kecil di depan.
- **Kacamata** (slot `kacamata`): pusat lensa di (78, 76) dan (122, 76), jembatan di y = 76, garis navy tebal 4, isi lensa putih transparan 25% agar mata tetap terlihat. Digambar **setelah** mata.
  - `bulat`: lensa lingkaran r = 15.
  - `bintang`: lensa berbentuk bintang emas r = 16.
- **Jubah** (slot `jubah`): hanya mengganti warna lewat `CAPES` (`merah` gratis, `biru`, `hijau`).
- **Latar** (slot `latar`): SVG terpisah di belakang karakter, `viewBox="0 0 800 500"`, `preserveAspectRatio="xMidYMid slice"`. Default `ruangan` (dinding `#E8F6FB`, lantai biru muda, jendela, rak kecil), plus `taman` (rumput, pohon, awan) dan `langit` (gradasi langit, awan, bintang).

### A.8 Ukuran dan kinerja

- **Hero (hub)**: 360 sampai 420px, beranimasi penuh, pose `sikat`.
- **Pemandu di layar tes, video, dan game**: 120px, beranimasi, tanpa pelacakan mata (`track: false`).
- **Avatar kecil dan pratinjau toko**: 64 sampai 160px, `animated: false`.
- Hanya satu maskot beranimasi per layar. Animasi hanya `transform` dan `opacity`. Pelacakan mata memakai `requestAnimationFrame` dengan batas satu per frame. Instance dihancurkan dengan `destroy()` saat layar ditinggalkan.

### A.9 Galeri uji maskot (wajib)

Buat rute `/dev/mascot` yang **hanya aktif jika `APP_ENV=local`**: grid semua kombinasi ekspresi x pose (statis, kecil) dan satu hero beranimasi dengan kontrol: pilihan ekspresi, pilihan pose, slider kebersihan 0 sampai 100, pilihan topi/kacamata/jubah, tombol reaksi (`jump`, `wobble`, `pop`), tombol bicara, dan saklar animasi. Semua kontrol bisa dipakai keyboard.

Kriteria lulus galeri: tidak ada galat di konsol; ID gradasi unik; bintang berkilau tetap di posisinya (tidak menumpuk di satu titik); lambaian tangan berputar di bahu, bukan di tempat lain; jubah berkibar dari leher; cermin kepala berada di tengah ikat kepala; mata tetap terlihat dengan kacamata; dengan `prefers-reduced-motion` semua animasi diam; tampilan statis sama bentuknya dengan yang beranimasi.

---

## Lampiran B: Pemetaan situasi ke ekspresi, pose, dan animasi

Aturan umum: maskot hanya bereaksi sesaat lalu kembali ke keadaan dasar. Tidak ada reaksi yang menghukum.

- **Login:** `senang`, `lambai`.
- **Onboarding dan cerita pembuka:** `senang` + `lambai`, `semangat` + `atas` pada kalimat gembira; bicara (`speak`) selama teks muncul.
- **Pre-test dan post-test:** `senang` + `turun`, **tanpa reaksi apa pun terhadap benar/salah**. Maskot hanya bicara di layar pembuka ("Ini bukan ujian, jawab sesuai yang kamu tahu ya") dan layar penutup (`semangat` + `atas`, `react('pop')`, tanpa membocorkan skor).
- **Hub, kebersihan >= 70:** `senang` + `sikat`. **40-69:** `senang` + `turun` dengan lapisan plak. **< 40:** `sedih` + `turun` dengan bercak, plus gelembung bicara yang mengajak ("Yuk sikat gigiku").
- **Pilah Makanan, jawaban benar:** `semangat` + `atas` + `react('jump')`. **Salah:** `bingung` + `tunjuk` + `react('wobble')` ringan, dengan alasan singkat di gelembung.
- **Urutan Sikat:** `senang` + `tunjuk` saat memberi petunjuk; `semangat` + `atas` saat urutan benar.
- **Sikat Bersih:** `senang` + `sikat` selama bermain; selesai: `bersih` + `atas` + `react('pop')`.
- **Memory Fakta dan Kuis Kilat:** `senang` + `turun`; benar: `semangat` + `atas`; salah: `bingung` + `tunjuk`.
- **Video:** sebelum: `senang` + `tunjuk` (mengantar); sesudah: `semangat` + `atas`; tantangan video mengikuti aturan kuis.
- **Hadiah, naik level, lencana:** `semangat` + `atas` + `react('jump')` lalu `react('pop')`, bintang berkilau.
- **Toko:** `senang` + `lambai`; saat aksesori dipasang: `semangat` + `react('pop')`.
- **Pesan harian:** `senang` + `tunjuk`.
- **Makan (menu Makan):** `makan` selama makanan sehat dimakan; makanan merusak tidak dimakan oleh Dento, tetapi dijelaskan lewat `bingung`.
- **Tidak ada interaksi 90 detik di hub:** `tidur` + `turun`; bangun saat disentuh atau ditekan tombol.
- **Klik pada maskot (di luar tes):** `semangat` + `atas` + `react('jump')` selama sekitar satu detik, lalu kembali.
