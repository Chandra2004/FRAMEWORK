# 🚀 Artisan CLI Engine (v5.0.2 - Premium CLI)

Artisan adalah pusat komando baris perintah (CLI) yang disertakan dengan **The Framework**. Didesain untuk memberikan pengalaman developer (DX) yang luar biasa, Artisan membantu mengotomatisasi tugas-tugas repetitif dengan tampilan visual yang premium dan intuitif.

---

## 🎨 Keunggulan Visual (Premium DX)

Setiap perintah Artisan dibangun di atas `BaseCommand.php` yang menawarkan:
- **Rich Level Logging**: Indikator visual untuk `SUCCESS` (green), `INFO` (blue), `WARN` (yellow), dan `ERROR` (red).
- **Interactive Assistance**: Sarana bantuan cerdas (levenshtein distance) yang memberikan saran perintah jika Anda salah ketik.
- **Smart Formatting**: Tabel data yang rapi untuk rute dan skema database.
- **Auto-Alias Tinker**: Mengakses Model tanpa perlu menulis namespace lengkap.

---

## 🏗️ Daftar Perintah Utama

Gunakan `php artisan list` untuk melihat semua perintah. Berikut adalah ringkasan berdasarkan kategori:

### 🌟 GENERAL (Perintah Utama)

| Perintah | Fungsi |
| :--- | :--- |
| `serve` | Menjalankan server pengembangan dengan deteksi IP & Auto-Port. |
| `setup` | Inisialisasi awal aplikasi (Generasi ENV, APP_KEY, & Security). |
| `optimize` | Mengoptimasi performa (Cache rute, config, dan file) untuk produksi. |
| `tinker` | Membuka REPL Interaktif untuk debugging kode secara real-time. |
| `test` | Menjalankan unit & feature testing dengan laporan visual yang cantik. |

---

### 🔨 MAKE (Sistem stub & Generator)

Sistem Generator kami menggunakan **Stub Premium** untuk memastikan kode Anda mengikuti standar arsitektur terbaru.

| Perintah | Deskripsi |
| :--- | :--- |
| `make:controller` | Membuat controller baru (Gunakan `--resource` untuk CRUD lengkap). |
| `make:model` | Membuat model (Opsi `-m` untuk sekalian membuat file migrasi). |
| `make:migration` | Membuat file migrasi database baru. |
| `make:crud` | **Killer Feature**: Generate Controller + Model + Request + Views + Routes sekaligus. |
| `make:service` | Membuat class Service baru untuk logika bisnis (v5.0.2). |
| `make:middleware` | Membuat class Middleware baru. |
| `make:job` | Membuat class Job untuk antrean (*Queue*). |
| `make:repository`| Membuat class Repository untuk abstraksi query database. |

---

### 🗄️ DATABASE & MIGRATE

| Perintah | Deskripsi |
| :--- | :--- |
| `migrate` | Menjalankan migrasi database yang belum tereksekusi. |
| `migrate:rollback`| Membatalkan batch migrasi terakhir. |
| `migrate:fresh` | Menghapus semua tabel dan menjalankan ulang seluruh migrasi dari nol. |
| `db:seed` | Mengisi database dengan data dummy dari folder `seeders`. |

---

### 🛣️ ROUTE & CONFIG

| Perintah | Deskripsi |
| :--- | :--- |
| `route:list` | Menampilkan daftar seluruh rute aktif dalam tabel premium. |
| `route:cache` | Membuat file cache rute untuk performa request yang lebih cepat. |
| `config:cache` | Meng-cache file `.env` ke dalam array PHP agar akses konfigurasi instan. |

---

## ⚡ Pro-Tips Penggunaan

### 1. Mempercepat Setup Awal
Saat baru men-clone project dari Git, Bapak tidak perlu melakukan setup satu-per-satu. Cukup jalankan:
```bash
php artisan setup
```
Artisan akan otomatis mendeteksi jika `.env` belum ada, membuatnya, dan men-generate `APP_KEY` yang aman.

### 2. Debugging di Tinker
Di dalam Tinker, Bapak bisa langsung memanggil model tanpa namespace:
```php
>>> User::count();
=> 150
```

### 3. Otomatisasi Publikasi Asset
Jika Bapak baru saja mengunduh framework atau mengubah file di `resources/css` atau `resources/js`, gunakan:
```bash
php artisan asset:publish
```
Perintah ini akan menyinkronkan file resources ke folder `public/assets` agar bisa diakses oleh browser.

---

## 📝 Tips Membuat Perintah Kustom
Bapak bisa membuat perintah sendiri dengan mewarisi class `BaseCommand`.
```php
namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class HaloDunia extends BaseCommand {
    protected $name = 'halo:dunia';
    protected $description = 'Perintah percobaan pertama saya';

    public function handle($args) {
        $this->success("Halo Pak! Perintah kustom berhasil dijalankan.");
    }
}
```

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
