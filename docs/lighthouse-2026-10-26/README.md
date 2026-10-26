# Lighthouse login desktop — 26 Oktober 2026

Perubahan rilis `84c5a001ad6650937844645351dbecb3bd9c304b` sudah aktif di `https://absensi.zuhri.my.id/login`. JavaScript aplikasi yang dimuat login turun dari 135.877 byte menjadi 406 byte. Login kini mengunduh CSS khusus 29.584 byte, JavaScript 406 byte, dan satu font Jakarta variabel 27.348 byte, bukan bundel dashboard, font ikon, dan empat font terpisah. Total aset Vite turun dari 455.967 menjadi 57.338 byte (87,4%).

## Hasil pengukuran

Lighthouse 13.5.0 memakai Chrome 151, preset desktop, dan kategori Performance. Opsi Lighthouse serta emulasi layar desktop 1350 × 940 sama pada semua laporan.

Empat audit akhir memakai profil Chrome baru. Skornya **73, 100, 91, dan 100**, median **95,5**. Keempat pengukuran memuat CSS, JavaScript, dan font yang benar dengan status HTTP 200. Dua dari empat skor masuk 100; hasilnya berubah besar antar pengukuran dan mencakup skor 78 yang dilaporkan pengguna.

Satu audit awal sebelum perubahan menjadi pembanding cache dingin yang lengkap: skor 99, FCP/LCP 774 ms, TBT 0 ms, CLS 0,0082, aset Vite 455.967 byte. Median empat audit setelah perubahan mencatat FCP 874 ms, LCP 998 ms, TBT 0 ms, CLS 0, dan aset Vite 57.338 byte. Pengukuran halaman publik terpengaruh variasi waktu respons dokumen; audit setelah perubahan mencatat `server-response-time` sekitar 380–740 ms. Hasil ini membuktikan pengurangan unduhan aset. Skor, FCP, dan LCP tetap berfluktuasi, jadi laporan ini tidak mengklaim kenaikan terukur untuk metrik tersebut terhadap satu sampel awal.

Ada tiga audit awal tambahan: 99 dan 99, lalu 87. Pengukuran ketiga itu memuat halaman tanpa stylesheet (`resourceSize` CSS nol), jadi tidak menggambarkan tampilan login yang benar. Pengukuran kedua menggunakan cache disk untuk CSS dan prefetch JavaScript. Hasil awal tersebut tidak digabung sebagai pembanding tiga audit cache dingin.

## Perubahan

- Layout autentikasi memakai CSS dan JavaScript khusus. Aset dashboard dan fitur login tetap tersedia pada entry masing-masing.
- Ikon login memakai SVG sebaris dari Font Awesome Free 7.3.1, sehingga halaman tidak meminta font ikon 119 KB.
- Empat font Jakarta statis diganti satu font variabel 27 KB; font dimuat lokal dan dipra-muat.
- Tombol tampil/sembunyikan sandi memiliki status aksesibel serta diuji tetap berfungsi tanpa mengirim formulir.

## Verifikasi dan rilis

- Build Vite berhasil.
- JavaScript: **6 tes lulus**. PHP 8.2: **106 tes lulus, 559 assertion**.
- Pratinjau desktop dan lebar ponsel diuji; form lupa sandi juga berhasil dirender.
- Produksi: `/login`, `/forgot-password`, dan `/up` merespons 200; percobaan login tidak valid mendapat 422; pemeriksaan integritas SQLite lulus.
- Rilis tidak menjalankan migrasi database atau mengubah pengaturan hosting. Hash `.env` dan konfigurasi unggahan diperiksa tetap sama selama deployment. Berkas baru dicadangkan privat di `/home/codex/absensi-deployments/lighthouse-20261026T122000Z/backup/`.
- Audit JSON yang sudah disaring disimpan di [measurements.json](measurements.json). Berkas tersebut tidak memuat cookie, HTML respons, kredensial, atau data pengguna.

Cabang: [`codex/lighthouse-performance`](https://github.com/syihab-zuhri/sistem_absensi_miftahul_huda/tree/codex/lighthouse-performance).
