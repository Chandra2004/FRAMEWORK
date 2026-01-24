<div align="center">

<img src="private-uploads/public/favicon.ico" alt="The Framework Logo" width="100">

# The Framework v4.0.0

**Modern, Secure, and Hosting-Friendly PHP Framework**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)
[![Status](https://img.shields.io/badge/status-stable-green.svg)]()
[![Version](https://img.shields.io/badge/version-4.0.0-orange.svg)]()

<p align="center">
  <b>The Framework</b> adalah solusi Native PHP modern yang dirancang untuk kecepatan, keamanan, dan kemudahan deployment di semua jenis hosting (termasuk Shared Hosting Gratisan). Dibangun dengan arsitektur MVC yang solid namun tetap ringan.
</p>

</div>

---

## 📖 Dokumentasi & Panduan

Dokumentasi lengkap tersedia di folder `docs/`. Silakan baca panduan berikut untuk memahami cara kerja framework:

- **Mulai Cepat**:
  - [🏗️ Struktur Folder](docs/structure.md)
  - [🚀 Deployment & Hosting](docs/deployment.md) (Wajib baca untuk pengguna hosting gratis!)
  - [🛠️ Artisan Command Line](docs/artisan.md)

- **Fitur Utama**:
  - [🛣️ Routing](docs/routing.md)
  - [🗄️ Database & Query Builder](docs/database.md)
  - [🏗️ Migrations](docs/migrations.md)
  - [🎨 Views & Templating](docs/views.md)
  - [🛡️ Security (CSRF, XSS)](docs/security.md)

- **Topik Lanjut**:
  - [🌐 Environment Config](docs/environment.md)
  - [🛠️ Helper Functions](docs/helpers.md)
  - [🚀 Performance & Caching](docs/performance.md)
  - [🧪 Testing](docs/testing.md)

---

## 🚀 Fitur Unggulan (Version 4.0)

### 🛡️ Security First

- **WAF Terintegrasi**: Web Application Firewall bawaan untuk menangkal serangan umum.
- **CSRF Protection**: Otomatis aktif pada setiap form request.
- **Secure Headers**: HSTS, CSP, XSS-Protection out-of-the-box.
- **Encryption Service**: Menggunakan Defuse PHP Encryption standard industri.

### 🌐 Hosting Friendly (Killer Feature!)

Satu-satunya framework yang peduli nasib pengguna Shared Hosting tanpa SSH.

- **Web Command Center**: Jalankan migrasi, seeder, dan maintenance lewat browser.
- **Smart Log Viewer**: Baca error log langsung di web tanpa perlu FTP.
- **Auto-Discovery Seeder**: Upload file seeder, sistem akan menemukannya otomatis.

### ⚡ Developer Experience

- **Expressive Routing**: Syntax mirip Laravel, mendukung Regex & Parameters.
- **Artisan CLI**: Console command lengkap untuk generate code & maintenance.
- **Blade-like Engine**: Templating engine ringan tapi powerful.
- **Database Query Builder**: Fluent interface untuk database operations.

---

## 📚 Dokumentasi Lengkap

### 1. Instalasi & Setup

**Via Git:**

```bash
git clone https://github.com/chandra2004/the-framework.git
cd the-framework

composer install
composer dump-autoload
composer clear-cache

php artisan setup
php artisan serve
```

**Konfigurasi Environment:**
File `.env` otomatis dibuat oleh `php artisan setup`. Pastikan Anda mengisi:

- `DB_NAME`, `DB_USER`, `DB_PASS` (Koneksi Database)
- `APP_KEY` (Otomatis digenerate untuk enkripsi)

### 2. Artisan Command Line (CLI)

Gunakan perintah ini di terminal lokal atau VPS:

| Perintah                             | Fungsi                                   |
| :----------------------------------- | :--------------------------------------- |
| `php artisan serve`                  | Menjalankan local server di port 8080.   |
| `php artisan make:controller [Name]` | Membuat Controller baru.                 |
| `php artisan make:model [Name]`      | Membuat Model database baru.             |
| `php artisan migrate`                | Menjalankan migrasi database.            |
| `php artisan db:seed`                | Menjalankan seeder (urut waktu).         |
| `php artisan route:list`             | Melihat semua daftar URL yang terdaftar. |
| `php artisan optimize`               | Membersihkan cache aplikasi & OpCache.   |

### 3. Web Command Center (Untuk Shared Hosting)

Jika server Anda tidak punya akses SSH (Terminal), aktifkan fitur ini di `.env`:
`ALLOW_WEB_MIGRATION=true`

Lalu akses URL berikut di browser Anda:

| Fitur            | URL Endpoint                    | Deskripsi                       |
| :--------------- | :------------------------------ | :------------------------------ |
| **Migrate DB**   | `/_system/migrate?key=APP_KEY`  | Update struktur database.       |
| **Seed DB**      | `/_system/seed?key=APP_KEY`     | Isi data dummy otomatis.        |
| **Log Viewer**   | `/_system/logs?key=APP_KEY`     | Lihat error log aplikasi.       |
| **Optimize**     | `/_system/optimize?key=APP_KEY` | Reset cache agar update tampil. |
| **Route List**   | `/_system/routes?key=APP_KEY`   | Cek daftar URL yang aktif.      |
| **Health Check** | `/_system/health?key=APP_KEY`   | Cek permission folder storage.  |

> **Catatan:** `key` adalah nilai `APP_KEY` dari file `.env` Anda.

---

## 📂 Struktur Folder

```
The-Framework/
├── app/
│   ├── Console/      # Perintah Artisan Custom
│   ├── Controllers/  # Logika Aplikasi
│   ├── Models/       # Interaksi Database
│   └── Helpers/      # Fungsi Bantuan Global
├── database/
│   ├── migrations/   # File Struktur Database
│   └── seeders/      # File Data Dumy
├── public/           # Entry Point (index.php, css, js)
├── resources/
│   └── views/        # Template HTML
├── routes/
│   ├── web.php       # Rute Aplikasi Utama
│   └── system.php    # Rute Web Utilities
└── storage/          # Logs, Cache, Uploads
```

---

## ❤️ Kontribusi & Lisensi

Framework ini dikembangkan dengan ❤️ oleh **Chandra Tri Antomo**.
Dilisensikan di bawah **MIT License**. Silakan gunakan, modifikasi, dan distribusikan secara bebas.

**Happy Coding!** 🚀
