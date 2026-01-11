
<p align="center">
  <img src="private-uploads/public/favicon.ico" alt="The Framework Logo" width="100">
</p>

# The Framework

**The Framework** adalah PHP Framework modern yang ringan, aman, dan berkinerja tinggi. Dibangun dengan arsitektur MVC (Model-View-Controller) yang solid, framework ini dirancang untuk pengembangan aplikasi web yang cepat tanpa mengorbankan keamanan atau fleksibilitas.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

## 🚀 Fitur Unggulan

*   **Security First Core:**
    *   🛡️ **WAF (Web Application Firewall)** Terintegrasi.
    *   🔒 **CSRF Protection** otomatis pada setiap form.
    *   ✨ **Secure Headers** (HSTS, CSP, XSS-Protection) out-of-the-box.
    *   🔑 **Encryption Service** menggunakan Defuse PHP Encryption.
*   **Modern Database Layer:**
    *   ⚡ **Query Builder** yang ekspresif & fluent.
    *   💾 **Automatic Query Caching** (`->remember(3600)`).
    *   🔄 **Migration & Seeding** support (termasuk Alter Table & Views).
    *   🤝 **Eloquent-like Relationships** (hasOne, hasMany, belongsTo).
*   **Developer Experience:**
    *   🎨 **Blade Templating Engine** (powered by Illuminate/View).
    *   🛣️ **Expressive Routing** dengan dukungan Grouping & Middleware.
    *   📦 **Dependency Injection Container**.
    *   📂 **Private File Serving** untuk dokumen sensitif.

## 📋 Persyaratan Sistem

Pastikan server Anda memenuhi persyaratan berikut sebelum menginstal:

*   **PHP**: >= 8.3
*   **Composer**: Versi terbaru
*   **Extension PHP Wajib**:
    *   `pdo_mysql` (atau driver database lain)
    *   `mbstring`
    *   `openssl`
    *   `xml`
    *   `ctype`
    *   `json`

## 🛠️ Instalasi

1.  **Clone Repository:**
    ```bash
    git clone https://github.com/chandra2004/the-framework.git
    cd the-framework
    ```

2.  **Install Dependencies:**
    ```bash
    composer install
    ```

3.  **Konfigurasi Environment:**
    Salin file contoh `.env`:
    ```bash
    cp .env.example .env
    ```
    Sesuaikan konfigurasi database dan app key Anda di file `.env`.

4.  **Jalankan Aplikasi:**
    Framework ini dirancang untuk berjalan di Apache/Nginx, namun untuk development bisa menggunakan built-in server:
    ```bash
    php -S localhost:8000
    ```

## 📖 Dokumentasi

Dokumentasi lengkap tersedia di folder `docs/`. Berikut panduan cepat untuk memulai:

*   [Routing & Controllers](docs/routing.md)
*   [Database & Models](docs/database.md) - *Termasuk Query Builder & Relationships*
*   [Migrations & Schema](docs/migrations.md) - *Termasuk Views & Indexing*
*   [Security Features](docs/security.md)
*   [Views (Blade)](docs/views.md)

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan fork repository ini dan buat Pull Request untuk perbaikan bug atau fitur baru.

## 📄 Lisensi

Open-sourced software licensed under the [MIT license](LICENSE).
