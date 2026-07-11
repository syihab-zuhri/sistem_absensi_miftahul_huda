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
| **Manajemen Jadwal Pintar** | Penjadwalan cerdas yang dipisahkan berdasarkan Semester Ganjil & Genap. Dilengkapi fitur Toggle ON/OFF global menggunakan Cache. |
| **Laporan & Ekspor** | Filter laporan berdasarkan tanggal, kelas, dan mata pelajaran. Mendukung ekspor ke format PDF dan Excel/CSV. |
| **Keamanan Database** | Fitur pengosongan database (Truncate) absensi untuk pergantian tahun ajaran, dilindungi dengan konfirmasi ganda via SweetAlert2. |

### 👨‍🏫 Guru

- **Antarmuka Scanner (Kamera):** Guru dapat membuka kamera pemindai langsung dari browser (HP/Laptop) untuk memindai QR Code siswa.
- **Deteksi Jadwal Otomatis:** Scanner mendeteksi secara otomatis mata pelajaran yang sedang diajarkan berdasarkan Jam, Hari, dan Semester yang aktif.
- **Batch Save Absensi:** Fitur untuk menyimpan seluruh rekaman absensi dalam satu sesi kelas dengan aman.

### 🎓 Siswa

- **Portal Siswa:** Siswa dapat login menggunakan NISN untuk melihat profil dan persentase kehadiran mereka.
- **Password Default:** Password bawaan siswa menggunakan NISN masing-masing (di-hash dengan aman).

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

- PHP >= 8.2
- Composer
- Node.js & NPM
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

## 🔐 Info Akses Default (Setelah Seeding)

| Peran | Email / Username | Password |
|---|---|---|
| **Admin** | `admin@sekolah.com` | `password123` |
| **Guru** | Tergantung data seeder | — |
| **Siswa (Simulasi)** | `siswa@sekolah.com` | `siswa123` |

> **Catatan:** Password siswa asli (bukan simulasi) menggunakan NISN masing-masing dan disimpan dengan enkripsi hash yang aman.

---

<div align="center">

Dibuat dengan ❤️ untuk kemajuan pendidikan **Miftahul Huda**

Dibuat dengan **[Laravel](http://laravel.com)**

</div>
