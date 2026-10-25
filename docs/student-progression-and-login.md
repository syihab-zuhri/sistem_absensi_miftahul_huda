# Dokumentasi Fitur Siswa, Kenaikan Kelas, dan Login Email/NISN

Dokumentasi ini menjelaskan fitur baru yang ditambahkan untuk mengelola status siswa, riwayat kelas per tahun ajaran, serta login siswa menggunakan NISN atau email pribadi.

## 1. Manajemen Status Siswa

Sistem sekarang memiliki status siswa pada tabel `students`.

Status yang didukung di sisi backend:

- `aktif`
- `lulus`
- `pindah`
- `keluar`

Pada halaman admin `Data Siswa`, filter status sengaja disederhanakan menjadi:

- **Semua**
- **Aktif**
- **Lulus**

Tujuannya agar admin fokus pada kebutuhan utama: melihat siswa aktif dan alumni/lulusan.

## 2. Tahun Ajaran dan Riwayat Kelas

Sistem sekarang mendukung konsep tahun ajaran melalui tabel:

- `academic_years`
- `student_class_histories`

### `academic_years`

Menyimpan data tahun ajaran, misalnya `2026/2027`.

Kolom penting:

- `name`
- `start_date`
- `end_date`
- `is_active`

### `student_class_histories`

Menyimpan riwayat kelas siswa per tahun ajaran.

Kolom penting:

- `student_id`
- `academic_year_id`
- `class_id`
- `status`
- `started_at`
- `ended_at`

Dengan tabel ini, sistem dapat melacak siswa berada di kelas apa pada tahun ajaran tertentu.

## 3. Alur Aman Kenaikan Kelas

Agar data tidak menumpuk di kelas tujuan, gunakan urutan berikut saat tahun ajaran baru:

1. Pastikan tahun ajaran aktif sudah benar.
2. Filter siswa kelas akhir, misalnya kelas XII.
3. Pilih semua siswa kelas XII, lalu klik **Luluskan**.
4. Filter siswa kelas XI, lalu pindahkan ke kelas XII.
5. Filter siswa kelas X, lalu pindahkan ke kelas XI.
6. Tambahkan atau import siswa baru ke kelas X.

Urutan ini mencegah siswa lama di kelas atas bercampur dengan siswa yang baru naik kelas.

## 4. Login Siswa dengan NISN atau Email Pribadi

Siswa sekarang dapat login menggunakan:

- NISN
- Email pribadi yang diatur dari halaman Pengaturan

### Cara Siswa Menambahkan Email Pribadi

1. Login sebagai siswa.
2. Buka sidebar **Pengaturan**.
3. Isi form **Email Login Pribadi**.
4. Simpan.
5. Logout.
6. Login kembali menggunakan email pribadi tersebut atau NISN.

### Catatan Teknis

Email pribadi disimpan di tabel `students.personal_email`, bukan mengganti `users.email`.

Alasannya:

- `users.email` tetap menjadi email sistem utama.
- Admin/guru tetap login menggunakan email utama.
- Siswa tetap bisa login menggunakan NISN walaupun sudah menambahkan email pribadi.

## 5. Halaman Siswa

Sidebar siswa sekarang berisi:

1. **Portal Siswa**
   - Kartu Pelajar Digital
   - Riwayat Kehadiran
2. **Pengaturan**
   - Email Login Pribadi
   - Keamanan Akun / Ganti Password

Form keamanan akun dipindahkan dari Portal Siswa ke halaman Pengaturan agar tampilan portal lebih bersih.

## 6. File Penting

### Model

- `app/Models/Student.php`
- `app/Models/AcademicYear.php`
- `app/Models/StudentClassHistory.php`
- `app/Models/Schedule.php`

### Controller

- `app/Http/Controllers/StudentController.php`
- `app/Http/Controllers/StudentSettingsController.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Requests/Auth/LoginRequest.php`

### View

- `resources/views/admin/students/index.blade.php`
- `resources/views/student/portal.blade.php`
- `resources/views/student/settings.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/dashboard.blade.php`

### Migration

- `database/migrations/*_add_status_to_students_table.php`
- `database/migrations/*_create_academic_years_table.php`
- `database/migrations/*_create_student_class_histories_table.php`
- `database/migrations/*_add_academic_year_id_to_schedules_table.php`
- `database/migrations/*_add_personal_email_to_students_table.php`

## 7. Verifikasi Setelah Deploy

Jalankan perintah berikut setelah pull/deploy:

```bash
php artisan migrate
php artisan route:list
```

Jika data lama perlu diselaraskan dengan tahun ajaran aktif, jalankan command backfill yang tersedia:

```bash
php artisan academic:backfill
```

Kemudian uji manual:

1. Login siswa dengan NISN.
2. Tambahkan email pribadi di Pengaturan.
3. Logout.
4. Login siswa dengan email pribadi.
5. Login admin/guru dengan email utama untuk memastikan tetap normal.
6. Luluskan siswa kelas akhir dari halaman admin.
7. Pastikan siswa lulus tidak muncul di daftar aktif/scanner.


## Pembaruan keamanan 25 Oktober 2026

Sandi awal siswa berupa nilai acak yang ditampilkan sekali kepada admin setelah tambah/import. Sandi tersebut wajib diganti melalui Pengaturan. Admin dapat membuat sandi awal baru melalui tombol kunci di Data Siswa. Email pribadi yang didaftarkan dapat digunakan untuk meminta tautan reset sandi. Impor siswa lama memakai NISN sebagai identitas dan tidak mengaktifkan kembali alumni. Saat tahun ajaran diaktifkan, riwayat kelas awal disiapkan otomatis; perpindahan kelas dan kelulusan disimpan dalam transaksi.

Jadwal dan data induk yang terhubung dengan absensi tidak dapat dihapus melalui penghapusan biasa. Reset jadwal dibatasi tahun dan semester; penghapusan riwayat absensi melalui laporan mengikuti rentang tanggal dan tahun yang dipilih administrator.
