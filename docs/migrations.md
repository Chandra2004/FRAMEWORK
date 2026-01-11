# Migrations & Schema

Migration adalah version control untuk database Anda, memungkinkan tim untuk mendefinisikan dan membagikan skema database aplikasi.

## Membuat Migration

Buat file class baru di folder `database/migrations/`. Format penamaan bebas, tapi disarankan menggunakan timestamp agar urut (misal `2025_01_01_CreateUsersTable.php`).

Class harus meng-extend `TheFramework\App\Database\Migration`.

```php
use TheFramework\App\Database\Migration;
use TheFramework\App\Schema;
use TheFramework\App\Blueprint;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Auto-increment ID
            $table->string('username', 100);
            $table->string('email')->unique(); // Unique Index
            $table->string('password');
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->text('bio')->nullable();
            $table->timestamps(); // create_at, updated_at

            // Indexing manual
            $table->index('username');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

## Available Column Types

| Method                           | SQL Type                                  |
| -------------------------------- | ----------------------------------------- |
| `$table->id()`                   | `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `$table->string('name', length)` | `VARCHAR`                                 |
| `$table->text('body')`           | `TEXT`                                    |
| `$table->integer('age')`         | `INT`                                     |
| `$table->boolean('is_active')`   | `TINYINT(1)`                              |
| `$table->date('dob')`            | `DATE`                                    |
| `$table->timestamp('added_on')`  | `TIMESTAMP`                               |
| `$table->json('options')`        | `JSON`                                    |
| `$table->enum('col', ['a','b'])` | `ENUM`                                    |

## Modifikasi Tabel (Alter Table)

Gunakan `Schema::table` untuk mengubah tabel yang sudah ada. Fitur ini sangat berguna untuk update aplikasi di production.

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone_number', 20)->nullable()->after('email'); // Tambah kolom
        $table->index('phone_number'); // Tambah Index baru
    });
}
```

## Database Views

Anda dapat membuat virtual table (View) untuk menyederhanakan query kompleks.

```php
public function up()
{
    $query = "SELECT u.id, u.username, p.title
              FROM users u
              JOIN posts p ON u.id = p.user_id
              WHERE u.role = 'editor'";

    Schema::createView('editors_posts_view', $query);
}

public function down()
{
    Schema::dropView('editors_posts_view');
}
```

## Menjalankan Migration

Gunakan script `artisan` (jika sudah tersedia) atau buat script PHP sederhana untuk mengeksekusi method `up()` pada file migrasi Anda.

_Catatan: Saat ini eksekutor migrasi (Migrator) berjalan manual atau via endpoint dev yang Anda buat, pastikan untuk memanggil method `up` dari class migrasi yang diinginkan._
