# 🌐 Konfigurasi Environment (v5.0.1 — All-In)

File `.env` adalah **jantung konfigurasi** The Framework. Seluruh perilaku aplikasi — dari mode debug, koneksi database, hingga rate limiter — semuanya dikontrol dari satu file ini.

---

## 📁 File `.env` vs `.env.example`

| File | Deskripsi | Commit ke Git? |
| :--- | :--- | :---: |
| `.env` | Konfigurasi **aktif** di server/laptop Anda. Berisi data sensitif. | ❌ **DILARANG** |
| `.env.example` | **Template** referensi. Berisi semua key dengan nilai kosong/default. | ✅ **WAJIB** |

**Quick Start:**
```bash
# Untuk developer baru yang clone project:
cp .env.example .env
php artisan setup
```

---

## 🏷️ APP_ENV — Mode Aplikasi

Variabel paling penting. Mengubah perilaku keseluruhan framework.

| Mode | Deskripsi | `APP_DEBUG` | Cache | Keamanan |
| :--- | :--- | :---: | :---: | :---: |
| **`local`** | Development di localhost. Error lengkap, tanpa cache. | `true` | ❌ Off | Normal |
| **`production`** | Server live. Performa maks, error disembunyikan. | `false` | ✅ On | 🔒 Maksimal |
| **`staging`** | Server pre-production. Mirip production, log lebih detail. | `false` | ✅ On | 🔒 Tinggi |
| **`testing`** | Saat menjalankan PHPUnit. Session/cache dinonaktifkan. | `true` | ❌ Off | Normal |
| **`maintenance`** | Halaman 503 untuk semua pengunjung. | — | — | — |
| **`payment`** | Halaman 402 (tagihan belum dibayar). | — | — | — |

### Contoh Penggunaan di Kode

```php
// Cek mode aplikasi
if (config('app.env') === 'production') {
    // Logic khusus production
}

// Menggunakan Config helper
if (\TheFramework\App\Core\Config::isProduction()) { ... }
if (\TheFramework\App\Core\Config::isLocal()) { ... }
if (\TheFramework\App\Core\Config::isTesting()) { ... }
```

---

## 📚 Daftar Lengkap Variabel Environment

### 🏷️ Application Core

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `APP_NAME` | string | `TheFramework` | Nama aplikasi (title, email, dll.) |
| `APP_ENV` | string | `production` | Mode aplikasi (lihat tabel di atas) |
| `APP_DEBUG` | bool | `false` | Tampilkan error detail di browser |
| `BASE_URL` | string | `http://localhost:8080` | URL dasar aplikasi |
| `APP_TIMEZONE` | string | `Asia/Jakarta` | Zona waktu PHP |

### 🌍 Localization

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `APP_LOCALE` | string | `id` | Bahasa default |
| `APP_FALLBACK_LOCALE` | string | `en` | Bahasa cadangan jika terjemahan tidak ada |
| `APP_SUPPORTED_LOCALES` | string | `en,id` | Daftar bahasa yang didukung (koma) |

### 🔐 Security Keys

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `APP_KEY` | string | — | 🔴 **WAJIB**. Kunci enkripsi utama. Generate via `php artisan setup` |
| `ENCRYPTION_KEY` | string | — | Kunci untuk library `defuse/php-encryption` |
| `API_SECRET_KEY` | string | — | Kunci rahasia untuk otentikasi API internal |
| `JWT_SECRET` | string | — | Secret key untuk JWT token |
| `JWT_TTL` | int | `60` | Masa berlaku JWT dalam menit |

### 🗄️ Database

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `DB_CONNECTION` | string | `mysql` | Driver database |
| `DB_HOST` | string | `127.0.0.1` | Alamat server database |
| `DB_PORT` | int | `3306` | Port database |
| `DB_NAME` | string | `the_framework_db` | Nama database |
| `DB_USER` | string | `root` | Username database |
| `DB_PASS` | string | — | Password database |
| `DB_CHARSET` | string | `utf8mb4` | Karakter set |
| `DB_COLLATION` | string | `utf8mb4_unicode_ci` | Collation |
| `DB_PREFIX` | string | — | Prefix tabel (opsional) |
| `DB_TIMEZONE` | string | `+07:00` | Timezone database |
| `DB_STRICT` | bool | `true` | MySQL strict mode |

### 🔴 Redis

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `REDIS_HOST` | string | `127.0.0.1` | Alamat server Redis |
| `REDIS_PASSWORD` | string | `null` | Password Redis |
| `REDIS_PORT` | int | `6379` | Port Redis |
| `REDIS_DB` | int | `0` | Nomor database Redis |

### 📋 Session

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `SESSION_DRIVER` | string | `file` | Penyimpanan session (`file`, `database`, `redis`) |
| `SESSION_LIFETIME` | int | `120` | Masa aktif session dalam menit |
| `SESSION_SECURE_COOKIE` | bool | `false` | Kirim cookie hanya via HTTPS |
| `SESSION_HTTP_ONLY` | bool | `true` | Blokir akses cookie dari JavaScript |
| `SESSION_SAME_SITE` | string | `Lax` | SameSite policy (`Lax`, `Strict`, `None`) |

### 📦 Cache

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `CACHE_DRIVER` | string | `file` | Driver cache (`file`, `array`, `redis`) |
| `CACHE_PREFIX` | string | `tf_cache_` | Prefix key cache |
| `CACHE_TTL` | int | `3600` | TTL default dalam detik |

### 📝 Logging

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `LOG_CHANNEL` | string | `daily` | Channel log (`single`, `daily`, `stack`) |
| `LOG_LEVEL` | string | `debug` | Level minimum dicatat |
| `LOG_MAX_DAYS` | int | `14` | Berapa hari log daily disimpan |

### 🛡️ Rate Limiter

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `RATE_LIMIT_ENABLED` | bool | `true` | Aktifkan rate limiter global |
| `RATE_LIMIT_MAX` | int | `100` | Maks request per IP per periode |
| `RATE_LIMIT_DECAY` | int | `120` | Periode reset dalam detik |
| `API_RATE_LIMIT_MAX` | int | `60` | Maks request API per IP per periode |
| `API_RATE_LIMIT_DECAY` | int | `60` | Periode reset API dalam detik |

### 🌐 CORS

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `CORS_ALLOWED_ORIGINS` | string | `*` | Domain yang diizinkan (koma) |
| `CORS_ALLOWED_METHODS` | string | `GET,POST,...` | HTTP methods yang diizinkan |
| `CORS_ALLOWED_HEADERS` | string | `Content-Type,...` | Headers yang diizinkan |
| `CORS_MAX_AGE` | int | `86400` | Preflight cache dalam detik |

### 📧 Email (SMTP)

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `MAIL_MAILER` | string | `smtp` | Transport mailer (`smtp`, `sendmail`, `log`) |
| `MAIL_HOST` | string | `smtp.mailtrap.io` | Server SMTP |
| `MAIL_PORT` | int | `2525` | Port SMTP |
| `MAIL_USERNAME` | string | — | Username SMTP |
| `MAIL_PASSWORD` | string | — | Password SMTP |
| `MAIL_ENCRYPTION` | string | `tls` | Enkripsi (`tls`, `ssl`, `null`) |
| `MAIL_TIMEOUT` | int | `30` | Timeout koneksi dalam detik |
| `MAIL_FROM` | string | `noreply@example.com` | Alamat pengirim default |
| `MAIL_FROM_NAME` | string | `My App Name` | Nama pengirim default |

### 📤 File Upload

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `UPLOAD_DIR` | string | `/uploads` | Direktori upload default |
| `UPLOAD_MAX_SIZE` | int | `10240` | Ukuran maks file dalam KB |
| `UPLOAD_AUTO_WEBP` | bool | `true` | Auto-convert gambar ke WebP |
| `UPLOAD_WEBP_QUALITY` | int | `80` | Kualitas konversi WebP (1-100) |

### 💳 Payment Gateway (Midtrans)

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `PAYMENT_GATEWAY` | string | `midtrans` | Gateway default |
| `MIDTRANS_SERVER_KEY` | string | — | Server Key dari dashboard Midtrans |
| `MIDTRANS_CLIENT_KEY` | string | — | Client Key dari dashboard Midtrans |
| `MIDTRANS_IS_PRODUCTION` | bool | `false` | Mode produksi Midtrans |

### 🛠️ Web Command Center

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `ALLOW_WEB_MIGRATION` | bool | `false` | Aktifkan akses ke `/_system/*` routes |
| `SYSTEM_ALLOWED_IPS` | string | `127.0.0.1` | IP whitelist (koma) |
| `SYSTEM_AUTH_USER` | string | — | Username Basic Auth |
| `SYSTEM_AUTH_PASS` | string | — | Password Basic Auth (bcrypt) |

### 🚧 Maintenance Mode

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `APP_MAINTENANCE` | bool | `false` | Aktifkan maintenance via .env |
| `MAINTENANCE_IPS` | string | — | IP bypass maintenance (koma) |
| `MAINTENANCE_SECRET` | string | — | Secret URL untuk bypass maintenance |

### 🐳 Docker / Railway (Deployment)

| Key | Tipe | Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `MYSQLHOST` | string | — | Otomatis dari Railway (fallback `DB_HOST`) |
| `MYSQLDATABASE` | string | — | Otomatis dari Railway (fallback `DB_NAME`) |
| `MYSQLUSER` | string | — | Otomatis dari Railway (fallback `DB_USER`) |
| `MYSQLPASSWORD` | string | — | Otomatis dari Railway (fallback `DB_PASS`) |
| `MYSQLPORT` | string | — | Otomatis dari Railway (fallback `DB_PORT`) |
| `PORT` | int | `8080` | Port HTTP untuk Docker/Railway |

---

## 📂 Config Files

Semua file config di folder `config/` secara otomatis di-load saat bootstrap. Bapak bisa mengakses nilainya dengan helper `config()`:

```php
// Dari config/app.php → key 'name'
config('app.name');          // "TheFramework"

// Dari config/database.php → key 'connections.mysql.host'
config('database.connections.mysql.host'); // "127.0.0.1"

// Dari config/mail.php → key 'from.address'
config('mail.from.address'); // "noreply@example.com"

// Dari .env langsung
config('APP_DEBUG');         // "true"
```

### Daftar Config Files

| File | Isi |
| :--- | :--- |
| `config/app.php` | Konfigurasi inti: nama, env, timezone, locale, session, cache, rate limit |
| `config/database.php` | Koneksi database MySQL dan Redis |
| `config/mail.php` | Konfigurasi SMTP mailer |
| `config/payment.php` | Konfigurasi payment gateway Midtrans |
| `config/security.php` | Web Command Center, CORS, dan kunci keamanan |
| `config/upload.php` | Konfigurasi upload file dan konversi WebP |

---

## ⚡ Mengakses Konfigurasi di Kode

```php
// ✅ Cara 1: Global Helper (DIREKOMENDASIKAN)
$appName = config('app.name');
$dbHost  = config('database.connections.mysql.host');

// ✅ Cara 2: Config Class (untuk fitur lanjutan)
use TheFramework\App\Core\Config;

$env = Config::environment();         // "local"
Config::isProduction();               // false
Config::isLocal();                    // true
Config::getBool('APP_DEBUG');         // true
Config::getInt('SESSION_LIFETIME');   // 120
Config::getArray('SYSTEM_ALLOWED_IPS'); // ['127.0.0.1']
Config::getOrFail('APP_KEY');         // Throw exception jika kosong

// ✅ Cara 3: Langsung dari .env (tidak disarankan)
$raw = $_ENV['APP_ENV'];
```

---

## 🔒 Best Practices

### ✅ DO's

1. **Selalu pakai `.env`** untuk data sensitif (password, API key, dll.)
2. **Generate `APP_KEY`** unik untuk setiap project: `php artisan setup`
3. **Set `APP_DEBUG=false`** di production
4. **Set `SESSION_SECURE_COOKIE=true`** di production (HTTPS)
5. **Set `ALLOW_WEB_MIGRATION=false`** setelah deployment selesai
6. **Gunakan `config()`** helper, bukan `$_ENV` langsung

### ❌ DON'Ts

1. ❌ **Jangan commit `.env`** ke Git
2. ❌ **Jangan pakai `CORS_ALLOWED_ORIGINS=*`** di production
3. ❌ **Jangan pakai `SYSTEM_ALLOWED_IPS=*`** di production
4. ❌ **Jangan hardcode** nilai konfigurasi di dalam PHP files

---

## 🎯 Contoh Konfigurasi per Skenario

### Development (Localhost)
```env
APP_ENV=local
APP_DEBUG=true
BASE_URL=http://localhost:8080
SESSION_SECURE_COOKIE=false
ALLOW_WEB_MIGRATION=true
SYSTEM_ALLOWED_IPS=127.0.0.1,*
```

### Production (Server Live)
```env
APP_ENV=production
APP_DEBUG=false
BASE_URL=https://myapp.com
SESSION_SECURE_COOKIE=true
ALLOW_WEB_MIGRATION=false
CORS_ALLOWED_ORIGINS=https://myapp.com,https://www.myapp.com
```

### Staging (Server Testing)
```env
APP_ENV=staging
APP_DEBUG=false
BASE_URL=https://staging.myapp.com
LOG_LEVEL=debug
ALLOW_WEB_MIGRATION=true
SYSTEM_ALLOWED_IPS=103.45.67.89
```

### Maintenance
```env
APP_ENV=maintenance
# ATAU
APP_MAINTENANCE=true
MAINTENANCE_IPS=103.45.67.89
```

---

<div align="center">

**Total: 70+ Environment Variables — All-In Configuration!** 🚀

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
