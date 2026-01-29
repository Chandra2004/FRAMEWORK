# ⚡ 5-Minute Quick Start

Ingin mencoba The Framework secepat kilat? Ikuti langkah-langkah di bawah ini.

---

## 🏎️ Langkah 1: Download & Install

Pastikan Anda sudah menginstal **Composer** dan **PHP 8.3**.

```bash
# Clone
git clone https://github.com/chandra2004/the-framework.git my-app
cd my-app

# Install Dependensi
composer install
```

---

## 🛠️ Langkah 2: Persiapan Cepat

Gunakan perintah `setup` untuk mengotomatiskan pembuatan `.env`, generate `APP_KEY`, dan database.

```bash
php artisan setup
```

---

## 🚀 Langkah 3: Jalankan!

Jalankan server built-in PHP:

```bash
php artisan serve
```

Buka [http://localhost:8080](http://localhost:8080) di browser Anda. **Selesai!**

---

## 📂 Apa Selanjutnya?

| Tujuan                   | Halaman Petunjuk                  |
| :----------------------- | :-------------------------------- |
| **Membuat Halaman Baru** | [Routing & View](routing.md)      |
| **Mengambil Data DB**    | [Query Builder](query-builder.md) |
| **Membuat Model**        | [ORM & Model](orm.md)             |
| **Amankan Aplikasi**     | [Security Guide](security.md)     |

---

## 📦 Contoh Kode "Hello World"

Buka `routes/web.php` dan tambahkan ini:

```php
Router::get('/halo/{nama}', function($nama) {
    return "Halo, " . Helper::e($nama) . "! Selamat datang di The Framework.";
});
```

Akses: `http://localhost:8080/halo/Chandra`
