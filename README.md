# THE-FRAMEWORK - Secure MVC Native PHP Framework

## 📌 Pengenalan

**THE-FRAMEWORK** adalah framework PHP Native berbasis MVC (Model-View-Controller) yang dirancang dengan fokus pada **Keamanan (Security First)**, **Performa (Lean Core)**, dan **Efisiensi Developer**.

Tidak seperti framework raksasa, framework ini ringan namun dilengkapi fitur keamanan kelas enterprise seperti **Built-in WAF (Web Application Firewall)**, **Anti-Race Condition Logic**, dan **Secure Session Management** secara default.

### ✨ Fitur Utama

- **Security First**:
  - 🛡️ **Built-in WAF Middleware**: Memblokir serangan SQL Injection, XSS, dan Path Traversal sebelum masuk Controller.
  - 🔒 **Pessimistic Locking**: Metode `lockForUpdate()` untuk mencegah race condition (cocok untuk aplikasi tiket/stok).
  - 🔑 **Secure Headers**: Security headers otomatis (X-Frame, XSS-Protection, dll) di core bootstrap.
- **Developer Experience**:
  - 🛠️ **Artisan CLI**: Generator untuk Model, Controller, Middleware, dan Seeder.
  - 🌪️ **Blade Templating**: Menggunakan engine template populer Laravel Blade.
  - 📦 **Modern Handlers**: Helper siap pakai untuk Upload (support WebP/MIME-check), Email (PHPMailer wrapper), dan Payment (Midtrans wrapper).
- **Architecture**:
  - 🏗️ **PSR-4 Autoloading**: Struktur namespace modern.
  - 🗄️ **Database Migrations & Seeders**: Manajemen skema database terkontrol versioning.

## 🚀 Instalasi

### Prasyarat

- PHP 8.0+
- Composer
- MySQL/MariaDB

### Langkah-langkah

1. **Clone Proyek**:

   ```bash
   git clone https://github.com/Chandra2004/FRAMEWORK.git
   cd FRAMEWORK
   ```

2. **Install Dependensi & Setup**:

   ```bash
   composer install
   php artisan setup
   ```

   _Perintah `setup` otomatis membuat file `.env` dan `API Key`._

3. **Jalankan Server**:
   ```bash
   php artisan serve
   ```
   Akses di `http://localhost:8080`.

## 📂 Struktur Direktori (Modernized)

```
FRAMEWORK/
├── app/
│   ├── App/                # Core Framework (Jantung Sistem)
│   │   ├── Database.php    # Lazy Loading PDO Wrapper
│   │   ├── Router.php      # High Performance Router
│   │   └── ...
│   ├── Config/             # Configuration & External Services
│   │   ├── EmailHandler.php
│   │   ├── UploadHandler.php  # Secure Upload (WebP support)
│   │   └── PaymentHandler.php # Midtrans Wrapper
│   ├── Console/            # CLI Commands (Artisan)
│   ├── Database/           # Base Migration & Seeder Classes
│   ├── Helpers/            # Helper Functions (Utility only)
│   ├── Http/
│   │   ├── Controllers/    # Application Logic
│   │   ├── Requests/       # Form Validation
│   │   └── Services/       # Business Logic Layer
│   ├── Middleware/         # HTTP Middleware (CSRF, Auth, WAF)
│   └── Models/             # Database Models (extends Core Model)
├── bootstrap/              # App Bootstrapper
├── database/               # Database Files
│   ├── migrations/         # Schema Definitions
│   └── seeders/            # Dummy Data Generators
├── docs/                   # Full Documentation
├── public/                 # Public Entry Point (Assets)
├── resources/
│   └── views/              # Blade Templates
├── routes/
│   └── web.php             # Route Definitions
├── storage/                # Logs, Cache, Session Files
├── vendor/                 # Composer Dependencies
├── .env                    # Environment Variables
├── artisan                 # CLI Entry Point
└── index.php               # Web Entry Point
```

## 🔧 Artisan Commands

```bash
php artisan make:model Product     # Membuat Model baru
php artisan make:controller Home   # Membuat Controller
php artisan make:middleware Auth   # Membuat Middleware
php artisan serve                  # Menjalankan Server
php artisan migrate                # Menjalankan Migrasi
php artisan db:seed                # Menjalankan Seeder
```

## 📖 Dokumentasi Lengkap

Lihat folder `docs/` untuk panduan detail:

- [Routing & Controller](docs/routing.md)
- [Database & Models](docs/database.md)
- [Security Features](docs/security.md)
- [Helpers & Utilities](docs/helpers.md)

## 🤝 Kontribusi

Project ini Open Source. Silakan fork dan pull request!
Info kontak: chandratriantomo123@gmail.com
Web: https://www.the-framework.ct.ws
