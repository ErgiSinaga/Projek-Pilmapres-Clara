# README_KONTEN.md: Daftar Konten Klinis DRAFT (Wajib Validasi Pakar)

> [!IMPORTANT]
> Seluruh butir tes, naskah narasi, penjelasan mini game, dan fakta klinis di dalam repositori ini berstatus **`DRAFT` (Placeholder)**. Seluruh materi ini **WAJIB divalidasi dan disetujui oleh dokter gigi spesialis anak (pedodontis) atau dosen pembimbing** sebelum digunakan untuk pengumpulan data penelitian pre-test/post-test lapangan.

---

## 1. Pembagian Tujuan Pembelajaran (TP)
Sesuai dengan kerangka penelitian Pilmapres:
- **TP1**: Waktu dan frekuensi menyikat gigi (2 kali sehari, sesudah sarapan & sebelum tidur, durasi 2 menit).
- **TP2**: Tata cara dan langkah menyikat gigi yang tepat (teknik merata, pasta gigi fluorida sebesar biji kacang polong, penggantian sikat 3 bulan sekali).
- **TP3**: Memilah makanan dan minuman baik vs berisiko merusak gigi (kariogenik).
- **TP4**: Pemahaman proses karies/gigi berlubang (lapisan plak, bakteri, gula, dan produksi asam).
- **TP5**: Peran dokter gigi dan pentingnya kontrol rutin 6 bulan sekali.

---

## 2. Butir Instrumen Pre-Test / Post-Test (`database/content/test_items.json`)

| No | TP | Teks Pertanyaan | Kunci Jawaban Sementara | Status |
|---|---|---|---|---|
| 1 | TP1 | Berapa kali kita sebaiknya menyikat gigi dalam satu hari? | 2 kali, yaitu sesudah sarapan dan sebelum tidur | **DRAFT** |
| 2 | TP1 | Berapa lama waktu yang baik untuk menyikat seluruh bagian gigi kita? | Sekitar 2 menit | **DRAFT** |
| 3 | TP2 | Berapa banyak pasta gigi berfluorida yang sebaiknya digunakan anak-anak saat menyikat gigi? | Sebesar biji kacang polong | **DRAFT** |
| 4 | TP2 | Kapan sikat gigi sebaiknya diganti dengan yang baru? | Tiap sekitar 3 bulan atau saat bulu sikat sudah mengembang | **DRAFT** |
| 5 | TP3 | Makanan atau minuman apa yang paling baik dan menyehatkan bagi gigi kita? | Air putih, sayuran hijau, dan buah segar berserat | **DRAFT** |
| 6 | TP3 | Mengapa makanan manis dan lengket dapat berbahaya bagi kesehatan gigi? | Karena mudah menempel lama di gigi dan diubah kuman menjadi asam perusak gigi | **DRAFT** |
| 7 | TP4 | Apakah yang dimaksud dengan plak pada gigi? | Lapisan kuman/bakteri yang tipis dan menempel pada permukaan gigi | **DRAFT** |
| 8 | TP4 | Bagaimana gigi berlubang bisa terjadi? | Bakteri plak memakan sisa gula dan menghasilkan zat asam yang merusak lapisan luar gigi | **DRAFT** |
| 9 | TP5 | Seberapa sering kita sebaiknya memeriksakan gigi ke dokter gigi? | Sekitar 6 bulan sekali meskipun gigi tidak terasa sakit | **DRAFT** |
| 10 | TP5 | Apa yang dilakukan dokter gigi saat kita melakukan pemeriksaan rutin? | Memeriksa kebersihan gigi, mendeteksi lubang sejak dini, dan memberi petunjuk merawat gigi | **DRAFT** |

---

## 3. Butir Pernyataan Kuesioner Kepuasan Murid (`/api/survey`)
Skala respons 1-5 menggunakan ikon ekspresi wajah SVG (Sangat Tidak Setuju s/d Sangat Setuju):
1. `K1`: "Aku suka bermain dan belajar bersama Dento."
2. `K2`: "Aku mudah memahami cerita dan video dokter gigi di Dento."
3. `K3`: "Aku ingin merawat Dento lagi di rumah atau di sekolah."
4. `K4`: "Tombol dan game di dalam Rumah Dento mudah dimainkan di laptop."
5. `K5`: "Setelah bermain Dento, aku jadi tahu cara menyikat gigi yang benar."
6. `K6`: "Dento membuatku tidak takut lagi untuk periksa ke dokter gigi."

---

## 4. Butir Pertanyaan Jurnal Kebiasaan Gigi (`database/content/habits.json`)
Seluruh pertanyaan kebiasaan berstatus **DRAFT** dan wajib divalidasi oleh dokter gigi:
1. `sikat_pagi` (TP1): "Tadi pagi, setelah sarapan, kamu sikat gigi?" (Sudah / Belum / Lupa)
2. `sikat_malam` (TP1): "Semalam, sebelum tidur, kamu sikat gigi?" (Sudah / Belum / Lupa)
3. `manis` (TP3): "Sejak kemarin sampai sekarang, berapa kali kamu makan atau minum yang manis? Misalnya permen, cokelat, es, atau minuman manis." (0 / 1 / 2 / 3 kali atau lebih)
4. `kumur` (TP1, tampil jika konsumsi manis $\ge 1$ kali): "Setelah makan atau minum manis, kamu berkumur dengan air putih?" (Ya / Kadang-kadang / Belum)
5. `air_putih` (TP3): "Hari ini kamu sudah minum air putih?" (Sudah / Belum / Lupa)

---

## 5. Prosedur Pembaruan Konten Setelah Validasi Dokter Gigi
1. Sunting berkas JSON terkait di folder `database/content/`:
   - `test_items.json`
   - `habits.json`, `habit_tips.json`, `habit_rules.json`
   - `foods.json`
   - `quiz_bank.json`
   - `videos.json`
   - `daily_messages.json`
2. Jalankan perintah seeder untuk menyinkronkan data baru ke dalam database:
   ```bash
   php artisan db:seed --class=ContentSeeder
   ```
3. Ubah status butir dari `"DRAFT"` menjadi `"VALIDATED"` pada metadata berkas.
