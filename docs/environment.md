# CONFIGURATION & ENVIRONMENT

The-Framework menggunakan library `vlucas/phpdotenv` untuk mengelola variabel environment, namun dilengkapi dengan layer caching khusus untuk performa maksimal.

## File `.env`

Semua konfigurasi sensitif harus disimpan di file `.env`. Jangan pernah commit file ini ke Git repository.

Contoh konfigurasi standar:

```ini
APP_NAME="The Framework"
APP_ENV=local          # local, development, production
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=framework_db
DB_USERNAME=root
DB_PASSWORD=
```

## Mengakses Config

Gunakan class helper `Config` untuk mengambil nilai environment.

```php
use TheFramework\App\Config;

// Ambil value basic
$mode = Config::get('APP_ENV');

// Ambil dengan default value jika tidak ada
$apiKey = Config::get('API_KEY', 'default-key-123');
```

---

## ⚡ Config Caching (Wajib untuk Production)

Parsing file `.env` itu berat (lambat) karena melibatkan pembacaan file text dari disk di setiap request.

Fitur **Config Caching** akan membaca `.env` sekali, lalu menyimpannya sebagai file PHP native di `storage/cache/config.php`.

### Cara Menggunakan:

1. Jalankan command:
   ```bash
   php artisan config:cache
   ```
2. Framework sekarang akan mem-bypass parsing `.env` dan langsung memuat array dari cache.
3. **Peringatan:** Jika Anda mengubah isi `.env`, perubahan tidak akan terbaca sampai Anda menjalankan cache ulang atau clear.

### Cara Menghapus Cache:

Jika Anda dalam mode development dan sering gonta-ganti config, hapus cache:

```bash
php artisan config:clear
```

Atau cukup jangan jalankan cache command di local development.
