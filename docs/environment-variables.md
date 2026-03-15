# Panduan Konfigurasi Environment (.env)

File `.env` digunakan untuk mengatur konfigurasi lingkungan dari aplikasi The Framework. File ini **TIDAK BOLEH** di-commit ke Git (sudah masuk dalam `.gitignore`) karena berisi kredensial sensitif seperti password database dan API Keys.

Berikut adalah penjelasan lengkap untuk setiap variabel yang ada di file `.env.example` dan `.env`.

---

## 🏷️ APPLICATION CORE

Variabel untuk pengaturan inti aplikasi.

- `APP_NAME`: Nama aplikasi Anda (ditampilkan di title, email, dll.)
- `APP_ENV`: Mode aplikasi. Nilai yang didukung:
  - `local` → Development di localhost (error detail, tanpa cache)
  - `production` → Server live (error disembunyikan, cache aktif, keamanan maks)
  - `staging` → Server testing sebelum go-live (mirip production, tapi log lebih detail)
  - `testing` → Khusus saat menjalankan PHPUnit (database terpisah, tanpa session)
  - `maintenance` → Menampilkan halaman 503 ke semua pengunjung
  - `payment` → Menampilkan halaman 402 (tagihan belum dibayar) ke pengunjung
- `APP_DEBUG`: Tampilkan error detail di browser? Set `false` di production!
- `BASE_URL`: URL dasar aplikasi (tanpa trailing slash).
- `APP_TIMEZONE`: Zona waktu PHP (list: https://www.php.net/manual/en/timezones.php).

---

## 🌍 LOCALIZATION (Multi-Bahasa)

- `APP_LOCALE`: Bahasa default aplikasi.
- `APP_FALLBACK_LOCALE`: Bahasa cadangan jika file terjemahan tidak ditemukan.
- `APP_SUPPORTED_LOCALES`: Daftar bahasa yang didukung (pisahkan dengan koma).

---

## 🔐 SECURITY KEYS

- `APP_KEY`: Kunci enkripsi utama (generate via: `php artisan setup`). Format: `base64:xxx...`
- `ENCRYPTION_KEY`: Kunci untuk library defuse/php-encryption (opsional).
- `API_SECRET_KEY`: Kunci rahasia untuk otentikasi API internal.
- `JWT_SECRET`: JWT Secret untuk token-based API authentication.
- `JWT_TTL`: Masa berlaku JWT token dalam menit (default: 60 = 1 jam).

---

## 🗄️ DATABASE

- `DB_CONNECTION`: Driver database (saat ini didukung: `mysql`).
- `DB_HOST`: Alamat server database (contoh: `127.0.0.1`).
- `DB_PORT`: Port database (default MySQL: `3306`).
- `DB_NAME`: Nama database.
- `DB_USER`: Username database.
- `DB_PASS`: Password database.
- `DB_CHARSET`: Karakter set (contoh: `utf8mb4`).
- `DB_COLLATION`: Collation (contoh: `utf8mb4_unicode_ci`).
- `DB_PREFIX`: Prefix tabel (opsional, berguna jika berbagi database).
- `DB_TIMEZONE`: Timezone database.
- `DB_STRICT`: Strict mode MySQL (`true` = direkomendasikan).

---

## 🔴 REDIS (Opsional)

Untuk cache, session, atau antrean (queue).
- `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `REDIS_DB`.

---

## 📋 SESSION

- `SESSION_DRIVER`: Driver penyimpanan session (`file`, `database`, `redis`).
- `SESSION_LIFETIME`: Masa aktif session dalam menit.
- `SESSION_SECURE_COOKIE`: Kirim cookie hanya via HTTPS? (`true` di production).
- `SESSION_HTTP_ONLY`: Cookie hanya bisa diakses via HTTP (mencegah XSS).
- `SESSION_SAME_SITE`: SameSite policy (`Lax`, `Strict`, `None`).

---

## 📦 CACHE & LOGGING

- `CACHE_DRIVER`: Driver cache (`file`, `array`, `redis`).
- `CACHE_PREFIX`: Prefix key cache.
- `CACHE_TTL`: Time-to-live cache bawaan dalam detik.
- `LOG_CHANNEL`: Channel log (`single`, `daily`, `stack`).
- `LOG_LEVEL`: Level log (`debug`, `info`, `warning`, `error`, dll).
- `LOG_MAX_DAYS`: Jumlah hari file log `daily` disimpan.

---

## 🛡️ RATE LIMITER API & CORS

- `RATE_LIMIT_ENABLED`: Aktifkan rate limiter global.
- `RATE_LIMIT_MAX`: Maksimum request per alamat IP.
- `RATE_LIMIT_DECAY`: Waktu pemulihan (reset) limit dalam detik.
- `API_RATE_LIMIT_MAX` & `API_RATE_LIMIT_DECAY`: Rate limit spesifik API.
- `CORS_ALLOWED_ORIGINS`: Domain yang diizinkan memanggil API (`*` untuk semua).
- `CORS_ALLOWED_METHODS`, `CORS_ALLOWED_HEADERS`, `CORS_MAX_AGE`: Pengaturan policy CORS.

---

## 📧 EMAIL (SMTP)

Pengaturan kredensial pengiriman Email/SMTP:
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_TIMEOUT`, `MAIL_FROM`, `MAIL_FROM_NAME`.

---

## 📤 FILE UPLOAD & PAYMENT

- `UPLOAD_DIR`: Direktori upload (direkomendasikan di luar `public_html`).
- `UPLOAD_MAX_SIZE`: Max size upload dalam KB.
- `UPLOAD_AUTO_WEBP`: Konversi otomatis ke gambar WebP (`true`/`false`).
- `UPLOAD_WEBP_QUALITY`: Kualitas WebP konversi.
- `PAYMENT_GATEWAY`: Gateway pembayaran (`midtrans`).
- `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`: Kredensial API Payment Gateway Midtrans.

---

## 🛠️ WEB COMMAND CENTER & MAINTENANCE

- `ALLOW_WEB_MIGRATION`: Mengaktifkan route system Web Artisan (`/_system/`). **Dimatikan di production!**
- `SYSTEM_ALLOWED_IPS`: Whitelist IP untuk mengakses kontrol Web Artisan.
- `SYSTEM_AUTH_USER`, `SYSTEM_AUTH_PASS`: Kredensial basic Auth untuk rute system.
- `APP_MAINTENANCE`: Aktifkan mode perbaikan secara manual.
- `MAINTENANCE_IPS`: Bypass IP developer saat maintenance.
- `MAINTENANCE_SECRET`: Bypass secret URL.

---

## 🐳 CLOUD DEPLOYMENT (Docker/Railway)

Platform SaaS Cloud seperti Railway menyediakan environment variables secara otomatis. Jika koneksi database dari file `.env` kosong, The Framework akan secara otomatis melakukan fallback membaca dari server host menggunakan identifier standar ini:
- `MYSQLHOST`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLPORT`, `PORT`
