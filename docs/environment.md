# 🌐 Konfigurasi Environment

The Framework menggunakan library `vlucas/phpdotenv` untuk mengelola konfigurasi sensitif. Jangan pernah menyimpan password atau API Key langsung di dalam kode (`.php`). Simpanlah di `.env`.

## File .env vs .env.example

- **`.env`**: File konfigurasi aktif di server/laptop Anda. **JANGAN DI-COMMIT KE GIT** karena berisi password rahasia.
- **`.env.example`**: Template konfigurasi. File ini **HARUS DI-COMMIT** agar developer lain tahu variabel apa saja yang dibutuhkan.

## Daftar Variabel Penting

| Key                   | Deskripsi                                    | Contoh Nilai        |
| :-------------------- | :------------------------------------------- | :------------------ |
| `APP_ENV`             | Mode aplikasi (`local` atau `production`).   | `local`             |
| `APP_DEBUG`           | Menampilkan error detail (`true` untuk dev). | `true`              |
| `APP_KEY`             | Kunci enkripsi acak (Wajib diisi!).          | `base64:xYz...`     |
| `APP_URL`             | URL dasar aplikasi.                          | `http://mysite.com` |
| `DB_CONNECTION`       | Driver database (saat ini support `mysql`).  | `mysql`             |
| `DB_HOST`             | Alamat server database.                      | `127.0.0.1`         |
| `DB_PORT`             | Port database.                               | `3306`              |
| `DB_DATABASE`         | Nama database.                               | `my_app`            |
| `DB_USERNAME`         | Username database.                           | `root`              |
| `DB_PASSWORD`         | Password database.                           | `secret`            |
| `ALLOW_WEB_MIGRATION` | Switch ON/OFF fitur Web Command Center.      | `false`             |

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
