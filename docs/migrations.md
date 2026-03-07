# 📊 Migrations — Database Version Control

Migrasi adalah cara terbaik mengelola perubahan struktur database. Anggap saja sebagai _version control_ (seperti Git) untuk tabel database Anda.

---

## 📑 Daftar Isi

- [Struktur File Migrasi](#struktur-file-migrasi)
- [Membuat File Migrasi](#membuat-file-migrasi)
- [Menjalankan Migrasi](#menjalankan-migrasi)
- [Rollback & Reset](#rollback--reset)
- [Status Migrasi](#status-migrasi)
- [Artisan Commands](#artisan-commands)
- [Fitur Lanjutan](#fitur-lanjutan)

---

## Struktur File Migrasi

Setiap file migrasi memiliki dua method utama:

- **`up()`** — Eksekusi perubahan (buat tabel, tambah kolom, dll)
- **`down()`** — Batalkan perubahan (hapus tabel, hapus kolom, dll)

Selain itu, mulai versi 5.0, Migration menyediakan **Transaction Helpers** secara native untuk mencegah tabel menggantung jika terjadi error di tengah jalan:

- `$this->beginTransaction()`
- `$this->commit()`
- `$this->rollback()`

```php
<?php

use TheFramework\App\Schema\Schema;
use TheFramework\App\Schema\Blueprint;
use TheFramework\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### Konvensi Penamaan File

File migrasi menggunakan format timestamp:

```
YYYY_MM_DD_HHMMSS_nama_migrasi.php
```

Contoh:

```
2026_02_26_100000_create_users_table.php
2026_02_26_100001_create_posts_table.php
2026_02_26_100002_add_avatar_to_users_table.php
```

---

## Membuat File Migrasi

### Via Artisan CLI

```bash
# Migrasi kosong
php artisan make:migration create_posts_table

# Migrasi dengan opsi --create (auto-generate stub create)
php artisan make:migration create_posts_table --create=posts

# Migrasi untuk modify tabel (auto-generate stub table)
php artisan make:migration add_slug_to_posts_table --table=posts
```

### Via Migrator (Programmatic)

```php
use TheFramework\App\Schema\Migrator;

$migrator = new Migrator();

// Generate file migrasi baru (CREATE stub)
$migrator->createMigrationFile('create_products_table', 'products', create: true);

// Generate file migrasi baru (ALTER stub)
$migrator->createMigrationFile('add_price_to_products', 'products');
```

---

## Menjalankan Migrasi

### Via Artisan CLI

```bash
# Jalankan semua migrasi yang belum dijalankan
php artisan migrate

# Jalankan dengan output detail
php artisan migrate --verbose
```

### Via Web Command Center

Untuk shared hosting tanpa SSH:

```
https://yoursite.com/_system/migrate
```

### Via Kode (Programmatic)

```php
use TheFramework\App\Schema\Migrator;

$migrator = new Migrator();

// Jalankan semua migrasi pending
$count = $migrator->run();
echo "Dijalankan: $count migrasi";

// Lihat output detail
foreach ($migrator->getOutput() as $line) {
    echo $line . "\n";
}
```

**Output contoh:**

```
⬆️  Migrating: 2026_02_26_100000_create_users_table.php
   ✅ Migrated: 2026_02_26_100000_create_users_table.php (12.5ms)
⬆️  Migrating: 2026_02_26_100001_create_posts_table.php
   ✅ Migrated: 2026_02_26_100001_create_posts_table.php (8.3ms)
✅ 2 migrasi berhasil dijalankan (Batch #1).
```

---

## Rollback & Reset

### Rollback Batch Terakhir

```bash
php artisan migrate:rollback
```

```php
$migrator = new Migrator();

// Rollback 1 batch terakhir
$migrator->rollback();

// Rollback 3 batch terakhir
$migrator->rollback(steps: 3);
```

### Reset (Rollback Semua)

```bash
php artisan migrate:reset
```

```php
$migrator->reset(); // Rollback semua migrasi dari yang terakhir
```

### Fresh (Drop All + Migrate)

```bash
php artisan migrate:fresh
```

```php
// ⚠️ DANGER: DROP SEMUA tabel, lalu jalankan ulang semua migrasi
$migrator->fresh();
```

> ⚠️ **PERHATIAN:** `fresh()` memiliki guard produksi. Jika `APP_ENV=production`, method ini akan throw RuntimeException untuk mencegah kehilangan data.

### Refresh (Reset + Migrate)

```bash
php artisan migrate:refresh
```

```php
// Rollback semua, lalu jalankan ulang semua
$migrator->refresh();
```

### Rollback Specific

```php
// Rollback migrasi tertentu saja
$migrator->rollbackSpecific('2026_02_26_100002_add_slug_to_posts_table.php');

// Jalankan migrasi tertentu saja
$migrator->runSpecific('2026_02_26_100002_add_slug_to_posts_table.php');
```

---

## Status Migrasi

### Via Artisan

```bash
php artisan migrate:status
```

### Via Kode

```php
$migrator = new Migrator();

// Dapatkan status semua migrasi
$status = $migrator->getStatus();
foreach ($status as $s) {
    echo "{$s['status']}: {$s['migration']} (Batch #{$s['batch']})\n";
}

// Output:
// Ran: 2026_02_26_100000_create_users_table.php (Batch #1)
// Ran: 2026_02_26_100001_create_posts_table.php (Batch #1)
// Pending: 2026_02_26_100002_add_slug_to_posts_table.php (Batch #)
```

### Summary (Formatted Report)

```php
echo $migrator->summary();
```

```
📊 Migration Summary
──────────────────────────────────────────────────
   Total:   3
   Ran:     2
   Pending: 1
──────────────────────────────────────────────────
   ✅ 2026_02_26_100000_create_users_table.php [Batch #1]
   ✅ 2026_02_26_100001_create_posts_table.php [Batch #1]
   ⏳ 2026_02_26_100002_add_slug_to_posts_table.php
```

### Pending Count

```php
$pending = $migrator->getPendingCount();     // 1
$files = $migrator->getPendingMigrations();  // ['filename' => 'path']
```

---

## Artisan Commands

| Command                             | Deskripsi                      |
| ----------------------------------- | ------------------------------ |
| `php artisan migrate`               | Jalankan semua migrasi pending |
| `php artisan migrate:rollback`      | Rollback batch terakhir        |
| `php artisan migrate:reset`         | Rollback semua migrasi         |
| `php artisan migrate:fresh`         | Drop semua tabel + migrate     |
| `php artisan migrate:refresh`       | Reset + migrate                |
| `php artisan migrate:status`        | Lihat status migrasi           |
| `php artisan make:migration <name>` | Buat file migrasi baru         |

---

## Fitur Lanjutan

### Pretend Mode (Dry Run)

Jalankan migrasi tanpa benar-benar mengeksekusi SQL:

```php
$migrator = new Migrator();
$migrator->pretend();
$migrator->run();

// Output akan menampilkan apa yang AKAN dijalankan tanpa benar-benar menjalankannya
foreach ($migrator->getOutput() as $line) {
    echo "$line\n";
}
```

### Custom Migration Path

```php
$migrator = new Migrator('/path/to/custom/migrations');
// atau
$migrator->setPath('/another/path');
```

### Squash (Export SQL Dump)

Gabungkan semua migrasi yang sudah ada menjadi satu SQL dump file:

```php
$filepath = $migrator->squash();
// Output: database/migrations/2026_02_26_110000_squashed_schema.sql
```

### Tabel Migrations

Migrator secara otomatis membuat tabel `migrations` yang berisi:

| Kolom         | Tipe            | Deskripsi         |
| ------------- | --------------- | ----------------- |
| `id`          | INT UNSIGNED PK | Auto-increment ID |
| `migration`   | VARCHAR(255)    | Nama file migrasi |
| `batch`       | INT             | Nomor batch       |
| `executed_at` | TIMESTAMP       | Waktu eksekusi    |

---

## Contoh Lengkap: CRUD Migrasi

### 1. Buat Tabel

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->slug();                           // VARCHAR + UNIQUE INDEX
    $table->text('description')->nullable();
    $table->money('price');                   // DECIMAL(19,4)
    $table->integer('stock')->unsigned()->default(0);
    $table->status();                         // ENUM + INDEX + DEFAULT
    $table->boolean('featured')->default(false);
    $table->json('metadata')->nullable();
    $table->softDeletes();
    $table->timestamps();
    $table->auditColumns();                   // created_by, updated_by
});
```

### 2. Modifikasi Tabel

```php
Schema::table('products', function (Blueprint $table) {
    $table->string('barcode', 50)->nullable()->after('sku');
    $table->decimal('weight', 8, 2)->nullable()->after('price');
    $table->index(['name', 'category_id']);   // Composite index
});
```

### 3. Cek Sebelum Modifikasi

```php
// Conditional schema changes
if (!Schema::hasColumn('products', 'barcode')) {
    Schema::table('products', function (Blueprint $table) {
        $table->string('barcode', 50)->nullable();
    });
}

// Atau pakai helper
Schema::whenTableDoesntHaveColumn('products', 'barcode', function () {
    Schema::table('products', function (Blueprint $table) {
        $table->string('barcode', 50)->nullable();
    });
});
```

### 4. Drop Tabel

```php
Schema::dropIfExists('products');
```

---

📖 **Selanjutnya:** [Schema Builder Reference](schema-builder.md) — Dokumentasi lengkap semua tipe kolom, modifiers, dan indexes.
