<div align="center">

# 🏫 Sistem Absensi Berbasis QR Code
## Miftahul Huda

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)](https://alpinejs.dev/)

Sistem presensi sekolah modern berbasis QR Code yang menggantikan absensi manual — lebih cepat, lebih akurat, dan real-time.

</div>

---

## 📖 Tentang Proyek

**Sistem Absensi Miftahul Huda** adalah aplikasi presensi sekolah modern yang dirancang untuk menggantikan presensi manual. Menggunakan teknologi QR Code, aplikasi ini mempercepat proses absensi, meminimalisir kecurangan, dan menyediakan laporan kehadiran secara real-time yang mudah dikelola.

---

## ✨ Fitur Utama

Sistem ini membagi hak akses menjadi **3 peran utama**: Admin, Guru, dan Siswa.

### 👨‍💻 Administrator

| Fitur | Deskripsi |
|---|---|
| **Manajemen Siswa Cerdas** | Tambah siswa manual atau via Import Excel. Mendukung aksi massal seperti Hapus Massal dan Pindah Kelas Massal. |
| **QR Code Generator** | Sistem otomatis membuatkan QR Code unik berformat SVG untuk setiap siswa baru. |
| **Manajemen Jadwal** | Jadwal per tahun ajaran dan semester; semester aktif tersimpan di database. |
| **Sesi Pertemuan** | Pertemuan bertanggal dengan pengajar, jam, peserta tetap, pembukaan/penutupan, pemindahan waktu, dan pembatalan. |
| **Audit & Persetujuan Izin** | Riwayat perubahan presensi, koreksi dengan alasan, dan pemeriksaan pengajuan izin/sakit beserta lampiran privat. |
| **Laporan & Ekspor** | Filter laporan berdasarkan tanggal, kelas, dan mata pelajaran. Mendukung ekspor ke format PDF dan Excel/CSV. |
| **Perlindungan Riwayat** | Presensi yang terhubung sesi dipertahankan. Data akademik bersejarah tidak dihapus melalui fitur pengosongan. |

### 👨‍🏫 Guru

- **Antarmuka Scanner (Kamera):** Guru dapat membuka kamera pemindai langsung dari browser (HP/Laptop) untuk memindai QR Code siswa.
- **Deteksi Jadwal Otomatis:** Scanner mendeteksi secara otomatis mata pelajaran yang sedang diajarkan berdasarkan Jam, Hari, dan Semester yang aktif.
- **Batch Save Absensi:** Fitur untuk menyimpan seluruh rekaman absensi dalam satu sesi kelas dengan aman.
- **Kelola Pertemuan & Izin:** Buka/tutup sesi, periksa izin/sakit peserta, serta koreksi presensi dengan alasan yang tercatat.

### 🎓 Siswa

- **Portal Siswa:** Siswa dapat login menggunakan NISN untuk melihat profil dan persentase kehadiran mereka.
- **Sandi Awal:** Sandi acak dibuat saat menambah atau mengimpor siswa, ditampilkan satu kali kepada admin, dan wajib diganti saat login pertama.
- **Izin/Sakit:** Ajukan permohonan untuk pertemuan kelas, unggah lampiran privat, dan lihat catatan keputusan pengajar.

---

## 🛠️ Teknologi yang Digunakan

| Kategori | Teknologi |
|---|---|
| **Framework** | [Laravel 12.x](https://laravel.com/) |
| **Frontend** | HTML5, CSS3, [Tailwind CSS](https://tailwindcss.com/) |
| **State Management** | [Alpine.js](https://alpinejs.dev/) |
| **Pop-up Alerts** | [SweetAlert2](https://sweetalert2.github.io/) |
| **QR Scanner** | QR Scanner JS |
| **Database** | MySQL |
| **QR Code Generator** | `simplesoftwareio/simple-qrcode` |
| **Import/Export Excel** | `maatwebsite/excel` |
| **Manajemen Role** | `spatie/laravel-permission` |

---

## 🚀 Cara Instalasi (Local Development)

### 1. Persyaratan Sistem

Pastikan komputer Anda sudah terinstal:

- PHP 8.2–8.4 dengan ekstensi GD, fileinfo, mbstring, XML, ZIP, dan PDO sesuai database
- Composer
- Node.js 20.19+ atau 22.12+ dan NPM
- MySQL (XAMPP / Laragon)

### 2. Langkah Instalasi

**Clone repositori:**

```bash
git clone https://github.com/syihab-zuhri/sistem_absensi_miftahul_huda.git
cd sistem_absensi_miftahul_huda
```

**Install dependensi PHP & Node.js:**

```bash
composer install
npm install
npm run build
```

**Atur Environment Variables:**

Copy file `.env.example` dan ubah namanya menjadi `.env`:

```bash
cp .env.example .env
```

Buka file `.env`, lalu atur koneksi database Anda:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_absensi_miftahul_huda
DB_USERNAME=root
DB_PASSWORD=
```

**Generate Application Key & Migrasi Database:**

```bash
php artisan key:generate
php artisan migrate --seed
```

**Buat Symlink Storage (⚠️ SANGAT PENTING):**

Langkah ini wajib dilakukan agar QR Code siswa dapat ditampilkan dan diunduh.

```bash
php artisan storage:link
```

**Jalankan Server Lokal:**

```bash
php artisan serve
```

Aplikasi kini dapat diakses melalui browser di: **[http://localhost:8000](http://localhost:8000)**

---

## 🔐 Akun dan Sandi Awal

Seeder hanya menyiapkan role dan tahun ajaran, tanpa akun atau sandi bawaan. Buat administrator dengan:

```bash
php artisan school:create-admin
```

Sandi awal dimasukkan melalui prompt tersembunyi (minimal 12 karakter). Untuk siswa baru, admin menerima daftar sandi acak satu kali setelah tambah/import. Tombol kunci pada Data Siswa dapat membuat sandi awal baru jika diperlukan. Siswa wajib mengganti sandi sebelum mengakses portal.

---

<div align="center">

Dibuat dengan ❤️ untuk kemajuan pendidikan **Miftahul Huda**

Dibuat dengan **[Laravel](http://laravel.com)**

</div>


## Perbaikan keamanan dan migrasi

Lihat [catatan perbaikan 25 Oktober 2026](docs/remediation-2026-10-25/README.md) untuk perubahan perilaku, migrasi data, dan hasil pengujian. Setelah memperbarui kode dan dependensi, jalankan `php artisan migrate` dan `npm run build`. Gunakan PHP 8.2–8.4; dependency Excel saat ini belum mendukung PHP 8.5.

## Sesi presensi, riwayat perubahan, dan izin/sakit

Lihat [panduan fitur dan pemasangan](docs/attendance-workflows-2026-10-25/README.md) untuk alur pengguna, aturan akses, migrasi, serta bukti pengujian. Menu **Sesi Presensi**, **Riwayat Perubahan**, dan **Pengajuan Izin/Sakit** tersedia sesuai peran pengguna.

Rilis VPS dan hasil verifikasi terbaru dicatat pada [laporan deployment 26 Oktober 2026](docs/deployment-2026-10-26/README.md).
