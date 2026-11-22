# BAGIAN A: ADDENDUM SPEC

## A1. Ringkasan perubahan

Dua tambahan besar pada sistem Dento yang sudah jadi:

1. **Jurnal Gigi (habit tracker):** murid melaporkan kebiasaan giginya (sudah sikat gigi atau belum, makan/minum manis, berkumur, minum air putih) lewat check-in harian bersama Dento. Hasilnya menjadi **indikator perilaku** untuk penelitian (melengkapi skor pengetahuan pre/post-test), sekaligus memicu rekomendasi konten yang relevan.
2. **Penempatan video** buatan Clara: video dibagi menjadi video inti (sudah ada), video singkat kontekstual, video pembuka, dan video penutup, masing-masing dengan tempat dan pemicu yang jelas.

**Yang TIDAK dilakukan sistem:** sistem **tidak mendeteksi** kebiasaan. Semua data adalah **laporan diri murid** (self-report). Jangan memakai kata "deteksi", "pantau otomatis", atau "terverifikasi" di UI, README, atau dokumentasi mana pun. Gunakan "laporan", "cerita", "jurnal".

## A2. Prinsip desain (wajib, menentukan kualitas data)

1. **Hadiah untuk MELAPOR, bukan untuk jawaban "baik".** Koin, XP, dan kebahagiaan diberikan sama persis apa pun isi jawabannya. Kalau tidak, murid terdorong menjawab "sudah" supaya dapat hadiah, dan datanya rusak.
2. **Status hewan TIDAK terhubung ke jawaban jurnal.** `kebersihan` dan `kebahagiaan` Dento hanya berubah karena aktivitas di dalam game (A6 di SPEC lama), tidak pernah karena isi laporan kebiasaan.
3. **Tanggapan Dento selalu suportif dan netral.** Untuk jawaban "belum" atau "makan manis banyak", Dento tidak boleh sedih, bingung, atau menyalahkan. Ia berterima kasih sudah jujur, lalu (opsional) menawarkan satu hal bermanfaat.
4. **Tidak ada perbandingan antar murid:** tanpa papan peringkat, tanpa menampilkan data murid lain, tanpa warna merah/hijau penilaian. Ringkasan murid hanya tentang dirinya sendiri, dengan warna merek yang netral.
5. **Tidak ada teks bebas** pada jurnal (hanya pilihan), agar tidak ada data pribadi yang masuk. Tidak ada foto, tidak ada sensor, tidak ada notifikasi paksa.
6. Kalimat pembuka check-in: "Ceritakan dengan jujur ya, tidak ada jawaban yang salah."
7. **Data kebiasaan adalah data kesehatan anak** (meski hanya laporan diri dan memakai kode peserta). Pastikan hanya dapat dilihat oleh peran yang berhak (A11) dan masuk daftar hal yang perlu persetujuan orang tua di `README_KONTEN.md`/README.

## A3. Definisi kebiasaan (`database/content/habits.json`)

Seluruh pertanyaan **dibaca dari JSON** (bukan hardcode) supaya Clara bisa mengubahnya tanpa menyentuh kode. Skema:

```json
{
  "status": "DRAFT",
  "items": [
    {
      "kode": "sikat_pagi", "urutan": 1, "aktif": true, "tp": "TP1",
      "teks": "Tadi pagi, setelah sarapan, kamu sikat gigi?",
      "acuan": "hari_ini",
      "opsi": [
        {"nilai": "sudah", "label": "Sudah", "num": 1, "tanggapan": "Terima kasih sudah cerita!"},
        {"nilai": "belum", "label": "Belum", "num": 0, "tanggapan": "Tidak apa-apa. Terima kasih sudah jujur."},
        {"nilai": "tidak_ingat", "label": "Lupa", "num": null, "tanggapan": "Tidak apa-apa, kadang kita lupa."}
      ]
    },
    {
      "kode": "sikat_malam", "urutan": 2, "aktif": true, "tp": "TP1",
      "teks": "Semalam, sebelum tidur, kamu sikat gigi?",
      "acuan": "kemarin_malam",
      "opsi": [ "...sama seperti sikat_pagi..." ]
    },
    {
      "kode": "manis", "urutan": 3, "aktif": true, "tp": "TP3",
      "teks": "Sejak kemarin sampai sekarang, berapa kali kamu makan atau minum yang manis? Misalnya permen, cokelat, es, atau minuman manis.",
      "acuan": "24_jam",
      "opsi": [
        {"nilai": "0", "label": "Tidak ada", "num": 0},
        {"nilai": "1", "label": "1 kali", "num": 1},
        {"nilai": "2", "label": "2 kali", "num": 2},
        {"nilai": "3_plus", "label": "3 kali atau lebih", "num": 3}
      ]
    },
    {
      "kode": "kumur", "urutan": 4, "aktif": true, "tp": "TP1",
      "teks": "Setelah makan atau minum manis, kamu berkumur dengan air putih?",
      "acuan": "24_jam",
      "tampil_jika": {"item": "manis", "nilai_in": ["1", "2", "3_plus"]},
      "opsi": [
        {"nilai": "ya", "label": "Ya", "num": 1},
        {"nilai": "kadang", "label": "Kadang-kadang", "num": 0.5},
        {"nilai": "belum", "label": "Belum", "num": 0}
      ]
    },
    {
      "kode": "air_putih", "urutan": 5, "aktif": true, "tp": "TP3",
      "teks": "Hari ini kamu minum air putih?",
      "acuan": "hari_ini",
      "opsi": [ "sudah / belum / tidak_ingat, seperti sikat_pagi" ]
    }
  ]
}
```

Aturan skema:
- `tampil_jika` opsional: item hanya ditanyakan jika jawaban item lain memenuhi syarat. Jika tidak ditanyakan, simpan `nilai = "tidak_berlaku"`, `num = null`.
- `aktif: false` menyembunyikan item tanpa menghapus datanya.
- `tanggapan` per opsi opsional; bila kosong, pakai tanggapan netral umum dari `habit_tips.json`.
- Jangan membuat item baru di luar daftar ini kecuali ditambahkan lewat JSON.

**Periode acuan (penting untuk analisis, simpan di ekspor):**
- `hari_ini`: tanggal laporan itu sendiri (misalnya tadi pagi).
- `kemarin_malam`: malam sebelum tanggal laporan (tanggal laporan dikurangi 1).
- `24_jam`: kira-kira 24 jam terakhir sampai saat melapor.

Kalimat pertanyaan sengaja berbentuk **mengingat kembali** ("tadi pagi", "semalam") supaya valid dilaporkan kapan pun murid membuka sistem (di sekolah pada jam belajar atau di rumah).

## A4. Alur murid

1. **Syarat:** murid sudah menyelesaikan pre-test. Jurnal tidak tersedia sebelum itu.
2. **Pemicu harian:** pada login pertama setiap hari (zona waktu `Asia/Jakarta`), jika belum ada check-in `selesai` hari itu dan `habit.prompt_on_first_login_of_day = true`, Dento menyapa di hub dengan gelembung: "Yuk cerita tentang gigimu hari ini" dan dua tombol: **"Ayo cerita"** dan **"Nanti dulu"**. "Nanti dulu" mencatat `dilewati_count + 1` untuk hari itu dan tidak mengganggu lagi pada sesi yang sama. Tombol dok **Jurnal** selalu tersedia dan menampilkan titik penanda selama check-in hari ini belum selesai.
3. **Check-in:** satu pertanyaan per layar, urut `urutan` dari `habits.json`, kartu jawaban besar (area klik >= 56px) dengan ikon SVG buatan sendiri (tanpa emoji), titik kemajuan di atas, Dento di sisi dengan gelembung bicara (teks pertanyaan, tombol "dengarkan" jika audio ada). Setiap jawaban **disimpan saat itu juga** (autosave). Jika halaman di-refresh, lanjut dari pertanyaan yang belum dijawab. Tidak ada tombol "kembali" untuk mengubah jawaban yang sudah dikirim (menjaga konsistensi data); koreksi hanya lewat guru (A11).
4. **Tanggapan:** setelah tiap jawaban, Dento menampilkan tanggapan singkat dari opsi yang dipilih selama sekitar 1,5 detik lalu lanjut otomatis (bisa dilewati dengan klik).
5. **Selesai:** layar terima kasih, hadiah (A5), dan **maksimal satu rekomendasi** (A6) yang boleh diterima atau ditolak ("Nanti saja").
6. **Ringkasan Jurnal** (layar terpisah dari dok **Jurnal**): kalender dua minggu terakhir yang hanya menandai hari **sudah melapor**, ditambah ringkasan 7 hari terakhir tentang dirinya sendiri dengan kalimat netral dan satu warna merek, misalnya "Sikat gigi malam: 4 dari 5 hari yang kamu ceritakan". Tidak ada tanda silang, merah, atau kata "gagal".

Satu check-in per tanggal per murid. Status: `draft` (sebagian terjawab), `selesai`, `dilewati` (hanya tombol "Nanti dulu" yang ditekan; masih bisa diisi kemudian di hari yang sama). Hari tanpa check-in = data hilang (missing), bukan nol.

## A5. Hadiah dan "runtun lapor"

- Hadiah selesai check-in (sekali per hari, **apa pun jawabannya**): `habit.reward` = koin 8, XP 10, kebahagiaan +5 (nilai dari config). Tidak masuk hitungan `coin_cap_per_activity_per_day` aktivitas lain; ini aktivitas tersendiri.
- **Runtun lapor (streak):** jumlah hari sekolah berturut-turut dengan check-in `selesai`. Hanya menghitung Senin sampai Jumat (`school_days_only = true`), dengan toleransi `streak_grace_days = 1` hari terlewat sebelum runtun putus. Runtun adalah tentang **konsistensi melapor**, bukan tentang kebiasaan baik. Tampilkan di Ringkasan Jurnal dengan bahasa yang tidak menekan ("Kamu sudah cerita 4 hari berturut-turut"). Tidak ada hukuman, tidak ada animasi sedih saat putus.
- Lencana opsional (SVG buatan sendiri, tanpa koin): "Pencerita" setelah 3 check-in, 7 check-in, dan 14 check-in selesai.

## A6. Rekomendasi adaptif (satu per hari, opsional)

Setelah check-in selesai, sistem memilih **maksimal satu** rekomendasi dari `database/content/habit_rules.json` (aturan dievaluasi berdasarkan `prioritas` naik, ambil yang pertama cocok):

```json
{
  "status": "DRAFT",
  "rules": [
    {"prioritas": 1, "jika": {"item": "sikat_malam", "nilai": "belum"},
     "rekomendasi": {"tipe": "video", "target": "M1-sikat-malam"}, "tip": "tip_sikat_malam"},
    {"prioritas": 2, "jika": {"item": "sikat_pagi", "nilai": "belum"},
     "rekomendasi": {"tipe": "video", "target": "M2-sikat-pagi"}, "tip": "tip_sikat_pagi"},
    {"prioritas": 3, "jika": {"item": "manis", "nilai": "3_plus"},
     "rekomendasi": {"tipe": "game", "target": "pilah-makanan"}, "tip": "tip_manis"},
    {"prioritas": 4, "jika": {"item": "kumur", "nilai_in": ["kadang", "belum"]},
     "rekomendasi": {"tipe": "video", "target": "M4-kumur"}, "tip": "tip_kumur"}
  ]
}
```

- `habit_tips.json` berisi kalimat tip singkat (id, teks, `status: "DRAFT"`) yang hanya boleh memakai fakta whitelist A15 di SPEC lama (sikat gigi sesudah sarapan dan sebelum tidur, berkumur air putih sesudah makan, manis dan lengket menambah risiko berlubang, air putih lebih baik). Nada tip: ajakan, bukan teguran.
- Jika tidak ada aturan yang cocok: tidak ada rekomendasi (jangan mengarang).
- Setiap rekomendasi dicatat di `habit_recommendations` beserta `shown_at`, `accepted_at`, dan `completed_at` (diisi saat video selesai atau game selesai). Menolak rekomendasi tidak menyebabkan apa pun.

## A7. Video: penempatan dan pemicu

`database/content/videos.json` diperluas. **Pertahankan `id`, `file`, dan data video inti yang sudah ada**; hanya tambahkan field baru dan entri baru. Field baru:

- `tipe`: `inti` | `mikro` | `pembuka` | `penutup`
- `pemicu`: objek (lihat tabel di bawah)
- `tantangan`: hanya untuk `inti` (tetap seperti sekarang). **Video mikro, pembuka, dan penutup tidak punya tantangan.**
- `kreator`: opsional, teks tampilan (boleh kosong).

Daftar slot dan tempatnya:

- **`V0-pembuka`** (30-45 detik). Perkenalan singkat. Pemicu: **sekali**, tepat **setelah pre-test selesai**, sebelum hub pertama kali tampil. **Jangan pernah ditampilkan sebelum pre-test** (menjaga validitas pengukuran awal). Boleh dilewati dengan tombol "Lewati" setelah 5 detik; tetap dicatat.
- **`V1` sampai `V4` (inti, sudah ada)**: tetap di layar **Kelas** → tab **Pelajaran**, terbuka bertahap, dengan tantangan dan hadiah seperti SPEC lama (A8). Ini inti intervensi dan **sama untuk semua murid**.
- **`M1-sikat-malam`, `M2-sikat-pagi`, `M3-manis`, `M4-kumur`** (30-60 detik, mikro). Tersedia di layar **Kelas** → tab **Video singkat**, **selalu terbuka** setelah pre-test. Selain itu muncul sebagai: (a) rekomendasi setelah check-in (A6); (b) tombol "Penjelasan" di game: `pemicu: {"game": "pilah-makanan", "salah_berturut": 3}` untuk `M3-manis` dan `{"game": "urutan-sikat", "gagal_percobaan": 2}` untuk `M1-sikat-malam`, hanya sebagai tawaran, tidak otomatis memutar.
- **`VP-penutup`** (30-45 detik). Pemicu: **sekali**, setelah **post-test dan kuesioner selesai**, sebelum layar sertifikat.

Aturan:
- Video mikro memberi hadiah kecil sekali saja per video (koin 5, XP 5) setelah ditonton >= 90% (aturan penyelesaian sama dengan video inti). Pembuka dan penutup tidak memberi hadiah.
- Pemutar yang sama dipakai untuk semua tipe (tanpa seek maju, tombol putar cadangan, `playsinline`, fade, hormati `prefers-reduced-motion`, pemuatan video berikutnya hanya satu langkah).
- **Catat dari mana video dimulai:** kolom baru `video_progress.sumber` dengan nilai `kelas`, `rekomendasi`, `petunjuk_game`, `pembuka`, `penutup`. Ini penting karena murid yang diarahkan rekomendasi mendapat paparan video mikro berbeda dari yang tidak. **Paparan itu harus bisa dihitung di analisis** (ekspor di A11).
- Jika file video tidak ada: kartu "Video belum tersedia", tidak crash. Perbarui `scripts/make_placeholder_videos.sh` agar membuat placeholder untuk semua `id` di `videos.json` (bila ffmpeg tersedia).

**Spesifikasi produksi untuk Clara** (tulis juga ke `docs/PANDUAN_VIDEO.md`, ringkas, siap diserahkan):
- MP4, H.264 + AAC, 480p, rasio 16:9, `faststart`. Berkas: `public/media/videos/{id}.mp4`, poster 16:9: `public/media/img/{id}.jpg`. Teks (subtitle) `.vtt` dianjurkan (`public/media/videos/{id}.vtt`).
- Durasi: inti 2-3 menit, mikro 30-60 detik, pembuka dan penutup 30-45 detik. Satu pesan utama per video.
- Bahasa sederhana setingkat SD, kalimat pendek, nada ajakan, **tanpa menakut-nakuti**. Musik dan gambar bebas lisensi atau buatan sendiri; catat sumbernya di `README_KONTEN.md`.
- Setiap video dipetakan ke tujuan pembelajaran (TP1 sampai TP5) dan ke item jurnal yang relevan.
- Prioritas pembuatan: (1) V1 sampai V4, (2) M1 sampai M4, (3) V0 dan VP. Jika waktu terbatas, V0 dan VP boleh diganti kartu teks dari Dento (sistem tetap harus jalan tanpa file video).

## A8. Perubahan data (migration BARU; jangan mengubah migration lama)

- `habit_checkins`: `id`, `student_id`, `tanggal` (date, Asia/Jakarta), `status` (`draft|selesai|dilewati`), `dilewati_count` (tinyint default 0), `started_at`, `finished_at`, `durasi_detik`; unik (`student_id`, `tanggal`).
- `habit_answers`: `id`, `checkin_id`, `item_kode`, `nilai` (string), `nilai_num` (decimal nullable), `ms` (waktu menjawab), `answered_at`; unik (`checkin_id`, `item_kode`).
- `habit_recommendations`: `id`, `checkin_id`, `student_id`, `tipe` (`video|game`), `target_kode`, `alasan_item`, `shown_at`, `accepted_at` null, `completed_at` null.
- `video_progress`: tambah kolom `sumber` (string, nullable).
- `config/dento.php` (tambahan, nilai default di bawah):

```php
'habit' => [
  'enabled' => true,
  'school_days_only' => true,
  'streak_grace_days' => 1,
  'prompt_on_first_login_of_day' => true,
  'allow_skip' => true,
  'max_recommendations_per_day' => 1,
  'reward' => ['koin' => 8, 'xp' => 10, 'kebahagiaan' => 5],
  'micro_video_reward' => ['koin' => 5, 'xp' => 5],
],
'post_test_unlock' => [ /* tambahkan: */ 'min_checkins' => 3 ],
```

`min_checkins` menjadi syarat tambahan unlock otomatis post-test (jumlah check-in `selesai`). Set ke 0 untuk menonaktifkan; buka manual oleh guru tetap berlaku. Dokumentasikan di README bahwa nilai 3 mengandaikan uji coba beberapa hari.

## A9. API baru (murid, session + CSRF, rate limit)

- `GET /api/jurnal/hari-ini`: status check-in hari ini, daftar item aktif yang perlu ditanyakan (dengan `tampil_jika` dievaluasi), jawaban yang sudah ada. **Hanya tersedia jika pre-test selesai** (403 jika belum).
- `POST /api/jurnal/jawab` `{item_kode, nilai, ms}`: simpan/ubah jawaban saat status `draft` atau `dilewati`; tolak (409) jika `selesai`. Validasi `nilai` harus salah satu opsi item aktif.
- `POST /api/jurnal/lewati`: catat "Nanti dulu".
- `POST /api/jurnal/selesai`: pastikan semua item yang wajib sudah dijawab; set `selesai`, hitung `durasi_detik`, berikan hadiah **sekali per hari** (server-authoritative), hitung dan kembalikan rekomendasi (A6) atau `null`, serta tip.
- `POST /api/jurnal/rekomendasi/{id}/terima` dan `POST /api/jurnal/rekomendasi/{id}/tolak`.
- `GET /api/jurnal/ringkasan`: kalender dua minggu (hanya penanda sudah lapor), ringkasan 7 hari dirinya sendiri, runtun lapor, lencana.
- `GET /api/videos` diperluas: mengembalikan `tipe`, status buka, dan pengelompokan; `POST /api/videos/{id}/progress` dan `/complete` menerima `sumber`.

## A10. Perubahan UI

- Dok hub: tambah tombol **Jurnal** (ikon SVG buku/kalender dengan gigi, buatan sendiri). Dok sekarang 7 tombol: pastikan tata letak tetap rapi di lebar 1024px.
- Layar baru: `screens/jurnal.js` (check-in), `screens/jurnal-ringkasan.js`, komponen kartu jawaban besar.
- Layar Kelas: dua tab, **Pelajaran** (V1 sampai V4) dan **Video singkat** (M1 sampai M4).
- Alur baru: setelah pre-test selesai → `V0-pembuka` (sekali) → hub. Setelah kuesioner selesai → `VP-penutup` (sekali) → sertifikat.
- Semua mengikuti A14 di SPEC lama (palet, huruf, aksesibilitas, tanpa emoji).

## A11. Admin dan ekspor

- **Menu Kebiasaan** (peneliti: semua; guru: hanya sekolahnya, dengan Policy): ringkasan per sekolah/kelas: jumlah check-in, persen `selesai`/`dilewati`, distribusi jawaban per item, **tren mingguan** (Chart.js lokal, hanya di admin), perbandingan berpasangan **periode awal vs periode akhir** per murid (default 3 hari lapor pertama vs 3 terakhir, bisa diubah) berupa rata-rata proporsi `sudah` per item dan N pasangan lengkap. Catatan permanen di halaman: "Data ini adalah laporan diri murid, bukan observasi. Tafsirkan dengan hati-hati. Uji statistik dilakukan di luar sistem."
- **Detail murid:** tab Kebiasaan berisi garis waktu check-in dan jawabannya.
- **Buka lagi jurnal hari ini** (guru/peneliti): mengubah check-in `selesai` hari itu menjadi `draft` agar murid bisa mengoreksi; dicatat.
- **Ekspor CSV** (UTF-8 BOM, hanya kode peserta): `kebiasaan_harian.csv` (kode, tanggal_laporan, item, periode_acuan, tanggal_acuan, nilai, nilai_num, ms, status_checkin), `kebiasaan_ringkas.csv` (per murid: jumlah check-in selesai, hari dilewati, proporsi `sudah` per item, rata-rata `manis`, runtun terpanjang), `rekomendasi_kebiasaan.csv` (kode, tanggal, tipe, target, alasan_item, diterima, selesai), dan perbarui `aktivitas.csv` agar memuat `sumber` untuk video.
- Dashboard: tambah kartu "Check-in selesai hari ini" dan "Murid belum pernah lapor".

## A12. Maskot (tambahan Lampiran B)

- **Jurnal, pertanyaan:** `senang` + `tunjuk`, bicara selama teks muncul.
- **Setelah jawaban APA PUN:** `senang` + `lambai` (tanpa `sedih` atau `bingung`, bahkan untuk "belum" dan "manis banyak").
- **Check-in selesai:** `semangat` + `atas` + `react('pop')`, bintang berkilau.
- **Menawarkan rekomendasi:** `senang` + `tunjuk`.
- **Pemicu harian di hub:** `senang` + `lambai` dengan gelembung ajakan.
- **V0-pembuka dan VP-penutup:** `senang` + `lambai` saat mengantar; tanpa reaksi setelah video selesai selain `senang`.

## A13. Konten DRAFT (aturan)

Tambahkan ke `README_KONTEN.md` daftar baru yang wajib divalidasi dokter gigi dan, bila perlu, dosen pembimbing: `habits.json` (kalimat pertanyaan, opsi, periode acuan), `habit_tips.json`, `habit_rules.json` (aturan rekomendasi), naskah semua video baru, dan keputusan batas `3_plus` untuk makanan manis. Tandai semuanya `"status": "DRAFT"`. Jangan menambah fakta klinis di luar whitelist A15 SPEC lama.

## A14. Larangan tambahan

Jangan: memberi hadiah yang berbeda berdasarkan isi jawaban jurnal; menghubungkan `kebersihan`/`kebahagiaan` Dento ke jawaban jurnal; memakai ekspresi `sedih` atau `bingung` pada tanggapan jurnal; menampilkan data murid lain atau papan peringkat; membuat input teks bebas; menyimpan foto; mengirim notifikasi di luar aplikasi; memakai kata "deteksi" untuk fitur ini; menampilkan `V0-pembuka` sebelum pre-test selesai; memutar video otomatis tanpa interaksi murid; mengubah migration lama; mengubah perilaku fitur lama selain yang disebut di dokumen ini.

## A15. Pengujian (Pest/PHPUnit, tambahan)

Jurnal tidak tersedia sebelum pre-test; satu check-in per tanggal (batas tanggal Asia/Jakarta); autosave dan lanjut setelah refresh; `nilai` tidak valid ditolak; jawaban tidak bisa diubah setelah `selesai` (409) kecuali dibuka guru; `tampil_jika` bekerja (kumur hanya jika manis > 0, nilai `tidak_berlaku` tersimpan); hadiah check-in identik untuk semua kombinasi jawaban dan hanya sekali per hari; runtun lapor memakai hari sekolah dan toleransi 1 hari; rekomendasi maksimal satu, prioritas benar, tidak ada rekomendasi jika tidak ada aturan cocok; `V0-pembuka` tidak muncul sebelum pre-test dan hanya sekali; `VP-penutup` hanya setelah survei; `sumber` video tercatat; `min_checkins` memengaruhi unlock post-test; guru tidak bisa melihat atau mengekspor kebiasaan sekolah lain; CSV berformat benar dan tanpa nama; **regresi:** seluruh tes lama tetap hijau.
