# Optimasi Performa (Performance Tuning)

Panduan ini berisi teknik-teknik untuk mengubah The Framework dari mode pengembangan (Development) ke mode produksi kelas enterprise yang sangat cepat dan efisien.

---

## 🚀 1. Route Caching

**Masalah:**
Secara default, Router akan melakukan iterasi (scan) satu per satu setiap request untuk mencocokkan URL dengan regex. Ini lambat jika Anda memiliki ratusan route.

**Solusi:**
Ubah seluruh daftar route dinamis menjadi peta statis (array PHP) yang bisa diload instan.

**Perintah:**

```bash
php artisan route:cache
```

**Hasil:**
File cache akan dibuat di `storage/cache/routes.php`. Router akan otomatis mendeteksi file ini dan melompati proses scanning regex.

**PENTING:**
Setiap kali Anda mengubah file `routes/web.php` atau menambahkan route baru, Anda **WAJIB** menjalankan perintah ini lagi, atau jalankan `php artisan route:clear` untuk menghapus cache.

---

## ⚡ 2. Config Caching

**Masalah:**
Membaca file `.env` setiap request membutuhkan parsing text file yang membebani I/O Disk, terutama di server dengan beban tinggi.

**Solusi:**
Ubah variabel `.env` menjadi PHP Array murni. File PHP ini kemudian akan di-compile oleh OPcache dan disimpan di RAM, membuat akses config nyaris tanpa delay ($O(1)$ Memory Access).

**Perintah:**

```bash
php artisan config:cache
```

**Hasil:**
File cache dibuat di `storage/cache/config.php`.

**PENTING:**
Jangan pernah memanggil `getenv()` atau `$_ENV` langsung di kode aplikasi Anda jika menggunakan cache. Selalu gunakan helper `Config::get('KEY')` atau env helper global jika ada.
Jika Anda mengubah isi `.env`, jalankan perintah ini lagi.

---

## 🖼️ 3. Optimization: Asset Serving

**Masalah (Development Mode):**
Di localhost (`php artisan serve`), file gambar/CSS/JS dilayani oleh PHP (`serveAsset()`). Ini memakan RAM PHP (bisa 20MB+) hanya untuk mengirim file CSS kecil 5KB.

**Solusi (Production Mode):**
Jangan biarkan PHP menyentuh file statis! Konfigurasikan Web Server (Nginx/Apache) untuk melayani folder `/public` secara langsung.

### Konfigurasi Nginx (Rekomendasi)

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/project/public; # Point ke folder public

    index index.php;

    # 1. Layani file statis langsung tanpa lewat PHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 2. Block akses ke file sensitif
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 3. Pass PHP scripts ke FastCGI server
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }
}
```

Dengan konfigurasi ini, Nginx akan mengecek apakah file ada di folder public. Jika ada (misal `css/style.css`), Nginx langsung mengirimnya ke browser (0 overhead PHP). Jika tidak ada, baru dilempar ke `index.php` (Router).

---

## 🧠 4. Optimization: Explicit Binding (Container)

(Advanced) Jika aplikasi Anda sangat besar, penggunaan Reflection (auto-wiring) di Container bisa sedikit membebani CPU. Anda bisa mendaftarkan service secara eksplisit di `bootstrap/app.php` untuk menghindari reflection.

```php
// Contoh Explicit Binding (Tanpa Reflection)
$container->bind(UserController::class, function($c) {
    return new UserController($c->get(UserService::class));
});
```

_Note: Optimization ini hanya perlu dilakukan jika profiling menunjukkan bottleneck di Container._

---

## 🧹 Maintenance Commands

Bersihkan semua cache sebelum deploy ulang:

```bash
php artisan route:clear
php artisan config:clear
composer dump-autoload -o
```

(Flag `-o` pada composer dump-autoload akan mengoptimalkan class map autoloader).
