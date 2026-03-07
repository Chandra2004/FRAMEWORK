# 🚀 Artisan CLI Engine (v5.0.1 - Premium CLI)

Artisan adalah pusat komando baris perintah (CLI) yang disertakan dengan **The Framework**. Didesain untuk memberikan pengalaman developer (DX) yang luar biasa, Artisan membantu mengotomatisasi tugas-tugas repetitif dengan tampilan visual yang premium dan intuitif.

---

## 🎨 Keunggulan Visual (Premium DX)

Setiap perintah Artisan dibangun di atas `BaseCommand.php` yang menawarkan:

- **Rich Level Logging**: Indikator visual untuk `SUCCESS` (green), `INFO` (blue), `WARN` (yellow), dan `ERROR` (red).
- **Interactive Assistance**: Sarana bantuan cerdas (levenshtein distance) yang memberikan saran perintah jika Anda salah ketik.
- **Premium Tables**: Render tabel data yang rapi dan artistik (digunakan di `route:list`).
- **Interactive Prompts**: Kemudahan mengambil input (`ask`) dan konfirmasi (`confirm`) dari user.
- **Auto-Alias Tinker**: Mengakses Model tanpa perlu menulis namespace lengkap.

---

## 🏗️ Daftar Perintah Utama

Gunakan `php artisan list` untuk melihat semua perintah secara real-time. Berikut adalah daftar lengkap berdasarkan kategori:

### 🌟 GENERAL (Perintah Utama)

| Perintah      | Fungsi                                                               |
| :------------ | :------------------------------------------------------------------- |
| `serve`       | Jalankan server pengembangan dengan deteksi IP & Auto-Port.          |
| `setup`       | Inisialisasi awal aplikasi (Generasi ENV, APP_KEY, & Security).      |
| `optimize`    | Mengoptimasi performa (Cache rute, config, dan file) untuk produksi. |
| `tinker`      | Membuka REPL Interaktif Premium untuk debugging kode.                |
| `test`        | Jalankan unit & feature testing dengan laporan visual yang cantik.   |
| `down` / `up` | Mengatur mode pemeliharaan (Maintenance Mode).                       |
| `env`         | Menampilkan environment aplikasi saat ini.                           |

---

### 🔨 MAKE (Sistem stub & Generator)

Sistem Generator kami menggunakan **Stub Premium** yang menghasilkan kode siap pakai.

| Perintah          | Deskripsi                                                              |
| :---------------- | :--------------------------------------------------------------------- |
| `make:controller` | Membuat controller baru (v5 mendukung `--resource`).                   |
| `make:model`      | Membuat model (Opsi `-m` untuk menyertakan migrasi).                   |
| `make:crud`       | **Killer Feature**: Generate modul CRUD lengkap (Controller s/d View). |
| `make:migration`  | Membuat file migrasi database baru.                                    |
| `make:request`    | Membuat class Request untuk validasi form.                             |
| `make:service`    | Membuat class Service untuk logika bisnis.                             |
| `make:repository` | Membuat class Repository untuk abstraksi query.                        |
| `make:middleware` | Membuat class Middleware baru.                                         |
| `make:view`       | Membuat file Blade view baru.                                          |
| `make:mail`       | Membuat class Mail untuk pengiriman email.                             |
| `make:job`        | Membuat class Job untuk antrean (_Queue_).                             |
| `make:provider`   | Membuat Service Provider baru.                                         |
| `make:test`       | Membuat file test baru untuk PHPUnit.                                  |

---

### 🗄️ DATABASE & MIGRATE

| Perintah           | Deskripsi                                                 |
| :----------------- | :-------------------------------------------------------- |
| `migrate`          | Jalankan migrasi database dengan status tracking.         |
| `migrate:rollback` | Membatalkan batch migrasi terakhir.                       |
| `migrate:fresh`    | Hapus SEMUA tabel lalu jalankan ulang migrasi.            |
| `migrate:reset`    | Rollback SEMUA migrasi database.                          |
| `migrate:status`   | Menampilkan status setiap migrasi (Ran/Pending).          |
| `db:seed`          | Mengisi database dengan data dummy dari folder `seeders`. |

---

### 🚀 OPTIMIZATION & UTILITIES

| Perintah                 | Deskripsi                                                             |
| :----------------------- | :-------------------------------------------------------------------- |
| `optimize:clear`         | **Hard Reset**: Hapus SEMUA cache, compiled views, logs, dan session. |
| `route:cache` / `clear`  | Mengelola cache rute aplikasi.                                        |
| `config:cache` / `clear` | Mengelola cache konfigurasi `.env`.                                   |
| `view:clear`             | Hapus semua compiled Blade views dari cache.                          |
| `cache:clear`            | Hapus semua file cache aplikasi.                                      |
| `asset:publish`          | Salin asset dari `resources` ke `public`.                             |
| `storage:link`           | Membuat symbolic link untuk akses file publik.                        |
| `queue:work`             | Memulai proses pengerjaan antrean (Worker).                           |

---

## ⚡ Pro-Tips Penggunaan

### 1. Hard Reset Storage

Jika Anda menemui masalah aneh dengan cache atau session, gunakan perintah "Sakti":

```bash
php artisan optimize:clear
```

Ini akan membersihkan views, logs, cache, hingga session yang menggantung.

### 2. Otomasi CRUD

Gunakan `make:crud` untuk mempercepat development. Misal Anda ingin membuat fitur Produk:

```bash
php artisan make:crud Product
```

Sistem akan membuatkan Model, Migration, Controller, Request, dan 4 file Views (`index`, `create`, `edit`, `show`) secara instan.

### 3. Debugging dengan Tinker

Di dalam Tinker, semua Model di `app/Models` di-alias secara otomatis:

```php
>>> User::where('id', 1)->first();
```

---

## 📝 Membuat Perintah Kustom

Anda dapat membuat perintah sendiri dengan mewarisi class `BaseCommand`. Simpan di `app/Console/Commands/`.

```php
<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class HaloDunia extends BaseCommand {
    public function getName(): string { return 'halo:dunia'; }
    public function getDescription(): string { return 'Perintah percobaan pertama'; }

    public function handle(array $args): void {
        $this->success("Command executed! Perintah kustom Anda berhasil.");

        if ($this->confirm("Apakah Anda ingin melanjutkan?")) {
            $name = $this->ask("Masukkan nama Anda:");
            $this->info("Selamat datang, $name!");
        }
    }
}
```

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
