# Audit proyek Sistem Absensi — 25 Oktober 2026

> Dokumen ini merekam kondisi sebelum perbaikan. Lihat [status perbaikan dan bukti terbaru](../remediation-2026-10-25/README.md).

Proyek memiliki **19 temuan fungsional dan keamanan yang direproduksi**, terdiri dari **9 prioritas tinggi (P1)** dan **10 prioritas menengah (P2)**. Pembatasan akses objek, keamanan data yang ditampilkan scanner, dan penyimpanan riwayat sekolah perlu diperbaiki sebelum aplikasi digunakan dengan data operasional penting.

P1 berarti berpotensi mengambil alih tindakan pengguna lain, menghapus/mengubah riwayat, atau menghasilkan laporan kehadiran yang salah. P2 berarti fungsi gagal pada kondisi tertentu atau ketahanan data belum memadai. Prioritas ini merupakan penilaian dampak pada aplikasi, terpisah dari severity advisori dependensi.

## Ruang lingkup dan hasil pemeriksaan

Diperiksa: seluruh controller, model, request, route, migration, seeder, import/export, konfigurasi, dokumentasi, pengujian bawaan, template halaman utama, serta JavaScript scanner. Pengujian berjalan pada salinan kode di `/tmp/sistem-absensi-audit-20261025`, dengan dependensi dari lock file proyek. Kode aplikasi, `.env`, dan database asli tidak diedit.

| Pemeriksaan | Hasil |
| --- | --- |
| Sintaks PHP pada app/bootstrap/config/database/routes/tests | 83 berkas, 0 kegagalan pada PHP 8.2.33; cache diabaikan |
| `composer validate --no-check-publish` | Lulus |
| Tes bawaan, PHP 8.2.33 dan SQLite di memori | **22 lulus, 56 assertion** |
| Tes reproduksi audit, PHP 8.2.33 dan SQLite di memori | **21 lulus, 53 assertion**: 20 skenario cacat dan 1 tes kontrol akses |
| Instalasi frontend bersih dari `package-lock.json` | Lulus dengan `npm ci` di salinan `/tmp` |
| `npm run build` pada instalasi bersih | Lulus, Vite 7.3.3, 56 modul |
| `php82 artisan view:cache` dan `route:cache` | Keduanya lulus pada salinan proyek |
| Persyaratan platform Composer pada PHP 8.2.33 | Lulus, termasuk GD dan SQLite |
| Reproduksi browser | XSS siswa → guru berhasil dibuktikan |
| Aksesibilitas scanner, axe-core 4.12.1 | 2 aturan dilanggar: kontras dan nama aksesibel pada select |
| Audit Composer, termasuk require-dev | 56 entri advisori pada 15 paket |
| Audit npm | 13 kelompok dependensi rentan: 2 critical, 8 high, 2 moderate, 1 low |

**Arti tes audit yang lulus:** tes tersebut sengaja memeriksa bahwa perilaku bermasalah dapat terjadi. Hasil ini membuktikan reproduksi cacat, bukan bahwa cacat telah diperbaiki. Tes bawaan terutama menguji autentikasi/profil; belum mencakup alur absensi, pergantian tahun ajaran, laporan, dan otorisasi per jadwal.

Instalasi asli belum menyediakan 36 paket pengembangan PHP, sehingga `php artisan test` tidak terdaftar. Build asli gagal karena `@rollup/rollup-linux-x64-gnu` tidak tersedia di `node_modules`. Keduanya merupakan keterbatasan instalasi lokal yang berhasil diatasi di salinan pengujian. PHP default 8.5.4 juga tidak cocok dengan `phpoffice/phpspreadsheet 1.30.4` yang mensyaratkan PHP < 8.5 dan membutuhkan GD; verifikasi aplikasi memakai PHP 8.2.33 yang memenuhi kebutuhan paket.

## Temuan P1

### A01 — Stored XSS dari profil siswa ke sesi guru

**Lokasi:** `resources/views/scanner.blade.php:156`, `:160`, `:174`; `app/Http/Controllers/ProfileController.php:30`; `app/Http/Requests/ProfileUpdateRequest.php:24`.

Siswa dapat mengisi nama profil dengan HTML. API mengembalikan nama tersebut, lalu scanner menyisipkannya langsung melalui template string dan `insertAdjacentHTML`. Kode JavaScript di atribut HTML kemudian berjalan dalam origin aplikasi dan sesi guru. Blade escaping pada halaman lain tidak melindungi sink JavaScript ini.

**Reproduksi browser yang telah dijalankan:**

1. Login dengan akun siswa dummy, buka `/profile`, dan masukkan nama yang berisi `<img>` dengan handler `onerror`. [Bukti pengisian](xss-step-1.png).
2. Simpan profil; server menerima nilai tersebut. [Bukti nilai tersimpan](xss-step-2.png).
3. Logout, login sebagai guru dummy, buka `/scanner`. Handler mengganti isi sel menjadi `XSS AUDIT`, dan `document.body.dataset.auditXss` bernilai `"1"`. [Bukti eksekusi dalam sesi guru](xss-step-3.png), [video reproduksi](xss-repro.webm).

Payload audit hanya menampilkan penanda lokal. Perbaikan: bangun DOM menggunakan `textContent` dan atribut DOM untuk data siswa; audit juga interpolasi data ke skrip dan SweetAlert HTML.

### A02 — Guru dapat membaca dan mengubah absensi milik guru lain

**Lokasi:** `app/Http/Controllers/AttendanceController.php:43`, `:78`; `routes/web.php:143`.

Endpoint GET `/absensi/session-data/{id}` dan POST `/absensi/save-session/{id}` hanya dilindungi role guru/admin. Keduanya menerima jadwal dengan ID apa pun tanpa memeriksa `teacher_id` terhadap pengguna yang login.

**Bukti:** guru A mendapat HTTP 200 beserta NISN siswa dari jadwal guru B, lalu berhasil menyimpan status `hadir` pada jadwal tersebut. Perbaikan: terapkan policy/otorisasi per jadwal secara konsisten pada kedua endpoint, dengan aturan admin yang eksplisit.

### A03 — Siswa dapat menghapus riwayat absensi sekolah lewat penghapusan akun

**Lokasi:** `routes/web.php:26`; `app/Http/Controllers/ProfileController.php:43`; `database/migrations/2026_05_15_084757_create_students_table.php:17`; `database/migrations/2026_05_15_102139_create_attendances_table.php:14`.

`DELETE /profile` tersedia bagi semua pengguna terautentikasi. Menghapus user siswa memicu cascade ke `students` lalu `attendances`. Siswa cukup memasukkan password sendiri untuk menghapus catatan kehadiran yang dikelola sekolah. Penghapusan akun guru juga terhubung ke cascade jadwal dan absensi.

**Bukti:** setelah siswa mengirim DELETE dengan password benar, catatan absensinya hilang dari database. Perbaikan: pisahkan penonaktifan akun dari retensi data akademik; batasi penghapusan permanen dan lindungi relasi arsip.

### A04 — Scanner dapat memilih jadwal tahun ajaran lama

**Lokasi:** `app/Http/Controllers/AttendanceController.php:28`.

Pemilihan jadwal memfilter guru, hari, jam, dan semester, tetapi tidak memfilter `academic_year_id` atau tanggal tahun ajaran. Jika jadwal lama masih tersimpan, `first()` dapat memilih jadwal arsip dan absensi baru masuk ke tahun yang salah.

**Bukti:** dengan dua jadwal identik pada tahun lama dan tahun aktif, scanner memilih jadwal tahun lama yang dibuat lebih dahulu. Perbaikan: wajibkan tahun ajaran aktif pada pemilihan sesi serta tentukan perilaku jika ada lebih dari satu jadwal yang cocok.

### A05 — API tetap menerima absensi untuk sesi yang sudah ditutup

**Lokasi:** `app/Http/Controllers/AttendanceController.php:78`.

Pembatasan hari/jam hanya diterapkan saat merender halaman scanner. API penyimpanan tidak memeriksa hari, rentang jam, semester aktif, dan tahun aktif. Halaman yang menampilkan “Akses Scanner Ditutup” tidak mencegah request langsung.

**Bukti:** request pada hari Senin berhasil menyimpan absensi ke jadwal Selasa pukul 01.00–02.00, semester Genap yang tidak aktif, dan tahun lama. Perbaikan: validasi aturan sesi di server pada setiap penyimpanan; bila diperlukan, sediakan alur koreksi terpisah yang berotorisasi dan tercatat.

### A06 — PDF sesi mencampur tanggal dan menampilkan semua siswa sebagai hadir

**Lokasi:** `app/Http/Controllers/ReportController.php:149`; `resources/views/reports/attendance.blade.php:50`, `:76`.

Query mengambil semua absensi sepanjang umur jadwal tanpa filter tanggal sesi. Template menghitung semua baris sebagai “Total Hadir” dan mencetak label `Hadir` secara tetap, termasuk untuk sakit, izin, dan alpa.

**Bukti:** satu absensi sakit minggu lalu dan satu alpa hari ini masuk ke laporan sesi yang sama, lalu keduanya dirender sebagai hadir. Query dan HTML laporan diuji; rendering biner PDF tidak diuji dalam tes ini. Perbaikan: tetapkan tanggal sesi secara eksplisit, gunakan status sebenarnya, dan hitung total hadir dari status `hadir`.

### A07 — Kelulusan pada tahun baru dapat gagal setelah status telanjur berubah

**Lokasi:** `app/Http/Controllers/StudentController.php:256`; `database/migrations/2026_07_05_122326_create_student_class_histories_table.php:15`.

`updateStatusBulk()` membuat history baru hanya dengan `status`, padahal `class_id` wajib diisi. Ini terjadi ketika tahun ajaran baru sudah aktif tetapi history siswa untuk tahun tersebut belum ada, sesuai urutan pergantian tahun yang didokumentasikan proyek. Operasi tidak dibungkus transaksi bersama pembaruan siswa.

**Bukti:** proses mengembalikan HTTP 500 karena `class_id` kosong; status siswa sudah berubah menjadi `lulus` sedangkan history belum terbentuk. Perbaikan: isi `class_id` ketika membuat history dan simpan seluruh perubahan secara atomik.

### A08 — Mengedit jadwal arsip memindahkan riwayatnya ke tahun aktif

**Lokasi:** `app/Http/Controllers/ScheduleController.php:125`.

Update selalu menimpa `academic_year_id` dengan tahun aktif. Saat admin memfilter tahun lama dan mengedit jadwalnya, jadwal tersebut berpindah tahun. Laporan memperoleh tahun dari jadwal sehingga absensi historis ikut terklasifikasi ulang.

**Bukti:** jadwal tahun 2025/2026 yang sudah mempunyai absensi menjadi jadwal 2026/2027 setelah diedit. Perbaikan: pertahankan tahun asal saat edit; pemindahan/penyalinan tahun harus menjadi operasi terpisah dan tidak mengubah arsip.

### A09 — Kosongkan semester menghapus jadwal dan absensi lintas tahun

**Lokasi:** `app/Http/Controllers/ScheduleController.php:157`; `resources/views/admin/schedules/index.blade.php:361`.

Halaman menyediakan filter tahun, tetapi operasi truncate hanya memfilter semester. Jadwal pada seluruh tahun dengan semester yang sama terhapus; cascade kemudian menghapus absensinya.

**Bukti:** reset Ganjil dengan tahun aktif yang disertakan dalam request tetap menghapus jadwal dan absensi tahun lama serta tahun aktif. Perbaikan: batasi operasi berdasarkan tahun dan semester yang dipilih, tampilkan cakupan penghapusan, serta pertahankan arsip sesuai kebijakan retensi.

## Temuan P2

| ID | Lokasi | Bukti dan dampak | Arah perbaikan |
| --- | --- | --- | --- |
| A10 | `ScheduleController.php:77`, `:107` | Jadwal 08.00–09.00 dan 08.30–09.30 pada kelas/guru yang sama sama-sama diterima. Validasi hanya membandingkan jam awal dan akhir yang persis sama. | Periksa irisan interval untuk kelas dan guru, dengan lingkup hari, semester, dan tahun. |
| A11 | `ScheduleController.php:77`, `:107` | Jadwal identik tahun lama menghalangi pembuatan jadwal tahun baru karena kondisi unique tidak menyertakan tahun ajaran. | Sertakan tahun ajaran pada pemeriksaan konflik dan normalkan format waktu. |
| A12 | `ClassroomController.php:33`; kolom `class_id` bertipe string pada siswa/jadwal/history | Mengubah nama kelas 10-A menjadi 10-B meninggalkan siswa dan jadwal memakai 10-A. Kelas lama hilang dari master dan pilihan filter. | Gunakan relasi ID kelas yang stabil, atau migrasikan semua referensi secara atomik saat rename. Lindungi penghapusan kelas yang masih digunakan. |
| A13 | `Admin/DashboardController.php:29`, `:50` | Data menggunakan `alpa`, sedangkan statistik mencari `alfa`. Satu catatan alpa menghasilkan hitungan nol pada statistik harian dan mingguan. | Satukan nilai status melalui konstanta/enum dan perbaiki query serta labelnya. |
| A14 | `routes/web.php:83`, `:110` | Tanpa route cache, DELETE `/admin/schedules/truncate-by-year` tertangkap route resource `{schedule}` dan berakhir 404. | Daftarkan endpoint statis sebelum resource; tambahkan constraint parameter ID. |
| A15 | `routes/web.php:78`; `ScheduleController.php` | Endpoint bulk-delete jadwal mengarah ke `destroyBulk()` yang tidak ada dan menghasilkan HTTP 500. Resource users juga mendaftarkan create/show/edit/update yang tidak diimplementasikan, dan `/test-qr` merujuk view yang tidak ada; tambahan ini ditemukan melalui pembacaan kode. | Implementasikan aksi yang digunakan dan daftarkan hanya endpoint yang tersedia. |
| A16 | `AttendanceController.php:98`; `2026_05_15_102139_create_attendances_table.php:20` | Dua baris siswa, jadwal, dan tanggal identik dapat disimpan. Pola baca-lalu-create tanpa unique constraint membuka kemungkinan duplikasi saat request bersamaan. | Gunakan tanggal sesi tersendiri dengan unique constraint yang sesuai, upsert, dan transaksi. Pengujian membuktikan constraint tidak ada; balapan dua request belum diuji. |
| A17 | `Auth/PasswordResetLinkController.php:36`; `StudentSettingsController.php:58` | Email pribadi diterima sebagai identitas login tetapi tidak ditemukan password broker yang mencari `users.email`. Request reset dengan email pribadi menghasilkan error dan tidak mengirim notifikasi. | Selaraskan lookup akun dan alamat tujuan reset dengan mekanisme login siswa. |
| A18 | `AcademicYearController.php:117`, `:129` | Menonaktifkan satu-satunya tahun aktif melakukan return sebelum commit/rollback. `DB::transactionLevel()` bertambah satu setelah respons. | Validasi penolakan sebelum transaksi atau gunakan transaction closure yang selalu menyelesaikan transaksi. Dampak pada proses panjang perlu diuji sesuai server yang dipakai. |
| A19 | `resources/views/reports/excel.blade.php:28`; `app/Exports/AttendancesExport.php` | Nama siswa `=1+1` diekspor menjadi elemen formula `<f>1+1</f>` pada worksheet XLSX, sehingga data pengguna ditafsirkan sebagai formula. | Tulis teks pengguna dengan tipe sel string eksplisit, termasuk nama dan NISN. Tes memakai formula aritmetika aman; eksekusi formula eksternal pada aplikasi spreadsheet belum diuji. |

Semua baris tabel memiliki tes reproduksi, kecuali tambahan endpoint users dan view `/test-qr` pada A15 yang berstatus temuan statis.

## Dependensi dan kondisi operasional

Hasil mentah disimpan pada [composer-audit.json](composer-audit.json) dan [npm-audit.json](npm-audit.json). Angka Composer adalah jumlah entri advisori, bukan jumlah CVE unik; hasil memuat entri ganda untuk satu masalah validasi email Laravel dan advisori paket pengembangan `symfony/yaml`. Hasil npm menghitung kelompok paket, termasuk paket yang terdampak transitif.

Paket yang perlu diprioritaskan dalam peninjauan pembaruan meliputi Laravel 12.59.0, PhpSpreadsheet 1.30.4, Dompdf, Guzzle, Vite 7.3.3, Axios 1.16.1, dan shell-quote. Keberadaan advisori belum membuktikan semua jalur eksploit dapat dicapai dalam proyek ini.

Advisori critical Laravel yang ditemukan berkaitan dengan **TokenGuard**. Konfigurasi proyek yang diperiksa menggunakan guard `web` berbasis session, sehingga audit ini tidak membuktikan aplikasi dapat dieksploitasi melalui masalah TokenGuard tersebut. [Sumber resmi Laravel](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6).

Advisori bypass pembatasan stream wrapper mencakup PhpSpreadsheet 1.30.4. Sumber maintainer membedakan dampak PHP 7 dan PHP 8; aplikasi ini mensyaratkan PHP 8.2 sehingga temuan tersebut tidak boleh langsung dilaporkan sebagai RCE pada aplikasi. Jalur upload impor tetap perlu ditinjau saat memperbarui paket. [Sumber resmi PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-87m4-826x-3crx).

Observasi operasional tambahan dari kode:

- Seeder memasang akun admin dengan password tetap dan akun siswa awal memakai NISN sebagai password. Tidak ditemukan alur wajib mengganti password awal. Hindari menyebarkan akun default yang sama di lingkungan operasional.
- Layout dashboard memuat Tailwind, Alpine, dan SweetAlert dari CDN; scanner memuat html5-qrcode dari unpkg tanpa versi tetap. Keberhasilan build Vite tidak menjamin halaman tersebut berfungsi ketika CDN tidak dapat diakses.
- Filter semester aktif disimpan hanya dalam cache. Pengosongan cache mengembalikannya ke Ganjil, sehingga konfigurasi operasional dapat berubah tanpa perubahan tahun/semester di database.
- Halaman laporan dan ekspor memakai `get()` tanpa batas rentang tanggal atau pagination/chunking; dashboard menjalankan 28 query statistik hanya untuk grafik 7 hari. Pengukuran beban produksi belum dilakukan.
- Pembuatan siswa manual memperbarui user, siswa, history, dan berkas QR tanpa transaksi terpadu atau kompensasi kegagalan berkas. Risiko state parsial perlu diuji dengan kegagalan storage.
- Impor mengaktifkan kembali siswa yang ditemukan dan dapat menimpa kelasnya. Identitas impor bergantung pada email sintetis, sementara `/profile` mengizinkan penggantian email. Perilaku impor terhadap alumni dan akun yang emailnya berubah membutuhkan pengujian regresi.

## Aksesibilitas yang diperiksa

Hasil scanner tersimpan pada [scanner-a11y.json](scanner-a11y.json). Axe menemukan 2 aturan yang dilanggar pada fixture satu siswa: **6 node dengan kontras kurang** dan **1 select tanpa nama aksesibel**. Tambahkan label/`aria-label` yang mengidentifikasi siswa untuk dropdown status dan sesuaikan warna teks/tombol. Pemeriksaan ini hanya mencakup halaman scanner desktop, bukan audit aksesibilitas seluruh aplikasi.

## Bukti dan cara menjalankan ulang

- [Tes reproduksi audit](ProjectAuditReproductionTest.php), disimpan di folder dokumentasi agar tidak masuk suite aplikasi secara otomatis.
- [Hasil JUnit tes audit](audit-tests.xml).
- [Video XSS](xss-repro.webm) dan tiga screenshot langkah reproduksi di atas. Semua akun dan data pada bukti dibuat khusus untuk audit.

Untuk menjalankan ulang, gunakan salinan checkout terpisah dengan dependensi pengembangan dan PHP yang memenuhi lock file. Salin `ProjectAuditReproductionTest.php` ke `tests/Feature/`, siapkan `APP_KEY` pengujian, dan pastikan konfigurasi tes tetap memakai SQLite di memori sebagaimana `phpunit.xml` proyek. Jalankan:

```bash
php artisan route:clear
php artisan test --group=audit
php artisan test --exclude-group=audit
npm ci
npm run build
composer audit --locked
npm audit
```

Perintah cache, instalasi, dan tes di atas diperuntukkan bagi salinan pengujian. A14 direproduksi tanpa route cache. Perbaikan aplikasi belum diterapkan; berkas baru pada proyek ini hanya dokumentasi dan bukti audit.

## Batas verifikasi dan urutan penanganan

Belum diuji: database MySQL produksi, konkurensi request sesungguhnya, kamera fisik dan izin kamera pada perangkat siswa/guru, delivery email SMTP, rendering akhir kartu/PDF, serta performa pada volume data sekolah sebenarnya. Database asli dan konfigurasi deployment produksi tidak diaudit secara langsung.

Urutan penanganan yang disarankan: A01–A03 untuk menutup jalur penyalahgunaan; A04–A09 untuk melindungi ketepatan dan retensi absensi; A10–A19 untuk konsistensi alur; lalu pembaruan dependensi sesuai keterjangkauan jalurnya, penguatan pengujian otomatis, aksesibilitas, dan pengukuran performa.
