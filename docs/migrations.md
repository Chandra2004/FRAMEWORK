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

Di versi **5.0.0**, Schema Builder kini mendukung penulisan _fluent_ (chaining) yang mempermudah Anda dalam menambahkan constraint tanpa harus menuliskan nama kolom berulang kali:

| Modifier           | Deskripsi                                             |
| :----------------- | :---------------------------------------------------- |
| `->nullable()`     | Mengizinkan nilai NULL pada kolom.                    |
| `->default(value)` | Menetapkan nilai default.                             |
| `->unique()`       | Menambahkan index UNIQUE pada kolom tersebut.         |
| `->index()`        | Menambahkan index biasa pada kolom tersebut.          |
| `->primary()`      | Menjadikan kolom tersebut sebagai Primary Key.        |
| `->unsigned()`     | Menetapkan tipe integer sebagai UNSIGNED (hanya pos). |

**Contoh Chaining:**

```php
Schema::create('products', function($table) {
    $table->id();
    $table->string('sku')->unique();
    $table->string('name')->index();
    $table->integer('stock')->unsigned()->default(0);
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### Menghapus Tabel

```php
Schema::drop('nama_tabel');
Schema::dropIfExists('nama_tabel');
```
