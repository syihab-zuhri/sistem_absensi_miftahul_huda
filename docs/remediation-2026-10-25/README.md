# Perbaikan audit — 25 Oktober 2026

Temuan A01–A19 dari [audit awal](../audit-2026-10-25/README.md) telah ditangani. Perubahan mencakup otorisasi jadwal, keamanan HTML dan Excel, retensi riwayat, pergantian tahun ajaran, validasi jadwal, pengelolaan akun, dan aset frontend.

## Perubahan utama

| Temuan | Perbaikan |
| --- | --- |
| A01 | Scanner membuat elemen DOM dan mengisi data siswa melalui `textContent`. Interpolasi JavaScript memakai `@js`, dan nama pengguna pada SweetAlert memakai `titleText` atau DOM teks. |
| A02 | `SchedulePolicy` membatasi akses guru pada jadwalnya sendiri; admin memiliki akses sesuai perannya. |
| A03 | Penghapusan akun mandiri ditolak. Penghapusan siswa, guru, mata pelajaran, dan jadwal yang terkait riwayat dibatasi; foreign key absensi melindungi data dari cascade. |
| A04–A05 | Scanner dan API memeriksa tahun, semester, hari, rentang jam, serta tanggal tahun ajaran. Jam akhir bersifat eksklusif. |
| A06 | PDF sesi menggunakan satu tanggal dan status sebenarnya, dengan total hadir dihitung khusus status `hadir`. PDF historis dapat diminta melalui parameter `date=YYYY-MM-DD`. |
| A07 | Status dan history siswa disimpan dalam transaksi; history baru menyertakan kelas. Aktivasi tahun menyiapkan history siswa aktif. |
| A08 | Edit mempertahankan tahun asal. Jadwal yang telah memiliki absensi tidak dapat diubah menjadi konteks akademik lain. |
| A09 | Reset jadwal dibatasi tahun/semester terpilih dan ditolak jika ada riwayat absensi. Aksi penghapusan absensi pada laporan mengikuti filter tanggal dan satu tahun yang dipilih. |
| A10–A11 | Pemeriksaan konflik memakai irisan waktu, kelas/guru, hari, semester, dan tahun. Jadwal tahun lama tidak menghalangi tahun baru. |
| A12 | Rename kelas memperbarui referensi siswa, jadwal, dan history dalam transaksi. Kelas yang masih digunakan tidak dapat dihapus. |
| A13 | Statistik memakai nilai `alpa` dan agregasi per hari/status untuk grafik mingguan. |
| A14–A15 | Route statis ditempatkan sebelum resource, bulk-delete jadwal diimplementasikan, dan endpoint tanpa implementasi dihapus. |
| A16 | `attendance_date` dan unique constraint menjamin satu absensi per siswa/jadwal/hari. Batch menggunakan upsert dalam transaksi. Waktu pencatatan pertama dipertahankan. |
| A17 | NISN, email utama, dan email pribadi diresolusikan secara konsisten. Reset password dikirim ke email pribadi bila tersedia. Alamat login tidak boleh bertabrakan, termasuk perbedaan kapitalisasi. |
| A18 | Penolakan penonaktifan tahun dilakukan sebelum transaksi; operasi aktivasi memakai transaction closure. |
| A19 | Ekspor Excel memakai query dan `StringValueBinder`, sehingga nama/NISN pengguna tetap berupa teks dan tidak menjadi formula. |

Perbaikan tambahan:

- Semester aktif disimpan pada tahun ajaran di database sehingga tidak hilang ketika cache dibersihkan.
- Batch menolak siswa di luar kelas/nonaktif dan ID ganda; kegagalan validasi tidak menyimpan sebagian data.
- Siswa baru memperoleh sandi acak 16 karakter. Admin menerima sandi satu kali, dan siswa wajib menggantinya sebelum mengakses portal. Tombol kunci di Data Siswa dapat menerbitkan sandi awal baru.
- Sandi baru minimal 12 karakter. Perubahan/reset sandi memperbarui status kewajiban ganti sandi; middleware session memeriksa perubahan hash sandi.
- Seeder tidak membuat akun dan jadwal simulasi secara otomatis. Pembuatan admin dilakukan melalui `php artisan school:create-admin` dengan prompt sandi tersembunyi.
- Impor siswa mengenali akun berdasarkan NISN, mempertahankan email akun yang telah diubah, dan tidak mengaktifkan kembali alumni. File dibatasi 5 MB dan 1000 baris. Kegagalan pembuatan QR membatalkan perubahan database dan membersihkan berkas yang baru dibuat.
- Laporan dipaginasi 50 baris. Rentang laporan maksimal 366 hari; ekspor PDF maksimal 1000 baris dan Excel maksimal 50000 baris. Permintaan lebih besar meminta pengguna mempersempit filter.
- Aset Alpine, SweetAlert, Chart.js, scanner, ikon, dan font dibangun lokal oleh Vite. Scanner dan grafik dimuat saat halamannya membutuhkan.
- Scanner menangani kegagalan HTTP, izin kamera, dan QR yang tidak dikenal; penyimpanan tidak menghapus perubahan yang belum berhasil dikirim. Kamera diaktifkan melalui tombol.
- Label form, kontras scanner/Data Siswa, serta navigasi mobile diperbaiki. Link storage publik lokal dibuat ulang agar menunjuk ke direktori proyek saat ini.
- Rollback migration lama untuk `personal_email`, `status`, dan `semester` diperbaiki setelah diuji pada SQLite dan MariaDB.

## Database lokal dan backup

Dua migration baru telah dijalankan pada database lokal setelah berhasil diuji pada salinannya. Backup sebelum perubahan berada pada:

```text
storage/app/private/backups/absensi-before-fixes-20261025-135647.sqlite
```

Jumlah data sebelum dan sesudah: 2 akun, 1 siswa, 1 jadwal, 1 history kelas, dan 0 absensi. Tidak ditemukan duplikat pada database lokal. Bukti jumlah data: [database-migration.json](database-migration.json).

Jika database lain memiliki duplikat absensi, migration mempertahankan koreksi terbaru sebagai baris aktif dan menyimpan baris terdahulu lengkap dalam `attendance_duplicate_archives`. Arsip tersebut juga mempunyai perlindungan foreign key. Tes migration memeriksa penyimpanan arsip dan pemulihan baris asli saat rollback.

Sandi akun lama tidak dirotasi otomatis. Akun lama yang masih menggunakan sandi bawaan perlu menggantinya dari profil/pengaturan atau melalui reset oleh administrator.

## Dependensi

Laravel diperbarui ke 12.70.1 dan dependency PHP lain ke versi kompatibel dengan cabang proyek. Frontend tetap memakai Tailwind 3 dan Vite 7. Dependensi Axios yang tidak dipakai serta integrasi Tailwind 4 yang tidak digunakan dihapus.

Override `postcss-selector-parser` ke versi >=7.1.6 menutup advisori transitif sambil mempertahankan Tailwind 3. Kompatibilitas diperiksa melalui build dan browser. Referensi: [advisori maintainer parser](https://github.com/postcss/postcss-selector-parser/security/advisories/GHSA-rj75-hqrm-r3gf), [rilis Laravel 12.70.1](https://github.com/laravel/framework/releases/tag/v12.70.1).

Hasil audit pada saat verifikasi: **Composer 0 advisori, npm 0 kerentanan**. Bukti: [Composer](composer-audit.json), [npm](npm-audit.json). Hasil tersebut merupakan pemeriksaan advisori yang tersedia pada tanggal pengujian.

## Hasil verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| PHP 8.2.33, SQLite | 59 tes lulus, 164 assertion, tanpa skip/error/failure |
| PHP 8.2.33, MariaDB 11.8.6 terisolasi | 59 tes lulus, 164 assertion, tanpa skip/error/failure |
| JavaScript/DOM | 2 tes lulus: HTML berbahaya tetap berupa teks dan perubahan status memicu callback siswa yang benar |
| Sintaks PHP | 96 berkas, 0 kegagalan |
| Pint pada PHP yang diubah/ditambahkan | Lulus |
| `composer validate --no-check-publish` | Lulus |
| `npm run build` | Lulus, termasuk override parser |
| Cache config/route/Blade | Berhasil dibangun pada salinan pengujian, lalu dibersihkan |
| Browser | Login, scanner, simpan sesi, grafik admin, dialog edit siswa dengan nama berisi HTML, dan menu mobile berhasil diperiksa |
| XSS | Tidak ada elemen gambar dari payload; penanda eksekusi JavaScript tidak terbentuk |
| Aksesibilitas scanner dan Data Siswa | Tidak ada pelanggaran otomatis pada fixture yang diperiksa; pemeriksaan manual tetap diperlukan |

Bukti: [JUnit SQLite](phpunit-sqlite.xml), [JUnit MariaDB](phpunit-mariadb.xml), [tes JavaScript](javascript-tests.txt), [build frontend](frontend-build.txt), [aksesibilitas scanner](scanner-a11y.json), [aksesibilitas Data Siswa](students-a11y.json), [tampilan scanner](scanner-after.png).

Tes regresi permanen berada pada `tests/Feature/AuditRegressionTest.php`, `tests/Feature/AttendanceMigrationTest.php`, dan `tests/js/scanner.test.js`. Artefak pada folder audit awal tetap menggambarkan kondisi sebelum perbaikan.

## Menjalankan dan menerapkan pada lingkungan lain

Gunakan **PHP 8.2–8.4** dengan GD, fileinfo, mbstring, XML, ZIP, dan PDO sesuai database. Dependency Excel saat ini belum mendukung PHP 8.5. Gunakan Node.js 20.19+ atau 22.12+. Pada workspace pengujian ini, interpreter yang digunakan adalah `php82`.

Setelah backup database pada lingkungan tujuan:

```bash
composer install
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

Untuk instalasi baru, jalankan `php artisan db:seed` lalu `php artisan school:create-admin`. Tetapkan `APP_URL` sesuai alamat aplikasi, konfigurasi SMTP untuk reset sandi, dan gunakan HTTPS pada perangkat yang membutuhkan kamera browser.

Kamera fisik dan pengiriman email SMTP nyata belum diuji. Perubahan tidak dipublikasikan ke server eksternal. Pengujian database MySQL dilakukan menggunakan MariaDB terisolasi; server produksi pengguna tidak diakses.
