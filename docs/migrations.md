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

---

## Foreign Keys (Relational Constraints)

Framework mendukung pembuatan **foreign key constraints** untuk menjaga integritas relasi antar tabel.

### Sintaks Lengkap

```php
Schema::create('posts', function($table) {
    $table->id();
    $table->integer('user_id')->unsigned();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
    
    // Membuat foreign key constraint
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade')    // Aksi saat parent dihapus
          ->onUpdate('cascade');   // Aksi saat parent diupdate
});
```

### Shorthand Helper Methods (Recommended)

Untuk sintaks yang lebih ringkas dan modern (mirip Laravel):

```php
Schema::create('posts', function($table) {
    $table->id();
    
    // Method 1: foreignId() + constrained()
    // Auto-detect: 'user_id' -> references 'id' on 'users' table
    $table->foreignId('user_id')
          ->constrained()
          ->cascadeOnDelete();
    
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

**Penjelasan:**
- `foreignId('user_id')` → Membuat kolom `BIGINT UNSIGNED`
- `constrained()` → Auto-detect nama tabel dari kolom (`user_id` → `users`)
- `cascadeOnDelete()` → Shorthand untuk `onDelete('cascade')`

### Opsi Foreign Key Actions

| Method | Aksi | Deskripsi |
|:-------|:-----|:----------|
| `cascadeOnDelete()` | `ON DELETE CASCADE` | Hapus child records saat parent dihapus |
| `restrictOnDelete()` | `ON DELETE RESTRICT` | Cegah penghapusan parent jika masih ada child |
| `nullOnDelete()` | `ON DELETE SET NULL` | Set foreign key jadi NULL saat parent dihapus |
| `cascadeOnUpdate()` | `ON UPDATE CASCADE` | Update child records saat parent ID berubah |

### Contoh Penggunaan Lengkap

```php
// Migration: Create users table
Schema::create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});

// Migration: Create posts table with foreign key
Schema::create('posts', function($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});

// Migration: Create comments table
Schema::create('comments', function($table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->text('comment');
    $table->timestamps();
});
```

### Custom Table Name

Jika nama tabel tidak mengikuti konvensi:

```php
// Kolom 'author_id' reference ke tabel 'users'
$table->foreignId('author_id')
      ->constrained('users')  // Specify table name
      ->cascadeOnDelete();

// Atau menggunakan foreign() manual
$table->integer('author_id')->unsigned();
$table->foreign('author_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade');
```

### Menghapus Foreign Key

Untuk menghapus foreign key constraint di migration `down()`:

```php
public function down() {
    Schema::table('posts', function($table) {
        // Method 1: Menggunakan nama kolom
        $table->dropForeign(['user_id']);
        
        // Method 2: Menggunakan constraint name
        // $table->dropForeign('posts_user_id_foreign');
    });
    
    Schema::dropIfExists('posts');
}
```

### Menghapus Tabel

```php
Schema::drop('nama_tabel');
Schema::dropIfExists('nama_tabel');
```
