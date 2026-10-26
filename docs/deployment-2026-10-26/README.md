# Deployment VPS — 26 Oktober 2026

Status: **aktif** di <https://absensi.zuhri.my.id>. Kode aplikasi berasal dari branch `codex/attendance-workflows`, commit [`6ac24a5`](https://github.com/syihab-zuhri/sistem_absensi_miftahul_huda/commit/6ac24a57370b532ab73c40cba8fb754693df4c99). Pemeriksaan hash terhadap 190 berkas aplikasi dan aset cocok dengan rilis tersebut.

## Lingkungan aktif

- Nginx → PHP-FPM 8.3.6, Laravel 12.70.1, database SQLite.
- `APP_ENV=production`, `APP_DEBUG=false`; konfigurasi, rute, dan view telah dicache.
- Seluruh migrasi selesai; mode pemeliharaan sudah dinonaktifkan.
- Kunci aplikasi, sandi akun, pilihan tahun ajaran/semester, dan data lama dipertahankan.
- Tautan `public/storage` diperbaiki dari path Windows ke `/www/wwwroot/absensi.zuhri.my.id/storage/app/public`.
- Batas PHP-FPM khusus situs disetel melalui `public/.user.ini`: unggahan 5 MB dan total POST 8 MB. Berkas konfigurasi hosting ini berada di VPS, terpisah dari kode aplikasi. Unggahan sintetis 4,5 MiB berhasil melewati PHP; probe sementara beserta tokennya sudah dihapus.

## Data dan migrasi

| Data | Sebelum | Sesudah |
|---|---:|---:|
| Pengguna | 2 | 2 |
| Siswa | 1 | 1 |
| Jadwal | 1 | 1 |
| Presensi | 0 | 0 |
| Tahun ajaran | 2 | 2 |
| Riwayat kelas siswa | 1 | 1 |
| Master kelas | 0 | 1 |

Master kelas `10-A` ditambahkan dari referensi siswa/jadwal yang sudah ada. Referensi tersebut tidak diubah. Nilai asli tabel akademik dan akun diperiksa menggunakan fingerprint sebelum situs dibuka kembali. SQLite melaporkan `integrity_check = ok` dan nol pelanggaran foreign key.

## Verifikasi

- PHP 8.3 pada VPS, database pengujian terisolasi: **106 tes lulus, 559 assertion**.
- PHP 8.2 lokal: 104 tes awal dan 2 tes migrasi master kelas lulus; Pint lulus.
- JavaScript: **4 tes lulus**. Build Vite dan persyaratan platform Composer lulus.
- Migrasi dicoba terlebih dahulu pada salinan konsisten database produksi. Penjaga data juga diuji dengan salinan yang sengaja diubah dan berhasil menolaknya.
- HTTPS publik: login, health check, dan formulir lupa sandi mengembalikan 200. Rute sesi, audit, dan izin mengalihkan tamu ke login (302).
- Percobaan login dengan identitas uji yang tidak ada ditolak secara normal (422), sehingga alur sesi, CSRF, dan akses database berjalan tanpa membuat atau mengubah akun sekolah.
- Tampilan desktop dan ponsel 375 px diperiksa melalui browser. Tidak ditemukan luapan horizontal, kegagalan aset, atau galat JavaScript; tombol tampil/sembunyikan sandi berfungsi.
- Alur guru/siswa diuji dengan akun uji pada database terisolasi. Tidak ada reset sandi atau login sebagai akun sekolah yang dilakukan saat verifikasi publik.

Bukti: [status aplikasi](live-state.json), [tes PHP 8.3](php83-tests.txt), [tes migrasi PHP 8.2](php82-catalog-tests.txt), [tes JavaScript](javascript-tests.txt), [build frontend](frontend-build.txt), [platform PHP](platform-requirements.txt), [HTTPS](https-checks.json), [browser](browser-check.json), [unggahan](upload-limit-check.json), [desktop](login-desktop.png), [ponsel](login-mobile.png).

## Backup dan pemulihan

Backup privat berada di VPS pada:

```text
/home/codex/absensi-deployments/attendance-20261026T104835Z/backup/
```

Direktori rilis dibatasi dengan izin `700`; database, arsip kode, unggahan, dan salinan lingkungan berizin `600`. Database disalin menggunakan SQLite backup API dan diperiksa integritasnya; arsip kode serta storage diperiksa sebelum pemasangan. Berkas backup, kredensial produksi, dan kunci SSH tidak dimasukkan ke repository.

Percobaan pertama dipulihkan sebelum versi baru dibuka kepada pengguna karena pemeriksaan HTTPS langsung ke origin menolak Cloudflare Origin CA. Pemeriksaan diperbaiki agar memakai domain publik dengan verifikasi sertifikat tetap aktif. Percobaan berikutnya berhasil dan data setelah pemulihan telah diperiksa utuh.

Metadata rilis tersedia pada `/www/wwwroot/absensi.zuhri.my.id/.codex-release.json` dan direktori rilis privat. Direktori rilis yang sudah berhasil dipasang tidak dipakai ulang untuk deployment baru; siapkan direktori bertanggal baru dan backup baru untuk perubahan selanjutnya.
