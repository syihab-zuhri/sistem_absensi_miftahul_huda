# Panduan Upload Project Laravel ke cPanel

## Prerequisites
- Akun cPanel dengan akses SSH (opsional tapi direkomendasikan)
- FTP Client (FileZilla, WinSCP, atau yang lain)
- Database MySQL tersedia di cPanel
- Composer sudah terinstall di server
- **PHP Extension:** `gd`, `zip`, `fileinfo`, `mbstring`, `xml`, `intl`, `curl` (wajib untuk QR Code SVG, Excel, dan PDF)

## Langkah 1: Persiapan Project Lokal

### 1.1 Buat file `.env` untuk production
```bash
cp .env.example .env
```

### 1.2 Generate application key
```bash
php artisan key:generate
```

### 1.3 Optimize untuk production
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 1.4 Clear composer cache (opsional)
```bash
composer dump-autoload --optimize
```

## Langkah 2: Upload Project ke Server

### 2.1 Via FTP (Recommended untuk pemula)

1. **Buka FTP Client** (FileZilla, WinSCP, dll)
2. **Connect ke server dengan credentials:**
   - Host: ftp://yourdomain.com atau IP server
   - Username: cPanel FTP username
   - Password: cPanel FTP password
   - Port: 21 (default FTP) atau 22 (SFTP - lebih aman)

3. **Upload ke folder yang tepat:**
   - Navigasi ke folder `public_html` (untuk domain utama)
   - Atau ke subfolder untuk subdomain
   - **Penting:** Jangan upload `.env` dan `.env.example` via FTP langsung di folder publik

4. **Ekstrak file di server** (jika upload sebagai ZIP)
   - Upload project sebagai ZIP file untuk mempercepat
   - Ekstrak menggunakan cPanel File Manager atau command line

### 2.2 Via SSH (Lebih cepat & profesional)

1. **Connect ke server via SSH:**
   ```bash
   ssh username@yourdomain.com
   # atau
   ssh username@server-ip
   ```

2. **Navigate ke public_html:**
   ```bash
   cd ~/public_html
   ```

3. **Clone dari repository (jika menggunakan Git):**
   ```bash
   git clone https://github.com/username/project-name.git .
   ```

4. **Atau upload via SCP:**
   ```bash
   scp -r ./project/* username@yourdomain.com:~/public_html/
   ```

## Langkah 3: Struktur Folder yang Benar

Laravel project harus memiliki struktur:
```
public_html/
├── public/        (semua file public)
│   ├── index.php  (entry point utama)
│   └── ...
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── storage/       (harus writable)
├── bootstrap/     (harus writable)
├── vendor/
├── .env           (JANGAN di public_html, letakkan di parent directory)
└── ...
```

**Atau lebih aman:**
```
project-root/
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── bootstrap/
├── vendor/
├── public/
│   └── index.php
└── .env

public_html/
└── (symlink atau pointer ke project-root/public)
```

## Langkah 4: Konfigurasi Database di cPanel

1. **Buka cPanel → MySQL Databases**
2. **Buat database baru:**
   - Nama: `username_dbname`
   - Klik "Create Database"

3. **Buat MySQL User:**
   - Username: `username_user`
   - Password: Generate strong password
   - Klik "Create User"

4. **Assign User ke Database:**
   - Pilih user yang baru dibuat
   - Pilih database
   - Klik "Add"
   - Berikan ALL PRIVILEGES

5. **Catat credentials:**
   - Database Name
   - Database Username
   - Database Password
   - Host: localhost (biasanya)

## Langkah 5: Konfigurasi File .env di Server

### 5.1 Via cPanel File Manager
1. Buka cPanel → File Manager
2. Navigate ke home directory (parent dari public_html)
3. Create file baru: `.env`
4. Edit dan masukkan:

```env
APP_NAME="Sistem Absensi"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_dbname
DB_USERNAME=username_user
DB_PASSWORD=your_password_here

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=cookie
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Tambahan (Wajib untuk QR Code)
FILESYSTEM_DISK=public
```

### 5.2 Via SSH
```bash
nano ~/.env
# Edit konfigurasi
# Ctrl+X, Y, Enter untuk save
```

## Langkah 6: Set Permissions yang Benar

### Via SSH:
```bash
cd ~/public_html

# Folder yang harus writable (755 untuk folder, 644 untuk file)
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Owner folder
chown -R nobody:nobody storage
chown -R nobody:nobody bootstrap/cache
```

### Via cPanel File Manager:
1. Select folder `storage`
2. Klik "Change Permissions"
3. Set ke: `755`
4. Check "Recursive"
5. Apply
6. Ulangi untuk `bootstrap/cache`

## Langkah 7: Install Dependencies & Setup

### Via SSH (Recommended):
```bash
cd ~/public_html

# Install composer dependencies
composer install --optimize-autoloader --no-dev

# Generate key (jika belum)
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (opsional)
php artisan db:seed --force

# Buat link storage (Wajib agar QR Code siswa bisa tampil/diakses)
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

> **Catatan untuk cPanel Tanpa Akses SSH:**
> Jika cPanel Anda tidak memiliki akses SSH, symbolic link untuk storage dapat dibuat dengan salah satu cara berikut:
> 1. **Via Cron Job:** Buka menu **Cron Jobs** di cPanel, tambahkan tugas baru yang dijalankan sekali saja:
>    `ln -s /home/username/public_html/storage/app/public /home/username/public_html/public/storage`
>    *(Ganti `/home/username/public_html` dengan absolute path folder project Anda di server).*
> 2. **Via File PHP Temporary:** Buat file `symlink.php` di dalam folder `public/` dengan isi:
>    `<?php symlink('/home/username/public_html/storage/app/public', '/home/username/public_html/public/storage');`
>    Akses file tersebut dari browser (`https://yourdomain.com/symlink.php`), lalu **segera hapus** file tersebut demi keamanan.

## Langkah 8: Setup .htaccess untuk URL Rewriting

Pastikan file `.htaccess` ada di `public_html/public/`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Langkah 9: Setup SSL Certificate

1. **Di cPanel → AutoSSL**
2. Klik tombol "check" untuk enable SSL otomatis
3. Tunggu beberapa menit untuk setup
4. Verifikasi di browser: `https://yourdomain.com`

**Force HTTPS di Laravel** - Update `APP_URL` di `.env`:
```env
APP_URL=https://yourdomain.com
```

Atau tambahkan di `app/Providers/AppServiceProvider.php`:
```php
if ($this->app->environment('production')) {
    \URL::forceScheme('https');
}
```

## Langkah 10: Testing & Troubleshooting

### 10.1 Cek apakah berjalan:
```bash
# Via SSH
curl https://yourdomain.com
```

### 10.2 Lihat error logs:
```bash
# Laravel error log
tail -f storage/logs/laravel.log

# Server error log (via cPanel)
# cPanel → Error Log
```

### 10.3 Tentang QR Code dan Storage Link

**Sistem absensi ini menggunakan QR Code SVG untuk setiap siswa.** QR Code disimpan di `storage/app/public/qrcodes/` dan diakses via `/storage/...`.

Agar QR Code bisa ditampilkan:

1. **`php artisan storage:link` harus dijalankan** — membuat symbolic link dari `public/storage` ke `storage/app/public`. Tanpa ini, QR Code tidak akan tampil.
2. **`APP_URL` di `.env` harus benar** — QR Code URL dihasilkan dari `Storage::url()` yang menggunakan `APP_URL` untuk prefiks path. Contoh:
   - Jika `APP_URL=http://yourdomain.com`, maka URL QR Code: `http://yourdomain.com/storage/qrcodes/123456.svg`
   - Jika menggunakan HTTPS production: `APP_URL=https://yourdomain.com`

3. **Folder `storage/` harus writable** — agar sistem bisa menulis file QR Code.

### 10.4 Common Issues & Solutions:

| Issue | Solution |
|-------|----------|
| **500 Internal Server Error** | Cek storage/logs/laravel.log, pastikan .env benar |
| **Permission Denied** | Set permission 755 untuk storage & bootstrap/cache |
| **Database Connection Error** | Verifikasi DB credentials di .env |
| **Class not found** | Run `composer install` & `php artisan optimize` |
| **404 Not Found** | Pastikan .htaccess ada & mod_rewrite enabled |
| **Blank page** | Set APP_DEBUG=false, cek error logs |
| **QR Code tidak tampil (gambar broken)** | Jalankan `php artisan storage:link`, verifikasi `APP_URL` di .env sesuai domain, cek permission folder storage (755) |
| **Generate QR Code error (Extension GD)** | Install extension PHP: `gd`, `zip`, `fileinfo`, `mbstring`, `xml`, `intl` via cPanel |
| **QR Code link invalid / 404** | Verifikasi symbolic link ada: buka `https://yourdomain.com/storage/` di browser. Jika blank, storage:link belum berjalan |

## Langkah 11: Backup & Maintenance

### Scheduled Backup:
1. Buka cPanel → Backup
2. Setup backup schedule

### Monitor Performance:
```bash
# Check disk usage
du -sh ~/public_html

# Check memory
free -h

# Check Laravel logs reguler
tail -f storage/logs/laravel.log
```

## Ringkasan Quick Deploy

```bash
# 1. Connect ke server
ssh username@yourdomain.com

# 2. Go to public_html
cd ~/public_html

# 3. Upload project (via Git atau FTP)
git clone ... atau upload via FTP

# 4. Set permissions
chmod -R 755 storage bootstrap/cache

# 5. Install & setup
composer install --no-dev
php artisan key:generate
php artisan migrate --force

# 6. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Done!
```

---

**Last Updated:** 2026-07-05  
**Project:** Sistem Absensi
