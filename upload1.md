# Panduan Upload Project Laravel ke aaPanel

Panduan ini berisi langkah-langkah untuk mengonlinekan project **Sistem Absensi** Laravel ke VPS/server yang menggunakan **aaPanel**.

> Target project ini menggunakan **Laravel 12** dan membutuhkan **PHP 8.2 atau lebih baru**.

---

## 1. Persiapan Sebelum Upload

### 1.1 Pastikan server sudah terinstall aaPanel

Jika aaPanel belum terinstall, install aaPanel di VPS sesuai OS yang digunakan.

Contoh Ubuntu/Debian:

```bash
URL=https://www.aapanel.com/script/install_7.0_en.sh && if [ -f /usr/bin/curl ]; then curl -ksSO "$URL" ; else wget --no-check-certificate -O install_7.0_en.sh "$URL"; fi; bash install_7.0_en.sh aapanel
```

Setelah selesai, simpan informasi:

```text
aaPanel URL
Username
Password
```

---

## 2. Install Software yang Dibutuhkan di aaPanel

Masuk ke dashboard aaPanel, lalu buka menu **App Store**.

Install komponen berikut:

- **Nginx** atau **Apache**
- **MySQL/MariaDB**
- **PHP 8.2 / 8.3**
- **phpMyAdmin** opsional
- **Redis** opsional
- **Supervisor** opsional jika nanti memakai queue worker

### 2.1 Extension PHP yang direkomendasikan

Buka:

```text
aaPanel → App Store → PHP 8.2 → Settings → Install Extensions
```

Install/aktifkan extension berikut:

```text
fileinfo        ← Wajib untuk file upload validation
mbstring        ← Wajib untuk string processing
openssl         ← Wajib untuk encryption
pdo_mysql       ← Wajib untuk database
tokenizer       ← Wajib untuk Laravel
xml             ← Wajib untuk parsing
ctype           ← Wajib untuk character validation
json            ← Wajib untuk JSON processing
bcmath          ← Wajib untuk precision math
gd              ← WAJIB untuk QR Code SVG generation
zip             ← WAJIB untuk file compression (Excel, PDF download)
intl            ← WAJIB untuk internationalization
curl            ← Opsional untuk HTTP requests
exif            ← Opsional untuk image metadata
```

**Catatan Kritis untuk QR Code:**
Sistem absensi ini **otomatis membuat QR Code SVG unik untuk setiap siswa** menggunakan library `simplesoftwareio/simple-qrcode`. Extension `gd`, `zip`, `fileinfo`, `mbstring`, `xml`, dan `intl` **wajib** agar QR Code generation berfungsi dan sistem tidak error saat generate/export QR Code.

---

## 3. Buat Website di aaPanel

Buka menu:

```text
Website → Add site
```

Isi data:

```text
Domain      : yourdomain.com
Root path   : /www/wwwroot/yourdomain.com
Database    : MySQL
PHP Version : PHP-82 atau PHP-83
```

Jika aaPanel menyediakan pilihan untuk membuat database langsung, aktifkan opsi database.

Jika tidak, database bisa dibuat manual di menu:

```text
Database → Add database
```

Catat informasi berikut:

```text
Database name
Database username
Database password
Database host: localhost
Database port: 3306
```

---

## 4. Upload Project ke Server

Ada 3 cara upload project ke aaPanel.

---

### Opsi A: Upload ZIP lewat File Manager aaPanel

1. Di komputer lokal, compress project menjadi `.zip`.
2. Masuk aaPanel.
3. Buka menu:

```text
Files → /www/wwwroot/yourdomain.com
```

4. Upload file `.zip`.
5. Extract file tersebut.
6. Pastikan isi project Laravel berada langsung di:

```text
/www/wwwroot/yourdomain.com
```

Struktur yang benar:

```text
/www/wwwroot/yourdomain.com
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   └── .htaccess
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
├── composer.json
├── composer.lock
└── .env
```

> Jangan arahkan domain ke folder root Laravel. Domain harus diarahkan ke folder `public`.

---

### Opsi B: Upload menggunakan Git

Masuk terminal server dari aaPanel:

```text
aaPanel → Terminal
```

Lalu jalankan:

```bash
cd /www/wwwroot
rm -rf yourdomain.com
git clone https://github.com/username/nama-repository.git yourdomain.com
cd yourdomain.com
```

Ganti URL repository dengan repository project Anda.

---

### Opsi C: Upload menggunakan SCP dari komputer lokal

Dari komputer lokal:

```bash
scp -r ./sistem-absensi root@SERVER_IP:/www/wwwroot/yourdomain.com
```

Atau upload ZIP:

```bash
scp sistem-absensi.zip root@SERVER_IP:/www/wwwroot/yourdomain.com
```

---

## 5. Atur Document Root ke Folder Public

Ini langkah paling penting untuk Laravel.

Buka:

```text
aaPanel → Website → yourdomain.com → Site directory
```

Ubah **Running directory** menjadi:

```text
/public
```

Atau jika memakai path penuh, arahkan root website ke:

```text
/www/wwwroot/yourdomain.com/public
```

Dengan begitu, file yang dapat diakses publik hanya isi folder `public`, bukan seluruh source code Laravel.

---

## 6. Konfigurasi Rewrite URL

### Jika menggunakan Nginx

Buka:

```text
aaPanel → Website → yourdomain.com → Rewrite URL
```

Pilih template Laravel jika tersedia.

Jika tidak ada, isi manual:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\.ht {
    deny all;
}
```

Klik **Save**.

Restart Nginx:

```text
aaPanel → App Store → Nginx → Restart
```

---

### Jika menggunakan Apache

Pastikan file berikut ada:

```text
/www/wwwroot/yourdomain.com/public/.htaccess
```

Isi `.htaccess` Laravel standar:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

Pastikan Apache `mod_rewrite` aktif.

---

## 7. Buat dan Konfigurasi File `.env`

Masuk ke folder project:

```bash
cd /www/wwwroot/yourdomain.com
```

Jika belum ada `.env`, buat dari `.env.example`:

```bash
cp .env.example .env
```

Edit file `.env` lewat aaPanel File Manager atau terminal:

```bash
nano .env
```

Contoh konfigurasi production:

```env
APP_NAME="Sistem Absensi"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username_database
DB_PASSWORD=password_database

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=password_email
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Setelah itu generate app key:

```bash
php artisan key:generate
```

> Pastikan `APP_DEBUG=false` saat sudah online agar error sensitif tidak tampil ke publik.

---

## 8. Install Dependency Composer

Masuk ke terminal aaPanel:

```bash
cd /www/wwwroot/yourdomain.com
```

Jalankan:

```bash
composer install --no-dev --optimize-autoloader
```

Jika perintah `composer` tidak tersedia, install composer:

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer --version
```

Jika composer gagal karena PHP CLI masih memakai versi lama, cek versi PHP:

```bash
php -v
```

Jika perlu, gunakan path PHP aaPanel secara langsung, contoh:

```bash
/www/server/php/82/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

---

## 9. Build Asset Frontend

Jika project menggunakan Vite, jalankan build asset.

Pastikan Node.js dan npm tersedia:

```bash
node -v
npm -v
```

Jika belum ada, install Node.js melalui aaPanel App Store atau terminal.

Lalu jalankan:

```bash
cd /www/wwwroot/yourdomain.com
npm install
npm run build
```

Setelah build berhasil, folder asset production biasanya berada di:

```text
public/build
```

Jika Anda tidak ingin menjalankan `npm install` di server, Anda bisa menjalankan ini di lokal:

```bash
npm install
npm run build
```

Lalu upload folder berikut ke server:

```text
public/build
```

---

## 10. Jalankan Migration dan Seeder

Jalankan migration database:

```bash
php artisan migrate --force
```

Jika ingin mengisi data awal:

```bash
php artisan db:seed --force
```

Jika hanya ingin menjalankan seeder tertentu, contoh:

```bash
php artisan db:seed --class=RoleSeeder --force
```

---

## 11. Buat Storage Link

Laravel membutuhkan symbolic link agar file storage bisa diakses publik.

**Untuk project Sistem Absensi, langkah ini wajib.** QR Code siswa dibuat sebagai file SVG dan disimpan di:

```text
storage/app/public/qrcodes/
```

Halaman data siswa menampilkan QR Code melalui URL `/storage/qrcodes/...`. Jadi tanpa storage link, QR Code berhasil dibuat tetapi tidak akan tampil di browser.

```bash
php artisan storage:link
```

Hasilnya akan membuat link:

```text
public/storage → storage/app/public
```

Jika gagal karena symlink tidak diizinkan, buat manual lewat terminal:

```bash
ln -s /www/wwwroot/yourdomain.com/storage/app/public /www/wwwroot/yourdomain.com/public/storage
```

Setelah itu verifikasi link storage:

```bash
ls -la public/storage
```

Pastikan `.env` production memakai domain yang benar agar URL QR Code sesuai:

```env
APP_URL=https://yourdomain.com
FILESYSTEM_DISK=public
```

---

## 12. Atur Permission Folder

Laravel harus bisa menulis ke folder `storage` dan `bootstrap/cache`.

Jalankan:

```bash
cd /www/wwwroot/yourdomain.com
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache
```

Jika user web server berbeda, cek user Nginx/Apache di aaPanel. Biasanya aaPanel memakai user:

```text
www
```

Jika masih error permission, gunakan sementara:

```bash
chmod -R 777 storage bootstrap/cache
```

> Gunakan `777` hanya untuk troubleshooting. Setelah berhasil, kembalikan ke permission yang lebih aman.

---

## 13. Optimasi Laravel untuk Production

Setelah konfigurasi benar, jalankan:

```bash
cd /www/wwwroot/yourdomain.com
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Jika setelah cache muncul error, clear ulang:

```bash
php artisan optimize:clear
```

---

## 14. Pasang SSL HTTPS

Buka:

```text
aaPanel → Website → yourdomain.com → SSL
```

Pilih salah satu:

- **Let's Encrypt** untuk SSL gratis
- **Other Certificate** jika punya sertifikat sendiri

Aktifkan:

```text
Force HTTPS
```

Pastikan `.env` menggunakan HTTPS:

```env
APP_URL=https://yourdomain.com
```

Jika aplikasi tetap redirect ke HTTP, tambahkan force HTTPS di `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

---

## 15. Setup Cron Job Laravel Scheduler Opsional

Jika project memakai scheduler Laravel, tambahkan cron job di aaPanel.

Buka:

```text
aaPanel → Cron → Add task
```

Isi:

```text
Task type : Shell Script
Name      : Laravel Scheduler
Cycle     : Every minute
```

Command:

```bash
cd /www/wwwroot/yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

Jika perlu memakai PHP 8.2 aaPanel:

```bash
cd /www/wwwroot/yourdomain.com && /www/server/php/82/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## 16. Setup Queue Worker Opsional

Jika `.env` memakai queue selain `sync`, misalnya:

```env
QUEUE_CONNECTION=database
```

Maka perlu menjalankan queue worker dengan Supervisor.

Buka:

```text
aaPanel → App Store → Supervisor
```

Tambahkan process:

```text
Name      : laravel-queue
User      : www
Directory : /www/wwwroot/yourdomain.com
Command   : php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Jika perlu path PHP 8.2:

```bash
/www/server/php/82/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Untuk project ini, jika menggunakan:

```env
QUEUE_CONNECTION=sync
```

maka Supervisor belum diperlukan.

---

## 17. Testing Website

Buka browser:

```text
https://yourdomain.com
```

Cek halaman login/admin dan fitur utama:

- Login user
- Data siswa
- Data jadwal
- Absensi
- Upload/import Excel
- Generate QR Code
- Export PDF jika tersedia

---

## 18. Cek Log Error

### Log Laravel

```bash
cd /www/wwwroot/yourdomain.com
tail -f storage/logs/laravel.log
```

### Log Nginx/Apache di aaPanel

Buka:

```text
aaPanel → Website → yourdomain.com → Logs
```

Atau via terminal:

```bash
# Nginx error log
/www/wwwlogs/yourdomain.com.error.log

# Nginx access log
/www/wwwlogs/yourdomain.com.log
```

---

## 19. Troubleshooting Umum

| Masalah | Penyebab Umum | Solusi |
|---|---|---|
| 500 Internal Server Error | `.env` salah, permission salah, composer belum install | Cek `storage/logs/laravel.log`, jalankan composer install, perbaiki permission |
| 404 semua route kecuali `/` | Rewrite belum benar | Set Rewrite URL Laravel di aaPanel |
| Halaman blank | Error PHP tapi debug off | Cek Laravel log dan web server log |
| Database error | DB name/user/password salah | Periksa `.env` dan database aaPanel |
| `No application encryption key` | `APP_KEY` kosong | Jalankan `php artisan key:generate` |
| `Vite manifest not found` | Asset belum dibuild | Jalankan `npm install && npm run build` |
| Upload file gagal | Permission storage salah | Jalankan `chmod -R 775 storage bootstrap/cache` dan `chown -R www:www ...` |
| Class/package not found | Vendor belum ada | Jalankan `composer install --no-dev --optimize-autoloader` |
| PHP version error | PHP CLI berbeda dengan PHP website | Gunakan `/www/server/php/82/bin/php` |
| **QR Code tidak tampil / gambar broken** | Storage link belum dibuat atau APP_URL salah | Jalankan `php artisan storage:link`, verifikasi `APP_URL` di .env, dan pastikan `FILESYSTEM_DISK=public` |
| **Generate QR Code error** | Extension PHP GD tidak aktif | Buka aaPanel > PHP > Settings > Install extension `gd` |
| QR/PDF/Excel error (Selain GD) | Extension PHP kurang | Install extension `zip`, `fileinfo`, `mbstring`, `xml`, `intl` |

---

## 20. Checklist Deploy Cepat

Gunakan checklist ini setelah upload project:

```bash
cd /www/wwwroot/yourdomain.com

# 1. Install dependency
composer install --no-dev --optimize-autoloader

# 2. Buat .env jika belum ada
cp .env.example .env

# 3. Generate key
php artisan key:generate

# 4. Edit .env sesuai database dan domain
nano .env

# 5. Migration database
php artisan migrate --force

# 6. Seeder opsional
php artisan db:seed --force

# 7. Storage link
php artisan storage:link

# 8. Permission
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache

# 9. Build asset
npm install
npm run build

# 10. Optimasi production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Lalu di aaPanel:

```text
1. Website root/running directory arahkan ke /public
2. Rewrite URL pakai konfigurasi Laravel
3. SSL Let's Encrypt aktif
4. Force HTTPS aktif
5. Cek log jika ada error
```

---

## 21. Catatan Keamanan

- Jangan pernah menaruh `.env` di folder yang bisa diakses publik.
- Pastikan domain diarahkan ke folder `public`, bukan root Laravel.
- Gunakan `APP_DEBUG=false` di production.
- Gunakan password database yang kuat.
- Jangan upload folder `.git` ke server production jika tidak diperlukan.
- Backup database dan file secara rutin lewat aaPanel.
- Batasi akses SSH hanya untuk user yang diperlukan.

---

## Ringkasan Struktur Final yang Benar

```text
/www/wwwroot/yourdomain.com
├── app/
├── bootstrap/
├── config/
├── database/
├── public/                 ← domain diarahkan ke sini
│   ├── index.php
│   ├── build/
│   └── storage/            ← hasil php artisan storage:link
├── resources/
├── routes/
├── storage/                ← writable
├── vendor/
├── .env                    ← tidak boleh bisa diakses publik
├── artisan
├── composer.json
└── composer.lock
```

---

**Last Updated:** 2026-07-05  
**Project:** Sistem Absensi  
**Panel:** aaPanel
