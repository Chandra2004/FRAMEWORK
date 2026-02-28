# 🏗️ Schema Builder Reference

Dokumentasi lengkap semua fitur Schema Builder: tipe kolom, modifiers, indexes, foreign keys, introspection, dan fitur spesial.

---

## 📑 Daftar Isi

- [Membuat & Menghapus Tabel](#membuat--menghapus-tabel)
- [Tipe Kolom](#tipe-kolom)
- [Modifiers](#modifiers)
- [Indexes](#indexes)
- [Foreign Keys](#foreign-keys)
- [Polymorphic Columns](#polymorphic-columns)
- [Table Options](#table-options)
- [Schema Introspection](#schema-introspection)
- [Database Views](#database-views)
- [Bulk Data Operations](#bulk-data-operations)
- [Utilities](#utilities)
- [Fitur Beyond Laravel 🚀](#fitur-beyond-laravel-)

---

## Membuat & Menghapus Tabel

```php
use TheFramework\App\Schema\Schema;
use TheFramework\App\Schema\Blueprint;

// Membuat tabel
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});

// Modify tabel
Schema::table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable();
});

// Membuat tabel hanya jika belum ada
Schema::createIfNotExists('users', function (Blueprint $table) { ... });

// Rename tabel
Schema::rename('old_table', 'new_table');

// Hapus tabel
Schema::drop('users');               // Strict (error jika tidak ada)
Schema::dropIfExists('users');       // Safe
Schema::dropMany(['table1', 'table2']); // Hapus banyak sekaligus

// Truncate (hapus semua data, reset auto-increment)
Schema::truncate('users');
```

---

## Tipe Kolom

### Primary Key / Auto-Increment

| Method                           | SQL                                           | Deskripsi             |
| -------------------------------- | --------------------------------------------- | --------------------- |
| `$table->id()`                   | `id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`  | Primary key standar   |
| `$table->id('uid')`              | `uid INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` | Custom column name    |
| `$table->increments('id')`       | `INT UNSIGNED AUTO_INCREMENT` + PK            | INT auto-increment    |
| `$table->tinyIncrements('id')`   | `TINYINT UNSIGNED AUTO_INCREMENT` + PK        | Tiny auto-increment   |
| `$table->smallIncrements('id')`  | `SMALLINT UNSIGNED AUTO_INCREMENT` + PK       | Small auto-increment  |
| `$table->mediumIncrements('id')` | `MEDIUMINT UNSIGNED AUTO_INCREMENT` + PK      | Medium auto-increment |
| `$table->bigIncrements('id')`    | `BIGINT UNSIGNED AUTO_INCREMENT` + PK         | Big auto-increment    |

### Integer Types

| Method                                   | SQL                  |
| ---------------------------------------- | -------------------- |
| `$table->tinyInteger('level')`           | `TINYINT`            |
| `$table->unsignedTinyInteger('level')`   | `TINYINT UNSIGNED`   |
| `$table->smallInteger('rank')`           | `SMALLINT`           |
| `$table->unsignedSmallInteger('rank')`   | `SMALLINT UNSIGNED`  |
| `$table->mediumInteger('votes')`         | `MEDIUMINT`          |
| `$table->unsignedMediumInteger('votes')` | `MEDIUMINT UNSIGNED` |
| `$table->integer('count')`               | `INT`                |
| `$table->unsignedInteger('count')`       | `INT UNSIGNED`       |
| `$table->bigInteger('amount')`           | `BIGINT`             |
| `$table->unsignedBigInteger('amount')`   | `BIGINT UNSIGNED`    |

### Decimal / Float

| Method                                   | SQL                     |
| ---------------------------------------- | ----------------------- |
| `$table->decimal('price', 8, 2)`         | `DECIMAL(8,2)`          |
| `$table->unsignedDecimal('price', 8, 2)` | `DECIMAL(8,2) UNSIGNED` |
| `$table->float('rating', 53)`            | `FLOAT(53)`             |
| `$table->double('precise')`              | `DOUBLE`                |
| `$table->double('precise', 15, 8)`       | `DOUBLE(15,8)`          |

### String Types

| Method                            | SQL            |
| --------------------------------- | -------------- |
| `$table->char('code', 4)`         | `CHAR(4)`      |
| `$table->string('name')`          | `VARCHAR(255)` |
| `$table->string('name', 100)`     | `VARCHAR(100)` |
| `$table->tinyText('note')`        | `TINYTEXT`     |
| `$table->text('body')`            | `TEXT`         |
| `$table->mediumText('content')`   | `MEDIUMTEXT`   |
| `$table->longText('description')` | `LONGTEXT`     |

### Binary Types

| Method                        | SQL              |
| ----------------------------- | ---------------- |
| `$table->binary('data', 255)` | `VARBINARY(255)` |
| `$table->blob('file')`        | `BLOB`           |
| `$table->mediumBlob('image')` | `MEDIUMBLOB`     |
| `$table->longBlob('video')`   | `LONGBLOB`       |

### Boolean

| Method                      | SQL          |
| --------------------------- | ------------ |
| `$table->boolean('active')` | `TINYINT(1)` |

### Date & Time

| Method                                | SQL                                      |
| ------------------------------------- | ---------------------------------------- |
| `$table->date('birthday')`            | `DATE`                                   |
| `$table->datetime('published_at')`    | `DATETIME`                               |
| `$table->datetime('published_at', 6)` | `DATETIME(6)` — dengan presisi           |
| `$table->datetimeTz('published_at')`  | `DATETIME` — alias timezone              |
| `$table->time('alarm')`               | `TIME`                                   |
| `$table->time('alarm', 3)`            | `TIME(3)` — dengan presisi               |
| `$table->timeTz('alarm')`             | `TIME` — alias timezone                  |
| `$table->timestamp('added_at')`       | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`    |
| `$table->timestamp('added_at', 6)`    | `TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP` |
| `$table->timestampTz('added_at')`     | `TIMESTAMP` — alias timezone             |
| `$table->year('graduated')`           | `YEAR`                                   |
| `$table->timestamps()`                | `created_at` + `updated_at` TIMESTAMP    |
| `$table->nullableTimestamps()`        | `created_at` + `updated_at` nullable     |
| `$table->timestampsTz()`              | Alias `timestamps()`                     |

### Special Types

| Method                                           | SQL                         | Deskripsi          |
| ------------------------------------------------ | --------------------------- | ------------------ |
| `$table->uuid()`                                 | `CHAR(36)`                  | UUID v4            |
| `$table->uuid('order_id')`                       | `CHAR(36)`                  | Custom name        |
| `$table->ulid()`                                 | `CHAR(26)`                  | ULID               |
| `$table->json('options')`                        | `JSON`                      | JSON native        |
| `$table->jsonb('data')`                          | `JSON`                      | JSONB alias        |
| `$table->enum('status', ['active', 'inactive'])` | `ENUM('active','inactive')` | Enum               |
| `$table->set('days', ['Mon', 'Tue', 'Wed'])`     | `SET('Mon','Tue','Wed')`    | Set                |
| `$table->ipAddress()`                            | `VARCHAR(45)`               | IP col (IPv4+IPv6) |
| `$table->ipAddress('server_ip')`                 | `VARCHAR(45)`               | Custom name        |
| `$table->macAddress()`                           | `VARCHAR(17)`               | MAC address        |
| `$table->geometry('location')`                   | `GEOMETRY`                  | Geometry (GIS)     |
| `$table->point('coordinates')`                   | `POINT`                     | Point (GIS)        |
| `$table->rememberToken()`                        | `VARCHAR(100) NULL`         | Auth token         |

### Composite Helpers

| Method                               | Kolom yang Dibuat                               | Deskripsi            |
| ------------------------------------ | ----------------------------------------------- | -------------------- |
| `$table->timestamps()`               | `created_at`, `updated_at`                      | Timestamps standar   |
| `$table->softDeletes()`              | `deleted_at`                                    | Soft delete          |
| `$table->rememberToken()`            | `remember_token`                                | Auth                 |
| `$table->morphs('taggable')`         | `taggable_id`, `taggable_type` + INDEX          | Polymorphic          |
| `$table->nullableMorphs('taggable')` | Seperti morphs() tapi nullable                  | Polymorphic nullable |
| `$table->uuidMorphs('taggable')`     | `taggable_id` CHAR(36), `taggable_type` + INDEX | UUID Polymorphic     |
| `$table->auditColumns()`             | `created_by`, `updated_by`                      | 🚀 Audit trail       |
| `$table->slug()`                     | `slug` VARCHAR(255) + UNIQUE                    | 🚀 URL slug          |
| `$table->status()`                   | `status` ENUM + INDEX + DEFAULT                 | 🚀 Status pattern    |
| `$table->money('price')`             | `DECIMAL(19,4)`                                 | 🚀 Standard keuangan |
| `$table->percentage('discount')`     | `DECIMAL(5,2)`                                  | 🚀 Persentase        |

---

## Modifiers

Modifiers digunakan setelah tipe kolom dengan method chaining:

```php
$table->string('email')->nullable()->unique();
$table->integer('stock')->unsigned()->default(0);
$table->timestamp('verified_at')->nullable()->useCurrent();
```

### Daftar Semua Modifiers

| Modifier                        | Deskripsi                           |
| ------------------------------- | ----------------------------------- |
| `->nullable()`                  | Izinkan NULL                        |
| `->nullable(false)`             | NOT NULL (explicit)                 |
| `->default($value)`             | Set nilai default                   |
| `->default(null)`               | DEFAULT NULL                        |
| `->unsigned()`                  | Mark sebagai UNSIGNED               |
| `->autoIncrement()`             | AUTO_INCREMENT                      |
| `->columnComment('text')`       | Komentar pada kolom                 |
| `->after('column')`             | Posisi setelah kolom (ALTER only)   |
| `->first()`                     | Posisi pertama (ALTER only)         |
| `->columnCharset('utf8')`       | Charset per-kolom                   |
| `->columnCollation('utf8_bin')` | Collation per-kolom                 |
| `->invisible()`                 | Invisible column (MySQL 8.0.23+)    |
| `->useCurrent()`                | DEFAULT CURRENT_TIMESTAMP           |
| `->useCurrentOnUpdate()`        | ON UPDATE CURRENT_TIMESTAMP         |
| `->change()`                    | Modify kolom yang sudah ada (ALTER) |

### Generated / Virtual Columns

```php
// Virtual column (tidak disimpan, dihitung saat query)
$table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");

// Stored column (dihitung & disimpan ke disk)
$table->decimal('total')->storedAs("price * quantity");
```

### Contoh Penggunaan Modifiers

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('sku', 50)->unique();
    $table->text('description')->nullable()->columnComment('Deskripsi produk');
    $table->decimal('price', 10, 2)->unsigned()->default(0);
    $table->integer('stock')->unsigned()->default(0);
    $table->boolean('active')->default(true);
    $table->json('metadata')->nullable();
    $table->decimal('tax_amount')->storedAs("price * 0.11"); // PPN 11%
    $table->timestamps();
});

// Modifikasi kolom yang sudah ada
Schema::table('products', function (Blueprint $table) {
    $table->string('name', 500)->change();  // Ubah panjang
    $table->string('barcode', 50)->nullable()->after('sku'); // Tambah setelah 'sku'
});
```

---

## Indexes

### Membuat Index

```php
// Single column index
$table->string('email')->unique();
$table->string('name')->index();

// Atau terpisah dari definisi kolom
$table->unique('email');
$table->index('name');

// Composite index (multi-kolom)
$table->index(['first_name', 'last_name']);
$table->unique(['email', 'tenant_id']);

// Primary key
$table->primary('id');
$table->primary(['post_id', 'tag_id']); // Composite PK

// Fulltext index
$table->fullText('body');
$table->fullText(['title', 'body']);

// Spatial index (GIS)
$table->spatialIndex('location');

// Raw index dengan expression
$table->rawIndex('(CAST(metadata->>"$.price" AS DECIMAL(10,2)))', 'idx_json_price');

// Custom index name
$table->index('email', 'custom_email_index');
$table->unique('email', 'custom_email_unique');
```

### Menghapus Index

```php
Schema::table('users', function (Blueprint $table) {
    $table->dropIndex('email');        // Drop by column name
    $table->dropIndex('idx_users_email'); // Drop by index name
    $table->dropUnique('email');
    $table->dropPrimary();
    $table->dropFullText('body');
    $table->dropSpatialIndex('location');
});
```

### Rename Index

```php
$table->renameIndex('old_name', 'new_name');
```

---

## Foreign Keys

### Sintaks Fluent (Recommended)

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();

    // foreignId() + constrained() — auto-detect table name
    $table->foreignId('user_id')
          ->constrained()          // references 'id' on 'users'
          ->cascadeOnDelete();

    $table->string('title');
    $table->timestamps();
});
```

### Sintaks Manual

```php
$table->unsignedBigInteger('user_id');
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->onDelete('cascade')
      ->onUpdate('cascade');
```

### Foreign Key Actions

| Method                 | SQL                   | Deskripsi                        |
| ---------------------- | --------------------- | -------------------------------- |
| `->cascadeOnDelete()`  | `ON DELETE CASCADE`   | Hapus child saat parent dihapus  |
| `->restrictOnDelete()` | `ON DELETE RESTRICT`  | Cegah hapus parent               |
| `->nullOnDelete()`     | `ON DELETE SET NULL`  | Set FK null saat parent dihapus  |
| `->noActionOnDelete()` | `ON DELETE NO ACTION` | Tidak ada aksi                   |
| `->cascadeOnUpdate()`  | `ON UPDATE CASCADE`   | Update child saat parent berubah |
| `->restrictOnUpdate()` | `ON UPDATE RESTRICT`  | Cegah update parent              |
| `->noActionOnUpdate()` | `ON UPDATE NO ACTION` | Tidak ada aksi                   |

### UUID & ULID Foreign Keys

```php
// UUID foreign key
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

// ULID foreign key
$table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
```

### Custom Table Name

```php
// Jika nama kolom tidak mengikuti konvensi
$table->foreignId('author_id')
      ->constrained('users')       // Specify table name
      ->cascadeOnDelete();
```

### Smart Pluralization

`constrained()` menggunakan pluralization cerdas:

| Kolom         | Auto-detect Tabel                     |
| ------------- | ------------------------------------- |
| `user_id`     | `users`                               |
| `category_id` | `categories` ✅ (bukan ~~categorys~~) |
| `child_id`    | `children` ✅                         |
| `person_id`   | `people` ✅                           |

### Menghapus Foreign Key

```php
Schema::table('posts', function (Blueprint $table) {
    $table->dropForeign('user_id');      // By column name
    $table->dropForeign('posts_user_id_foreign'); // By constraint name

    // 🚀 Drop FK + index + column sekaligus
    $table->dropConstrainedForeignId('user_id');
});
```

---

## Polymorphic Columns

### morphs()

Membuat kolom `{name}_id` (BIGINT UNSIGNED) dan `{name}_type` (VARCHAR) + index:

```php
$table->morphs('commentable');
// Hasil:
//   commentable_id BIGINT UNSIGNED
//   commentable_type VARCHAR(255)
//   INDEX (commentable_id, commentable_type)
```

### nullableMorphs()

```php
$table->nullableMorphs('imageable');
// Sama seperti morphs() tapi kedua kolom nullable
```

### uuidMorphs() & nullableUuidMorphs()

```php
$table->uuidMorphs('likeable');
// Hasil:
//   likeable_id CHAR(36)
//   likeable_type VARCHAR(255)
//   INDEX (likeable_id, likeable_type)
```

### Drop Morphs

```php
$table->dropMorphs('commentable');
// Menghapus commentable_id dan commentable_type
```

---

## Table Options

```php
Schema::create('logs', function (Blueprint $table) {
    $table->engine('MyISAM');             // Storage engine
    $table->charset('utf8mb4');           // Character set
    $table->collation('utf8mb4_bin');     // Collation
    $table->comment('Tabel log sistem'); // Table comment
    $table->temporary();                  // Temporary table

    $table->id();
    $table->text('message');
    $table->timestamps();
});
```

---

## Schema Introspection

Schema menyediakan method untuk memeriksa struktur database saat runtime:

### Cek Keberadaan

```php
// Cek tabel
Schema::hasTable('users');            // true/false

// Cek kolom
Schema::hasColumn('users', 'email'); // true/false

// Cek beberapa kolom sekaligus
Schema::hasColumns('users', ['email', 'name', 'password']); // true jika SEMUA ada

// Cek index
Schema::hasIndex('users', 'users_email_unique'); // true/false

// Cek view
Schema::hasView('active_users'); // true/false
```

### Listing

```php
// Daftar semua tabel
Schema::getTables();      // ['users', 'posts', 'comments', ...]

// Daftar semua views
Schema::getViews();       // ['active_users', 'user_stats', ...]

// Daftar kolom di tabel
Schema::getColumnListing('users'); // ['id', 'name', 'email', ...]

// Tipe data kolom
Schema::getColumnType('users', 'email'); // 'varchar(255)'
```

### Detail Kolom

```php
// Detail semua kolom (SHOW FULL COLUMNS)
$columns = Schema::getColumns('users');
// [
//   ['Field' => 'id', 'Type' => 'int unsigned', 'Null' => 'NO', ...],
//   ['Field' => 'name', 'Type' => 'varchar(255)', 'Null' => 'NO', ...],
// ]

// Detail satu kolom
$col = Schema::getColumn('users', 'email');
// ['Field' => 'email', 'Type' => 'varchar(255)', 'Null' => 'NO', 'Key' => 'UNI', ...]
```

### Indexes & Foreign Keys

```php
// Daftar indexes
$indexes = Schema::getIndexes('users');

// Daftar foreign keys
$fks = Schema::getForeignKeys('posts');
// [
//   ['CONSTRAINT_NAME' => 'posts_user_id_foreign', 'COLUMN_NAME' => 'user_id',
//    'REFERENCED_TABLE_NAME' => 'users', 'REFERENCED_COLUMN_NAME' => 'id', ...]
// ]
```

### CREATE TABLE Statement

```php
$sql = Schema::getCreateTable('users');
// "CREATE TABLE `users` (
//    `id` int unsigned NOT NULL AUTO_INCREMENT,
//    ...
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
```

---

## FK Constraints Toggle

```php
// Matikan FK checks (berguna saat seeding/migrasi)
Schema::disableForeignKeyConstraints();
// ... operasi tanpa FK validation ...
Schema::enableForeignKeyConstraints();

// Atau menggunakan callback (auto-enable di akhir)
Schema::withoutForeignKeyConstraints(function () {
    Schema::dropIfExists('posts');
    Schema::dropIfExists('users');
});
```

---

## Database Views

```php
// Buat view
Schema::createView('active_users', "SELECT * FROM users WHERE active = 1");

// Buat view dari QueryBuilder
Schema::createView('admin_users', User::where('role', 'admin')->toSql());

// Drop view
Schema::dropView('active_users');

// Cek keberadaan view
Schema::hasView('active_users'); // true/false
```

---

## Bulk Data Operations

### Insert

```php
Schema::insert('users', [
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
]);
```

### Upsert (Insert or Update)

```php
Schema::upsert(
    'products',
    [
        ['sku' => 'ABC123', 'name' => 'Widget', 'price' => 9.99],
        ['sku' => 'DEF456', 'name' => 'Gadget', 'price' => 19.99],
    ],
    ['sku'],                 // Unique columns
    ['name', 'price']        // Columns to update on duplicate
);
// INSERT INTO ... ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price)
```

---

## Utilities

### Conditional Operations

```php
// Jalankan callback hanya jika kolom ada
Schema::whenTableHasColumn('users', 'avatar', function () {
    // ... lakukan sesuatu
});

// Jalankan callback hanya jika kolom TIDAK ada
Schema::whenTableDoesntHaveColumn('users', 'avatar', function () {
    Schema::table('users', function (Blueprint $table) {
        $table->string('avatar')->nullable();
    });
});
```

### Database Info

```php
Schema::getDatabaseName();    // 'my_database'
Schema::getServerVersion();   // '8.0.32-MariaDB'
Schema::getDatabaseSize();    // 1048576 (bytes)
```

### Table Stats

```php
Schema::getTableSize('users');     // 65536 (bytes)
Schema::getTableRowCount('users'); // 1500 (estimasi)
Schema::getTableEngine('users');   // 'InnoDB'

// Statistik lengkap semua tabel
$stats = Schema::getTableStats();
// [
//   ['TABLE_NAME' => 'users', 'TABLE_ROWS' => 1500,
//    'DATA_LENGTH' => 65536, 'INDEX_LENGTH' => 16384, ...]
// ]
```

---

## Fitur Beyond Laravel 🚀

### columnIf() — Conditional Column

```php
$hasSlug = true;
$table->columnIf($hasSlug, fn(Blueprint $t) => $t->slug());
```

### check() — CHECK Constraint (MySQL 8.0.16+)

```php
$table->integer('age');
$table->check('age >= 0 AND age <= 150', 'chk_valid_age');
// CONSTRAINT `chk_valid_age` CHECK (age >= 0 AND age <= 150)

// Drop CHECK
$table->dropCheck('chk_valid_age');
```

### jsonIndex() — JSON Path Index

```php
// Buat virtual column + index untuk field di dalam JSON
$table->json('metadata');
$table->jsonIndex('metadata', '$.category', 'VARCHAR(100)', 'idx_meta_category');
```

### rawColumn() — Custom SQL Type

```php
$table->rawColumn('coordinates', 'POINT NOT NULL SRID 4326');
```

### diffTables() — Schema Comparison

```php
$diff = Schema::diffTables('users_v1', 'users_v2');
// [
//   'added'    => [...columns in v2 but not v1...],
//   'removed'  => [...columns in v1 but not v2...],
//   'modified' => [...columns with different definitions...],
// ]
```

### dumpBlueprint() — Reverse Engineering

```php
// Generate Blueprint code dari tabel yang sudah ada!
$code = Schema::dumpBlueprint('users');
echo $code;
// Output:
// Schema::create('users', function (Blueprint $table) {
//     $table->increments('id');
//     $table->string('name', 255);
//     $table->string('email', 255)->unique();
//     ...
// });
```

---

## Drop Helpers

| Method                                        | Deskripsi                            |
| --------------------------------------------- | ------------------------------------ |
| `$table->dropColumn('name')`                  | Drop satu kolom                      |
| `$table->dropColumn(['col1', 'col2'])`        | Drop beberapa kolom                  |
| `$table->dropTimestamps()`                    | Drop `created_at` + `updated_at`     |
| `$table->dropSoftDeletes()`                   | Drop `deleted_at`                    |
| `$table->dropRememberToken()`                 | Drop `remember_token`                |
| `$table->dropMorphs('taggable')`              | Drop `taggable_id` + `taggable_type` |
| `$table->dropAuditColumns()`                  | 🚀 Drop `created_by` + `updated_by`  |
| `$table->dropConstrainedForeignId('user_id')` | 🚀 Drop FK + index + column          |
| `$table->dropIndex('col')`                    | Drop index                           |
| `$table->dropUnique('col')`                   | Drop unique index                    |
| `$table->dropPrimary()`                       | Drop primary key                     |
| `$table->dropFullText('col')`                 | Drop fulltext index                  |
| `$table->dropSpatialIndex('col')`             | Drop spatial index                   |
| `$table->dropForeign('col')`                  | Drop foreign key constraint          |
| `$table->dropCheck('name')`                   | 🚀 Drop check constraint             |

---

📖 **Kembali ke:** [Migrations](migrations.md) | [ORM & Relations](orm.md) | [Database](database.md)
