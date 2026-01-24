# 🏗️ Migrations & Schema Builder

Migrasi adalah cara terbaik mengelola perubahan struktur database. Anggap saja sebagai _version control_ (seperti Git) untuk tabel database Anda.

## Struktur File Migrasi

File migrasi yang dibuat oleh `php artisan make:migration` memiliki dua method utama:

- `up()`: Eksekusi perubahan (misal: buat tabel).
- `down()`: Batalkan perubahan (misal: hapus tabel).

```php
use TheFramework\Database\Schema;

class Migration_CreateUsersTable {
    public function up() {
        Schema::create('users', function($table) {
            $table->id();
            $table->string('username', 100);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('users');
    }
}
```

## Schema Builder Method

Class `Schema` mendukung berbagai tipe kolom MySQL:

| Method                             | Syntax SQL yang Dihasilkan                             |
| :--------------------------------- | :----------------------------------------------------- |
| `$table->id()`                     | `id INT AUTO_INCREMENT PRIMARY KEY`                    |
| `$table->string('col', length)`    | `col VARCHAR(length)`                                  |
| `$table->text('body')`             | `body TEXT`                                            |
| `$table->integer('count')`         | `count INT`                                            |
| `$table->bigInteger('amount')`     | `amount BIGINT`                                        |
| `$table->float('price')`           | `price FLOAT`                                          |
| `$table->double('precise')`        | `precise DOUBLE`                                       |
| `$table->boolean('active')`        | `active TINYINT(1)`                                    |
| `$table->date('birthday')`         | `birthday DATE`                                        |
| `$table->dateTime('published_at')` | `published_at DATETIME`                                |
| `$table->timestamps()`             | Membuat kolom `created_at` dan `updated_at` (DATETIME) |

### Modifiers

Anda tidak bisa menggunakan modifier _fluent_ (seperti `->nullable()`) di versi Framework 4.0 ini. Sebagai gantinya, argumen tambahan digunakan pada beberapa fungsi tertentu atau Anda perlu melakukan query SQL manual (`Schema::execute`) untuk constraint yang sangat spesifik.

Namun, untuk kebutuhan standar (CRUD), method di atas sudah sangat mencukupi.

### Menghapus Tabel

```php
Schema::drop('nama_tabel');
Schema::dropIfExists('nama_tabel');
```
