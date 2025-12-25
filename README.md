<p align="center">
  <img src="private-uploads/public/favicon.ico" width="100" alt="THE-FRAMEWORK Logo">
</p>

<h1 align="center">THE-FRAMEWORK</h1>

<p align="center">
  <strong>Secure MVC Native PHP Framework - Version 4.0</strong><br>
  <em>Filosofi "Security-First" dengan Performa "Zero-Overhead"</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-8892bf?style=for-the-badge&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/Version-4.0.0-orange?style=for-the-badge" alt="Version">
  <img src="https://img.shields.io/badge/Security-Hardened-red?style=for-the-badge&logo=dependabot" alt="Security">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

---

**THE-FRAMEWORK** adalah framework PHP Native modern berbasis MVC (Model-View-Controller) yang dirancang untuk pengembang yang menginginkan kontrol total tanpa _bloatware_. Framework ini memberikan struktur yang bersih, keamanan kelas enterprise, dan kecepatan yang melampaui framework berat lainnya.

## 🚀 Mengapa THE-FRAMEWORK?

| Fitur         | THE-FRAMEWORK                        | Framework Lain (Heavy)     |
| :------------ | :----------------------------------- | :------------------------- |
| **Kecepatan** | ⚡ **Ultra Fast** (Native Execution) | Terhambat Bloat Class      |
| **Keamanan**  | 🛡️ **WAF & Rate Limiting** Built-in  | Perlu Konfigurasi Tambahan |
| **Kapasitas** | 📦 **Lean Core** (< 5MB)             | Ratusan MB Dependency      |
| **DX**        | 🛠️ **Artisan CLI** Scaffolding       | Sering Terlalu Kompleks    |

---

## 📚 Dokumentasi Lengkap (Quick Links)

Kami menyediakan panduan lengkap untuk membantu Anda membangun aplikasi dari nol hingga siap produksi.

| Topik                       | Deskripsi & Link                                                                     |
| :-------------------------- | :----------------------------------------------------------------------------------- |
| **🚀 Tutorial Pemula**      | Panduan membuat Blog sederhana dalam 10 menit. [Baca di sini](docs/tutorial-blog.md) |
| **🛣️ Routing & Controller** | Cara menangani Request/Response & URL. [Baca di sini](docs/routing.md)               |
| **🗄️ Database & ORM**       | Model, Eager Loading (N+1 Fix), & Migrations. [Baca di sini](docs/database.md)       |
| **🛡️ Security Features**    | Penjelasan WAF, CSRF, & Secure Headers. [Baca di sini](docs/security.md)             |
| **✅ Validation System**    | Cara validasi input form dengan mudah. [Baca di sini](docs/validation.md)            |
| **⚙️ Artisan CLI**          | Generator kode untuk mempercepat development. [Baca di sini](docs/artisan.md)        |
| **⌛ Queue System**         | Menangani background job dengan Worker. [Baca di sini](docs/queue.md)                |

---

## ✨ Fitur Unggulan

### 🛡️ Keamanan Tanpa Kompromi

- **Built-in WAF (Web Application Firewall)**: Secara otomatis mendeteksi dan memblokir SQL Injection, XSS, dan Path Traversal.
- **Pessimistic Locking**: Fitur `lockForUpdate()` untuk mencegah _Race Condition_ pada transaksi finansial.
- **Auto-Hardened Headers**: Dilengkapi proteksi HSTS, CSP, dan X-Frame-Options secara default.

### ⚡ Performa Maksimal

- **Lazy Database Connection**: SQL hanya tersambung saat benar-benar dibutuhkan.
- **Optimized Router**: Menggunakan regex engine yang efisien untuk resolusi rute instan.
- **Route & Config Caching**: Mempercepat startup time di environment Production.

### 🛠️ Pengalaman Developer (DX)

- **Artisan CLI**: Satu perintah `php artisan make:crud` untuk men-generate Model, View, Controller, dan Services.
- **Powerful DI Container**: Mendukung Auto-wiring via Reflection API (Setara Laravel Container).
- **Integrated Handler**: SMTP Email, WebP Image Processing, dan Midtrans Payment Ready.

---

## � Instalasi

### Prasyarat

- PHP 8.3 atau lebih baru
- Composer
- MySQL/MariaDB

### Cara Setup

1. **Clone & Install**
   ```bash
   git clone https://github.com/Chandra2004/FRAMEWORK.git
   cd FRAMEWORK
   composer install
   ```
2. **Setup Env & Key**
   ```bash
   php artisan setup
   ```
3. **Run Server**
   ```bash
   php artisan serve
   ```
   Buka `http://localhost:8080` di browser Anda.

---

## 📂 Struktur Folder

```text
app/
├── App/                # Core Framework (System Files)
├── Http/               # Controllers, Requests, Middleware
├── Models/             # Model Database
├── Services/           # Business Logic Layer
bootstrap/              # Lifecycle & Error Handling
database/               # Migrations & Seeders
resources/              # Views (Blade Templates)
routes/                 # Definisi URL
```

---

## 🤝 Kontribusi & Dukungan

Dikembangkan dengan ❤️ oleh **Chandra Tri Antomo**. Kami sangat terbuka untuk Pull Request dan masukan dari komunitas.

- **Website**: [the-framework.ct.ws](https://www.the-framework.ct.ws)
- **Email**: chandratriantomo123@gmail.com

---

<p align="center">
  <i>© 2024 THE-FRAMEWORK. Licensed under MIT.</i>
</p>
