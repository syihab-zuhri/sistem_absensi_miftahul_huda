# Sesi presensi, audit perubahan, dan izin/sakit

Implementasi pada branch `codex/attendance-workflows`, melanjutkan [perbaikan sebelumnya](../remediation-2026-10-25/README.md). Aturan produk dijelaskan pada [spesifikasi fitur](../attendance-workflows.md).

## Penggunaan

1. Admin/guru membuka **Sesi Presensi**, memilih jadwal dan tanggal, lalu membuat pertemuan. Jam mengikuti jadwal mingguan kecuali diisi berbeda. Admin dapat memilih pengajar pengganti.
2. Siswa membuka **Pengajuan Izin/Sakit**, memilih pertemuan yang sudah disiapkan, mengisi alasan, dan menambahkan PDF/JPEG/PNG maksimal 5 MB jika diperlukan.
3. Pengajar sesi/admin memeriksa pengajuan dan mencatat alasan persetujuan/penolakan. Pengajuan dapat diputuskan sebelum atau setelah pertemuan. Persetujuan untuk sesi mendatang diterapkan saat sesi dibuka.
4. Pada hari pertemuan, pengajar membuka sesi. Daftar siswa disimpan saat itu dan tidak mengikuti perpindahan kelas berikutnya. Pengajuan mendatang untuk siswa yang sudah keluar kelas dibatalkan dengan catatan.
5. Mulai jam pertemuan, gunakan scanner atau pilihan status manual. Scanner hanya mengirim perubahan dan memeriksa status sebelumnya. Mengubah status tersimpan memerlukan alasan minimal 10 karakter.
6. Presensi dapat disimpan tanpa alasan sampai 60 menit setelah jam selesai (ubah melalui `ATTENDANCE_GRACE_MINUTES`). Sesudahnya, termasuk untuk pertemuan hari sebelumnya pada semester aktif, buka detail sesi lalu scanner/presensi manual: penyimpanan menjadi **pencatatan susulan** yang wajib disertai alasan minimal 10 karakter. Sesi yang terlupa dibuka pada harinya tetap dapat dibuka belakangan.
7. Tutup sesi setelah semua peserta tercatat. Pengisian alpa untuk peserta kosong tersedia melalui pilihan eksplisit; pengajuan peserta kosong yang masih menunggu keputusan harus diselesaikan dahulu.
8. Koreksi setelah sesi ditutup dilakukan dari detail sesi, dengan alasan dan pemeriksaan status sebelumnya. **Riwayat Perubahan** mencatat pencatatan, pencatatan susulan beserta alasannya, koreksi, keputusan izin, dan perubahan sesi beserta pelaku/waktunya.

Sesi terencana dapat dipindahkan ke tanggal/jam lain, termasuk dikembalikan ke tanggal sebelumnya. Tanggal yang ditinggalkan tidak dibuat ulang oleh scanner mingguan. Pemeriksaan bentrokan mencakup sesi bertanggal dan jadwal mingguan yang belum dibuat menjadi sesi. Sesi tanpa presensi dapat dibatalkan dengan alasan; pengajuan terkait ikut dibatalkan dan riwayat tetap tersimpan.

Pilihan pertemuan pada formulir siswa menampilkan satu bulan terakhir sampai tiga bulan mendatang. Pertemuan dengan pengajuan yang masih menunggu/disetujui tidak ditawarkan kembali; riwayat pengajuan tetap tersedia.

## Integritas dan akses

- Satu sesi per jadwal/tanggal dan satu presensi per siswa/jadwal/tanggal. Penyimpanan batch, keputusan izin, penutupan, dan audit berada dalam transaksi.
- Sesi menyimpan pengajar pertemuan; akses dan laporan mengikuti pengajar tersebut, termasuk guru pengganti.
- Lampiran berada pada disk privat `local` di `storage/app/private/leave-attachments`. Unduhan memerlukan login serta hak sebagai pemilik pengajuan, pengajar sesi, atau admin.
- Presensi `hadir` tidak ditimpa otomatis oleh persetujuan izin/sakit. Pengajar perlu memeriksa dan menggunakan koreksi jika pencatatan hadir keliru.
- Alamat login siswa `{NISN}@siswa.com` hanya pengenal. `siswa.com` adalah domain pihak ketiga, sehingga email tidak pernah dikirim ke alamat tersebut. Tautan reset sandi hanya dikirim ke email pribadi siswa. Siswa tanpa email pribadi diarahkan meminta sandi baru kepada admin melalui tombol kunci di Data Siswa.
- Siswa boleh membatalkan pengajuan yang masih menunggu, lalu mengajukan ulang. Pengajuan ditolak/dibatalkan tetap tersimpan.
- Jadwal, siswa, pengguna, dan presensi dengan referensi riwayat dilindungi dari penghapusan melalui aplikasi. Penggantian nama kelas memperbarui sesi terencana; sesi yang sudah dibuka mempertahankan nama kelas dan pesertanya.

## Migrasi dan pemasangan

Migrasi `2026_10_25_000003_add_attendance_workflows` menambahkan sesi, peserta, tanggal pertemuan yang dipindahkan, pengajuan, dan catatan audit. Presensi lama ditautkan ke sesi tertutup sesuai jadwal/tanggalnya, tanpa mengubah ID, status, atau timestamp presensi. Peserta sesi lama hanya berasal dari siswa yang memang mempunyai catatan pada pertemuan tersebut. Identitas pencatat lama tidak direkayasa.

Migrasi `2026_10_25_000000_attach_schedules_to_academic_years` mengisi tahun ajaran jadwal lama yang tersimpan tanpa tahun (formulir jadwal sebelumnya tidak mengirim tahun). Jadwal ditautkan ke tahun yang rentang tanggalnya memuat presensi terakhir jadwal tersebut, atau tanggal pembuatannya; bila tidak ada yang cocok, ke tahun aktif. Tanpa langkah ini jadwal tersebut tidak muncul di scanner, daftar jadwal, dan laporan. Jika belum ada tahun ajaran sama sekali, jadwal dibiarkan dan peringatan ditulis ke log; jalankan `php artisan academic:backfill` setelah membuat tahun ajaran.

MySQL/MariaDB melakukan commit untuk setiap perintah DDL, sehingga migrasi 2026_10_25 dibuat aman dijalankan ulang bila prosesnya terputus (koneksi SSH putus, timeout, server restart):

- `000001` melanjutkan dari langkah terakhir. Pengisian tanggal dan pengarsipan duplikat berada dalam satu transaksi, sehingga baris tidak pernah setengah diarsipkan.
- `000003` membuang tabel setengah jadi lalu membangunnya ulang dari data presensi. Jika tabel pengajuan, audit, atau pemindahan sesi sudah berisi data dari aplikasi, migrasi berhenti dan meminta pemulihan backup, bukan menghapus data.

Pada MariaDB 11.8 dengan 475.440 presensi (990 siswa, 900 jadwal, 16 minggu), keempat migrasi selesai dalam ±31 detik (`000001` ±13 detik, `000003` ±11 detik) di penyimpanan tmpfs. Disk server akan lebih lambat. Proses yang dimatikan paksa di tengah `000001` dan di tengah `000003` selesai dengan benar saat `php artisan migrate` dijalankan ulang, dan hasilnya identik dengan proses yang tidak terputus.

Setelah backup database serta `storage/app/private` dan `storage/app/public`, pada lingkungan tujuan dengan PHP 8.2–8.4:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
```

Jalankan `php artisan down` sebelum migrasi dan `php artisan up` sesudahnya. Lakukan upgrade di luar jam pencatatan, karena presensi lama dimigrasikan menjadi sesi tertutup. Untuk memperkirakan durasi, periksa jumlah presensi dengan `SELECT COUNT(*) FROM attendances;`. Pada workspace lokal, backup SQLite dibuat dengan SQLite backup API dan lolos `integrity_check` sebelum migrasi. Jumlah pengguna/siswa/jadwal/presensi tetap 2/1/1/0. Server publik tidak dideploy oleh pekerjaan ini.

Rollback migrasi mempertahankan baris presensi dan menghapus struktur baru beserta riwayat/pengajuan di dalamnya. Backup harus dipulihkan bila data fitur baru perlu dipertahankan sesudah rollback.

## Verifikasi

| Pemeriksaan | Hasil |
|---|---|
| PHP 8.2.33 + SQLite terisolasi | 93 tes lulus, 468 assertion |
| PHP 8.2.33 + MariaDB 11.8.6 terisolasi | 93 tes lulus, 468 assertion |
| JavaScript DOM | 3 tes lulus, termasuk pengiriman hanya siswa yang berubah dan status sebelumnya |
| Build Vite | Lulus |
| Composer validate | Lulus |
| Sintaks PHP / Blade terkompilasi | 115 / 112 berkas, 0 kesalahan |
| Pint | 68 berkas PHP yang diubah/ditambahkan lulus |
| Migrasi riwayat dan rollback | ID, status, tanggal, serta timestamp presensi lama tetap dipertahankan |
| Browser siswa/guru | Pengajuan dengan lampiran → persetujuan → buka sesi → presensi manual → tutup/alpa → koreksi → riwayat |
| Pembatasan akses browser | Guru lain menerima 403 untuk sesi dan lampiran; penyimpanan biasa sesi tertutup menerima 403 |
| Ponsel 375 px | Tidak ada luapan horizontal; menu dapat dibuka/ditutup, Escape mengembalikan fokus ke pemicu |
| axe-core: sesi, scanner, riwayat, pengajuan siswa | 0 pelanggaran otomatis; 1 pemeriksaan kontras native select pada ponsel memerlukan penilaian visual |

Pemeriksaan otomatis tidak mencakup kamera fisik, koneksi sekolah, atau pengujian beban serentak. Pemindaian QR memakai komponen yang sudah ada; alur manual dan penyimpanan sesi diuji lewat browser.

Bukti: [JUnit SQLite](phpunit-sqlite.xml), [JUnit MariaDB](phpunit-mariadb.xml), [build](frontend-build.txt), [audit sesi](session-a11y.json), [audit scanner](scanner-a11y.json), [audit riwayat](history-a11y.json), [audit pengajuan siswa](student-leave-a11y.json), [tampilan ponsel](student-mobile.png), [riwayat desktop](history-desktop.png).
