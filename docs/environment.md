# 🌐 Konfigurasi Environment

The Framework menggunakan library `vlucas/phpdotenv` untuk mengelola konfigurasi sensitif. Jangan pernah menyimpan password atau API Key langsung di dalam kode (`.php`). Simpanlah di `.env`.

## File .env vs .env.example

- **`.env`**: File konfigurasi aktif di server/laptop Anda. **JANGAN DI-COMMIT KE GIT** karena berisi password rahasia.
- **`.env.example`**: Template konfigurasi. File ini **HARUS DI-COMMIT** agar developer lain tahu variabel apa saja yang dibutuhkan.

## Daftar Variabel Penting

| Key                      | Deskripsi                                    | Contoh Nilai            |
| :----------------------- | :------------------------------------------- | :---------------------- |
| `APP_NAME`               | Nama aplikasi.                               | `The Framework`         |
| `APP_ENV`                | Mode aplikasi (`local` atau `production`).   | `local`                 |
| `APP_DEBUG`              | Menampilkan error detail (`true` untuk dev). | `true`                  |
| `APP_KEY`                | Kunci enkripsi acak (Wajib diisi!).          | `base64:xYz...`         |
| `APP_URL`                | URL dasar aplikasi.                          | `http://127.0.0.1:8000` |
| `DB_CONNECTION`          | Driver database (saat ini support `mysql`).  | `mysql`                 |
| `DB_HOST`                | Alamat server database.                      | `127.0.0.1`             |
| `DB_PORT`                | Port database.                               | `3306`                  |
| `DB_NAME`                | Nama database (Gunakan `DB_NAME`).           | `my_app`                |
| `DB_USER`                | Username database (Gunakan `DB_USER`).       | `root`                  |
| `DB_PASS`                | Password database (Gunakan `DB_PASS`).       | `secret`                |
| `SYSTEM_ALLOWED_IPS`     | Whitelist IP untuk Web Command Center.       | `127.0.0.1,::1`         |
| `SYSTEM_AUTH_USER`       | Admin username untuk Web Command Center.     | `admin`                 |
| `SYSTEM_AUTH_PASS`       | Admin password untuk Web Command Center.     | `admin123`              |
| `ALLOW_WEB_MIGRATION`    | Switch ON/OFF fitur Web Command Center.      | `false`                 |
| `MAIL_HOST`              | SMTP server host.                            | `smtp.mailtrap.io`      |
| `MAIL_PORT`              | SMTP server port.                            | `2525`                  |
| `MAIL_USERNAME`          | SMTP username.                               | `user123`               |
| `MAIL_PASSWORD`          | SMTP password.                               | `pass123`               |
| `MAIL_FROM`              | Email pengirim default.                      | `no-reply@app.com`      |
| `MAIL_FROM_NAME`         | Nama pengirim default.                       | `My App`                |
| `MIDTRANS_SERVER_KEY`    | API Server Key dari Midtrans.                | `SB-Mid-server-...`     |
| `MIDTRANS_CLIENT_KEY`    | API Client Key dari Midtrans.                | `SB-Mid-client-...`     |
| `MIDTRANS_IS_PRODUCTION` | Set `true` untuk mode produksi Midtrans.     | `false`                 |

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
