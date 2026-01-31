# 🌐 Konfigurasi Environment

The Framework menggunakan library `vlucas/phpdotenv` untuk mengelola konfigurasi sensitif. Jangan pernah menyimpan password atau API Key langsung di dalam kode (`.php`). Simpanlah di `.env`.

## File .env vs .env.example

- **`.env`**: File konfigurasi aktif di server/laptop Anda. **JANGAN DI-COMMIT KE GIT** karena berisi password rahasia.
- **`.env.example`**: Template konfigurasi. File ini **HARUS DI-COMMIT** agar developer lain tahu variabel apa saja yang dibutuhkan.

## Daftar Variabel Penting

| Key                      | Deskripsi                                                | Contoh Nilai            |
| :----------------------- | :------------------------------------------------------- | :---------------------- |
| `APP_NAME`               | Nama aplikasi.                                           | `The Framework`         |
| `APP_ENV`                | Mode aplikasi (lihat bagian **APP_ENV Modes** di bawah). | `local`                 |
| `APP_DEBUG`              | Menampilkan error detail (`true` untuk dev).             | `true`                  |
| `APP_KEY`                | Kunci enkripsi acak (Wajib diisi!).                      | `base64:xYz...`         |
| `APP_URL`                | URL dasar aplikasi.                                      | `http://127.0.0.1:8000` |
| `DB_CONNECTION`          | Driver database (saat ini support `mysql`).              | `mysql`                 |
| `DB_HOST`                | Alamat server database.                                  | `127.0.0.1`             |
| `DB_PORT`                | Port database.                                           | `3306`                  |
| `DB_NAME`                | Nama database (Gunakan `DB_NAME`).                       | `my_app`                |
| `DB_USER`                | Username database (Gunakan `DB_USER`).                   | `root`                  |
| `DB_PASS`                | Password database (Gunakan `DB_PASS`).                   | `secret`                |
| `SYSTEM_ALLOWED_IPS`     | Whitelist IP untuk Web Command Center.                   | `127.0.0.1,::1`         |
| `SYSTEM_AUTH_USER`       | Admin username untuk Web Command Center.                 | `admin`                 |
| `SYSTEM_AUTH_PASS`       | Admin password untuk Web Command Center.                 | `admin123`              |
| `ALLOW_WEB_MIGRATION`    | Switch ON/OFF fitur Web Command Center.                  | `false`                 |
| `MAIL_HOST`              | SMTP server host.                                        | `smtp.mailtrap.io`      |
| `MAIL_PORT`              | SMTP server port.                                        | `2525`                  |
| `MAIL_USERNAME`          | SMTP username.                                           | `user123`               |
| `MAIL_PASSWORD`          | SMTP password.                                           | `pass123`               |
| `MAIL_FROM`              | Email pengirim default.                                  | `no-reply@app.com`      |
| `MAIL_FROM_NAME`         | Nama pengirim default.                                   | `My App`                |
| `MIDTRANS_SERVER_KEY`    | API Server Key dari Midtrans.                            | `SB-Mid-server-...`     |
| `MIDTRANS_CLIENT_KEY`    | API Client Key dari Midtrans.                            | `SB-Mid-client-...`     |
| `MIDTRANS_IS_PRODUCTION` | Set `true` untuk mode produksi Midtrans.                 | `false`                 |

## APP_ENV Modes

Framework mendukung **4 mode aplikasi** yang dapat dikonfigurasi melalui variabel `APP_ENV` di file `.env`:

### 1. **local** (Development Mode)

Mode untuk pengembangan aplikasi di lingkungan lokal.

**Karakteristik:**

- Error ditampilkan secara detail untuk debugging
- Route caching dinonaktifkan (routes di-load on-the-fly)
- Asset serving dari folder `resources/` (fallback)
- Rate limiting lebih longgar
- Akses ke Web Command Center lebih mudah

**Kapan digunakan:**

- Saat development di localhost
- Saat debugging aplikasi
- Testing fitur baru

**Contoh:**

```env
APP_ENV=local
APP_DEBUG=true
```

### 2. **production** (Production Mode)

Mode untuk aplikasi yang sudah live di server produksi.

**Karakteristik:**

- Error tidak ditampilkan ke user (hanya log)
- Route caching aktif untuk performa maksimal
- Asset harus sudah di-compile ke folder `public/`
- Rate limiting ketat
- Keamanan maksimal (WAF, CSRF, XSS protection)

**Kapan digunakan:**

- Di server production yang diakses publik
- Setelah aplikasi siap deploy
- Untuk performa dan keamanan maksimal

**Contoh:**

```env
APP_ENV=production
APP_DEBUG=false
```

### 3. **maintenance** (Maintenance Mode)

Mode untuk menampilkan halaman maintenance kepada semua pengunjung.

**Karakteristik:**

- **Semua route** akan redirect ke halaman maintenance
- HTTP Status Code: **503 (Service Unavailable)**
- Halaman ditampilkan dari: `app/App/Internal/Views/errors/maintenance.blade.php`
- Aplikasi tidak dapat diakses sampai mode diubah

**Kapan digunakan:**

- Saat melakukan update database besar
- Saat deploy fitur baru yang membutuhkan downtime
- Saat maintenance server atau infrastruktur
- Emergency fixing critical bugs

**Contoh:**

```env
APP_ENV=maintenance
```

**Cara mengaktifkan:**

1. Edit file `.env`:
   ```env
   APP_ENV=maintenance
   ```
2. Semua visitor akan melihat halaman maintenance
3. Untuk menonaktifkan, ubah kembali ke `local` atau `production`

### 4. **payment** (Payment Required Mode)

Mode untuk menampilkan halaman payment reminder kepada semua pengunjung.

**Karakteristik:**

- **Semua route** akan redirect ke halaman payment
- HTTP Status Code: **402 (Payment Required)**
- Halaman ditampilkan dari: `app/App/Internal/Views/errors/payment.blade.php`
- Aplikasi tidak dapat diakses sampai mode diubah

**Kapan digunakan:**

- Saat pembayaran berlangganan/hosting belum dibayar
- Untuk mengingatkan client bahwa pembayaran diperlukan
- Sebagai "soft block" sebelum suspend aplikasi sepenuhnya
- SaaS subscription reminder

**Contoh:**

```env
APP_ENV=payment
```

**Cara mengaktifkan:**

1. Edit file `.env`:
   ```env
   APP_ENV=payment
   ```
2. Semua visitor akan melihat halaman payment reminder
3. Untuk menonaktifkan, ubah kembali ke `local` atau `production`

---

## Mengakses Konfigurasi

Anda bisa membaca nilai `.env` di mana saja dalam kode menggunakan Helper:

```php
use TheFramework\App\Config;

// Cara 1: Menggunakan $_ENV langsung
$debug = $_ENV['APP_DEBUG'];

// Cara 2: Menggunakan Helper Config (Disarankan)
$appName = Config::get('APP_NAME');

// Cara 3: Dengan nilai default jika kosong
$timezone = Config::get('APP_TIMEZONE', 'Asia/Jakarta');
```
