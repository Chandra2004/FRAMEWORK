# THE-FRAMEWORK - Secure MVC Native PHP Framework

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Security](https://img.shields.io/badge/security-hardened-red)

**THE-FRAMEWORK** adalah framework PHP Native modern berbasis MVC (Model-View-Controller) yang dirancang dengan filosofi **"Security First"** dan **"Zero Overhead"**. Framework ini memberikan struktur kode yang bersih, aman, dan siap untuk produksi skala menengah ke atas.

---

## 📚 Dokumentasi Lengkap (Quick Links)

Jangan bingung! Kami telah menyediakan panduan lengkap untuk setiap aspek framework ini di folder `docs/`.

| Topik                    | Deskripsi & Link                                                                                                    |
| :----------------------- | :------------------------------------------------------------------------------------------------------------------ |
| **Routing & Controller** | Cara membuat URL, Controller, dan menangani Request/Response. <br> 👉 **[Baca Guide Routing](docs/routing.md)**     |
| **Database & ORM**       | Panduan Model, Query Builder, Migrations, Seeders, dan Locking. <br> 👉 **[Baca Guide Database](docs/database.md)** |
| **Security Features**    | Penjelasan WAF, CSRF, Secure Headers, dan proteksi lainnya. <br> 👉 **[Baca Guide Security](docs/security.md)**     |
| **Helpers & Utilities**  | Daftar fungsi bantuan global, Email, Upload, dan Payment. <br> 👉 **[Baca Guide Helpers](docs/helpers.md)**         |

---

## ✨ Fitur Unggulan

### 🛡️ Keamanan Kelas Enterprise

- **Built-in WAF (Web Application Firewall)**: Middleware yang secara proaktif memblokir serangan SQL Injection, XSS, dan Path Traversal.
- **Pessimistic Locking**: Mencegah _Race Condition_ pada transaksi kritis (misal: flash sale) menggunakan `lockForUpdate()`.
- **Secure by Default**: Cookie session otomatis terproteksi (HttpOnly, Secure), header keamanan (HSTS, X-Frame) otomatis aktif.

### ⚡ Performa Tinggi & Ringan

- **Lean Core**: Tidak ada bloatware. Framework hanya memuat apa yang dibutuhkan.
- **Lazy Loading**: Koneksi database hanya dibuka saat query pertama dijalankan.
- **Optimized Router**: Routing regex yang cepat dan efisien.

### 🛠️ Developer Experience (DX)

- **Artisan CLI**: Generator kode untuk Model, Controller, dll.
- **Blade Templating**: Menggunakan syntax template Laravel yang familiar.
- **Ready-to-Use Handlers**: Integrasi Email (SMTP), Upload (WebP support), dan Payment (Midtrans) siap pakai.

---

## 🚀 Instalasi & Setup

### Prasyarat

- PHP 8.0 atau lebih baru
- Composer
- Database MySQL/MariaDB

### Langkah Instalasi

1. **Clone Repository**

   ```bash
   git clone https://github.com/Chandra2004/FRAMEWORK.git
   cd FRAMEWORK
   ```

2. **Install Dependencies**

   ```bash
   composer install
   ```

3. **Setup Environment**
   Jalankan perintah ini untuk membuat file `.env` dan generate key rahasia:

   ```bash
   php artisan setup
   ```

4. **Jalankan Server**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di `http://localhost:8080`.

---

## ☁️ Setup di Google Project IDX

Framework ini sudah dilengkapi konfigurasi `.idx/dev.nix` untuk pengembangan instan di Cloud.

1. Buka [Project IDX](https://idx.google.com/).
2. Import repository GitHub ini.
3. IDX akan otomatis:
   - Menginstall **PHP 8.3**, **Composer**, dan **MySQL**.
   - Menginstall ekstensi VS Code (Blade, Intelephense, DB Client).
   - Menjalankan `php artisan serve` dengan preview window.

---

## 📂 Struktur Folder Modern

```
FRAMEWORK/
├── app/
│   ├── App/                # Core Framework (System Files)
│   ├── Config/             # Konfigurasi & Handler Eksternal
│   ├── Console/            # Perintah CLI (Artisan)
│   ├── Database/           # Base Classes untuk Migrasi
│   ├── Helpers/            # Fungsi Bantuan Global
│   ├── Http/               # Controllers, Requests, Middleware
│   └── Models/             # Model Database
├── bootstrap/              # Bootstrap Script
├── database/               # Migrations & Seeders Files
├── docs/                   # 📖 DOKUMENTASI LENGKAP ADA DI SINI
├── public/                 # Entry Point (index.php, CSS, JS)
├── resources/              # Views (Blade Templates)
├── routes/                 # Definisi URL (web.php)
├── storage/                # Cache, Logs, Session
└── .env                    # Konfigurasi Environment
```

---

## 🤝 Kontribusi & Support

Framework ini dikembangkan oleh **Chandra Tri A**.
Kami sangat terbuka dengan kontribusi! Silakan kirim Pull Request.

- **Email**: chandratriantomo123@gmail.com
- **Website**: [the-framework.ct.ws](https://www.the-framework.ct.ws)

---

_© 2024 TheFramework. Licensed under MIT._
