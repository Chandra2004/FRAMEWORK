# 🛠️ Artisan Console (Command Line Interface)

**Artisan** adalah antarmuka command-line yang powerful untuk The Framework. Ia menyediakan banyak perintah bermanfaat untuk mempercepat pengembangan.

---

## 📋 Daftar Isi

1.  [Cara Menggunakan](#cara-menggunakan)
2.  [Daftar Perintah Lengkap](#daftar-perintah-lengkap)
    - [General](#general)
    - [Make (Generator)](#make-generator)
    - [Database & Migration](#database--migration)
    - [Routing](#routing)
    - [Config & Optimization](#config--optimization)
    - [Queue & Asset](#queue--asset)
3.  [Membuat Command Sendiri](#membuat-command-sendiri)

---

## Cara Menggunakan

Buka terminal di root folder project, lalu jalankan:

```bash
php artisan list
```

Ini akan menampilkan daftar semua perintah yang tersedia, dikelompokkan per kategori.

---

## Daftar Perintah Lengkap

Berikut adalah penjelasan detail untuk setiap perintah.

### General

| Perintah | Deskripsi                                                      |
| :------- | :------------------------------------------------------------- |
| `serve`  | Menjalankan server lokal PHP di `localhost:8080`.              |
| `setup`  | Melakukan setup awal (copy .env, generate key, dump autoload). |
| `test`   | Menjalankan Unit Test (PHPUnit) dengan tampilan yang rapi.     |

### Make (Generator)

Perintah ini membuat file boilerplate secara otomatis di folder yang tepat.

| Perintah                 | Deskripsi                                                                   | Output Folder          |
| :----------------------- | :-------------------------------------------------------------------------- | :--------------------- |
| `make:controller [Name]` | Membuat Controller baru.                                                    | `app/Controllers/`     |
| `make:model [Name]`      | Membuat Model baru (opsi `-m` untuk migrasi).                               | `app/Models/`          |
| `make:view [Name]`       | Membuat View.                                                               | `resources/views/`     |
| `make:migration [Name]`  | Membuat file Migrasi database.                                              | `database/migrations/` |
| `make:seeder [Name]`     | Membuat file Seeder database.                                               | `database/seeders/`    |
| `make:middleware [Name]` | Membuat Middleware baru.                                                    | `app/Middleware/`      |
| `make:request [Name]`    | Membuat Form Request Validation class.                                      | `app/Requests/`        |
| `make:service [Name]`    | Membuat Service class (Business Logic layer).                               | `app/Services/`        |
| `make:repository [Name]` | Membuat Repository class (Data Access layer).                               | `app/Repositories/`    |
| `make:job [Name]`        | Membuat Job class untuk Queue.                                              | `app/Jobs/`            |
| `make:crud [Name]`       | **Special:** Membuat Controller, Model, Request, View, dan Route sekaligus! | _(Multiple Folders)_   |
| `make:db-view [Name]`    | Membuat migrasi khusus untuk Database View (SQL View).                      | `database/migrations/` |

### Database & Migration

| Perintah           | Deskripsi                                                            |
| :----------------- | :------------------------------------------------------------------- |
| `migrate`          | Menjalankan migrasi database yang belum dieksekusi.                  |
| `migrate:rollback` | Membatalkan (undo) batch migrasi terakhir.                           |
| `migrate:fresh`    | **Hapus semua tabel** lalu jalankan migrasi dari awal (Fresh Start). |
| `db:seed`          | Menjalankan seeder untuk mengisi data awal/dummy.                    |

### Routing

| Perintah      | Deskripsi                                                            |
| :------------ | :------------------------------------------------------------------- |
| `route:list`  | Menampilkan tabel daftar semua URL yang terdaftar.                   |
| `route:cache` | Mengkompilasi semua rute menjadi satu file array (untuk Production). |
| `route:clear` | Menghapus file cache rute.                                           |

### Config & Optimization

| Perintah       | Deskripsi                                                                                                                                  |
| :------------- | :----------------------------------------------------------------------------------------------------------------------------------------- |
| `config:cache` | Menggabungkan `.env` dan config lain menjadi satu file PHP cepat.                                                                          |
| `config:clear` | Menghapus cache konfigurasi.                                                                                                               |
| `optimize`     | Membersihkan SEMUA cache (Config, Route, View, OpCache, Storage Cache, & Rate Limit). Jalankan ini untuk benar-benar me-refresh framework! |

### Queue & Asset

| Perintah        | Deskripsi                                                                 |
| :-------------- | :------------------------------------------------------------------------ |
| `queue:work`    | Menjalankan worker untuk memproses Job di background.                     |
| `asset:publish` | Menyalin asset dari path private ke `public/` (jika menggunakan package). |
| `storage:link`  | Membuat symlink `public/storage` ke `storage/app/public`.                 |

---

## Membuat Command Sendiri

Anda bisa menambah perintah custom ke `php artisan`.

1.  Buat file di `app/Console/Commands/NamaCommand.php`.
2.  Implementasikan interface `TheFramework\Console\CommandInterface`.

```php
<?php
namespace TheFramework\Console\Commands;
use TheFramework\Console\CommandInterface;

class HelloCommand implements CommandInterface {
    public function getName(): string {
        return 'app:hello';
    }

    public function getDescription(): string {
        return 'Menyapa dunia';
    }

    public function run(array $args): void {
        echo "Hello World!\n";
    }
}
```

3.  Jalankan `php artisan app:hello`.
