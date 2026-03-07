# 🗄️ Database Engine (Advanced)

Dokumentasi mendalam tentang **Database Engine** — class inti yang mengelola koneksi PDO, query execution, transactions, dan lifecycle database di The Framework.

> **📖 Catatan:** Untuk Query Builder ORM, lihat [Query Builder](query-builder.md) dan [ORM](orm.md). Halaman ini fokus pada **low-level database engine** dan fitur yang tidak tercakup di dokumentasi dasar.

---

## 📋 Daftar Isi

1. [Arsitektur](#arsitektur)
2. [Lazy Connection](#lazy-connection)
3. [Connection Management](#connection-management)
4. [CRUD Shortcuts](#crud-shortcuts)
5. [Raw Queries](#raw-queries)
6. [Transactions](#transactions)
7. [Savepoints](#savepoints)
8. [Query Logging](#query-logging)
9. [Benchmarking](#benchmarking)
10. [Server Information](#server-information)
11. [Database Toggle](#database-toggle)
12. [Error Handling](#error-handling)
13. [Method Reference](#method-reference)

---

## Arsitektur

```
┌─────────────────────────────────────────┐
│              Application Code           │
│  (Controller, Service, Repository)      │
├────────────┬────────────┬───────────────┤
│   Model    │  Query     │  Schema       │
│   (ORM)    │  Builder   │  Builder      │
├────────────┴────────────┴───────────────┤
│            Database Engine              │  ← HALAMAN INI
│  (PDO Wrapper, Connection, Transactions)│
├─────────────────────────────────────────┤
│           PDO (PHP Extension)           │
├─────────────────────────────────────────┤
│          MySQL / MariaDB Server         │
└─────────────────────────────────────────┘
```

### Design Pattern

- **Singleton Pattern** — Satu instance `Database` per application lifecycle
- **Lazy Connection** — Koneksi database hanya dibuat saat benar-benar diperlukan
- **Prepared Statements** — Semua query menggunakan PDO prepared statements
- **Anti-Clone/Serialize** — Mencegah duplikasi instance via `__clone()` dan `__wakeup()`

---

## Lazy Connection

**Fitur unik:** Database **tidak langsung terhubung** saat `getInstance()` dipanggil. Koneksi baru dibuat saat query pertama dijalankan.

```php
use TheFramework\App\Database\Database;

// Ini BELUM membuat koneksi database
$db = Database::getInstance();

// Koneksi dibuat di sini (saat pertama kali butuh)
$db->query("SELECT * FROM users");
$db->execute();
```

### Manfaat Lazy Connection

| Manfaat              | Deskripsi                                                        |
| -------------------- | ---------------------------------------------------------------- |
| **Performa**         | Halaman statis tidak membuka koneksi database                    |
| **Resource Saving**  | Connection pool tidak terbuang untuk request yang tidak butuh DB |
| **Error Tolerance**  | Aplikasi tetap bisa jalan tanpa DB untuk fitur non-DB            |
| **Faster Bootstrap** | Boot time lebih cepat tanpa koneksi DB di awal                   |

---

## Connection Management

### Mendapatkan Instance

```php
$db = Database::getInstance(); // Singleton
```

### Test Connection

```php
// Test apakah bisa terhubung ke database (tidak throw exception)
if ($db->testConnection()) {
    echo "Database connected!";
} else {
    echo "Database unreachable";
}
```

### Check Status

```php
// Apakah koneksi sudah terbuat?
$db->isConnected(); // true/false

// Apakah database feature diaktifkan?
Database::isEnabled(); // true/false
```

### Reconnect / Disconnect

```php
// Putus koneksi
$db->disconnect();

// Sambung kembali
$db->reconnect();
```

### Konfigurasi Koneksi

Koneksi dikonfigurasi via `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_database
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### PDO Options (Default)

Framework mengatur PDO options yang optimal:

```php
PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exception saat error
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,             // Fetch sebagai object
PDO::ATTR_EMULATE_PREPARES   => false,                      // True prepared statements
PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4 ...",   // Charset, timezone, SQL mode
```

---

## CRUD Shortcuts

Database Engine menyediakan metode shortcut untuk operasi CRUD dasar:

### Insert

```php
$db->insert('users', [
    'name' => 'Chandra',
    'email' => 'chandra@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT),
]);

$newId = $db->lastInsertId();
echo "User created with ID: $newId";
```

### Select

```php
// Select sederhana
$users = $db->select('users', ['id', 'name', 'email']);

// Dengan kondisi WHERE
$admins = $db->select('users', ['*'], ['role' => 'admin']);

// Dengan sorting, limit, offset
$latestPosts = $db->select(
    'posts',           // table
    ['id', 'title'],   // columns
    ['published' => 1],// where
    'created_at DESC', // orderBy
    10,                // limit
    0                  // offset
);
```

### Update

```php
$affected = $db->update('users',
    ['name' => 'Chandra Tri Antomo'],  // data
    ['id' => 1]                         // where
);

echo "$affected baris diupdate";
```

### Delete

```php
$deleted = $db->delete('users', ['id' => 5]);
echo "$deleted baris dihapus";
```

---

## Raw Queries

### Prepared Statement (Recommended)

```php
// 1. Prepare
$db->query("SELECT * FROM users WHERE email = :email AND active = :active");

// 2. Bind
$db->bind(':email', $email);
$db->bind(':active', 1);

// 3. Execute
$db->execute();

// 4. Fetch results
$user = $db->single();       // Satu baris (object)
$users = $db->resultSet();   // Banyak baris (array of objects)
$count = $db->rowCount();    // Jumlah baris terpengaruh
```

### Auto Type Detection

Method `bind()` otomatis mendeteksi tipe data PDO:

| PHP Type      | PDO Type          |
| ------------- | ----------------- |
| `int`         | `PDO::PARAM_INT`  |
| `bool`        | `PDO::PARAM_BOOL` |
| `null`        | `PDO::PARAM_NULL` |
| `string`, dll | `PDO::PARAM_STR`  |

### Statement (DDL/Non-Query)

```php
// Untuk ALTER, CREATE, DROP, dll
$db->statement("ALTER TABLE users ADD COLUMN age INT DEFAULT 0");

// Dengan bindings
$db->statement("INSERT INTO logs (message, level) VALUES (?, ?)", [
    'System started', 'info'
]);
```

### Unprepared SQL (Hati-hati!)

```php
// ⚠️ Tidak menggunakan prepared statement — hanya untuk DDL/admin
$db->unprepared("
    CREATE TABLE IF NOT EXISTS temp_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data TEXT
    )
");
```

### Raw Expression

```php
// Membuat raw SQL expression (untuk Query Builder)
$expr = $db->raw('NOW()');
$expr = $db->raw('COUNT(*)');
```

---

## Transactions

### Basic Transaction

```php
try {
    $db->beginTransaction();

    $db->insert('orders', ['user_id' => 1, 'total' => 500000]);
    $orderId = $db->lastInsertId();

    $db->insert('order_items', ['order_id' => $orderId, 'product_id' => 5]);
    $db->update('products', ['stock' => 99], ['id' => 5]);

    $db->commit();
    echo "Order berhasil!";
} catch (\Exception $e) {
    $db->rollBack();
    echo "Gagal: " . $e->getMessage();
}
```

### Closure Transaction (Recommended)

Framework menyediakan helper `transaction()` yang **auto-commit** dan **auto-rollback**:

```php
$result = $db->transaction(function () use ($db) {
    $db->insert('orders', ['user_id' => 1, 'total' => 500000]);
    $orderId = $db->lastInsertId();

    $db->insert('order_items', ['order_id' => $orderId, 'product_id' => 5]);

    return $orderId; // Return value dari transaction
});

echo "Order #$result berhasil dibuat!";
```

### Deadlock Retry

Transaction otomatis di-retry jika terjadi deadlock:

```php
// Retry sampai 3x jika deadlock
$result = $db->transaction(function () use ($db) {
    // ... operasi database yang rawan deadlock
    $db->update('products', ['stock' => $newStock], ['id' => $productId]);
    return true;
}, attempts: 3);
```

---

## Savepoints

Savepoint memungkinkan **partial rollback** di dalam transaction:

```php
$db->beginTransaction();

try {
    $db->insert('orders', ['user_id' => 1, 'total' => 100000]);

    $db->savepoint('after_order');

    try {
        // Operasi opsional (boleh gagal)
        $db->insert('notifications', ['user_id' => 1, 'type' => 'order']);
    } catch (\Exception $e) {
        // Rollback hanya notifikasi, order tetap aman
        $db->rollbackTo('after_order');
    }

    $db->commit();
} catch (\Exception $e) {
    $db->rollBack(); // Full rollback
}
```

### Savepoint Methods

| Method                    | Deskripsi             |
| ------------------------- | --------------------- |
| `savepoint($name)`        | Buat savepoint        |
| `rollbackTo($name)`       | Rollback ke savepoint |
| `releaseSavepoint($name)` | Hapus savepoint       |

---

## Query Logging

Database Engine mencatat semua query yang dieksekusi:

```php
// Jalankan beberapa query
$db->select('users');
$db->select('posts', ['*'], ['published' => 1]);

// Lihat log
$log = $db->getQueryLog();
// [
//   ['query' => 'SELECT ... FROM users', 'time' => 0.002],
//   ['query' => 'SELECT ... FROM posts WHERE ...', 'time' => 0.001],
// ]

// Bersihkan log
$db->flushQueryLog();
```

---

## Benchmarking

Ukur performa query atau operasi database:

```php
$result = $db->benchmark(function () use ($db) {
    return $db->select('users', ['*'], [], 'created_at DESC', 1000);
});

echo "Query took: {$result['time']}s";  // "Query took: 0.0234s"
$users = $result['result'];              // Data hasil query
```

---

## Server Information

```php
// Nama database saat ini
$name = $db->getDatabaseName(); // 'my_database'

// Driver (mysql, pgsql, sqlite)
$driver = $db->getDriverName(); // 'mysql'

// Versi server
$version = $db->getServerVersion(); // '8.0.32-MariaDB'

// Ambil PDO instance langsung (untuk advanced use)
$pdo = $db->getPdo();

// Cek apakah tabel ada
$exists = $db->tableExists('users'); // true/false
```

---

## Database Toggle

Framework bisa berjalan **tanpa database** untuk fitur non-DB (static pages, dll):

```php
// Matikan database
Database::setEnabled(false);

// Semua operasi DB akan di-skip tanpa error
// Halaman statis tetap berfungsi normal

// Cek status
Database::isEnabled(); // false

// Nyalakan kembali
Database::setEnabled(true);
```

### Kapan Berguna?

- Saat hosting database sedang maintenance
- Untuk halaman 100% statis (about, contact)
- Saat testing tanpa database
- Emergency mode: tampilkan halaman maintenance

---

## Error Handling

### DatabaseException

Semua error database di-wrap dalam `DatabaseException`:

```php
try {
    $db->query("SELECT * FROM nonexistent_table");
    $db->execute();
} catch (DatabaseException $e) {
    echo $e->getMessage();
    // "SQLSTATE[42S02]: Base table or view not found"
}
```

### Connection Error Diagnostics

Saat koneksi gagal, Database Engine memberikan error message yang informatif:

```
[DATABASE ERROR] Connection refused
═══════════════════════════════════════════
Host     : localhost
Port     : 3306
Database : my_database
User     : root
═══════════════════════════════════════════
Kemungkinan Penyebab:
1. MySQL server belum dijalankan
2. Kredensial database salah
3. Database 'my_database' belum dibuat
═══════════════════════════════════════════
```

### Quote & Escape

```php
// Quote value aman (via PDO::quote)
$safe = $db->quote($userInput);

// Escape nama kolom/tabel
$col = $db->escapeIdentifierSimple('user name'); // `user name`
```

---

## QueryBuilder Integration

```php
// Buat QueryBuilder dari Database instance
$users = $db->table('users')
    ->where('active', 1)
    ->orderBy('name')
    ->limit(10)
    ->get();

// Sama dengan ORM:
// User::where('active', 1)->orderBy('name')->limit(10)->get();
```

---

## Method Reference

### Connection

| Method              | Return     | Deskripsi               |
| ------------------- | ---------- | ----------------------- |
| `getInstance()`     | `Database` | Singleton instance      |
| `testConnection()`  | `bool`     | Test koneksi (no throw) |
| `isConnected()`     | `bool`     | Status koneksi          |
| `reconnect()`       | `void`     | Reconnect ke database   |
| `disconnect()`      | `void`     | Putus koneksi           |
| `setEnabled($bool)` | `void`     | Toggle database on/off  |
| `isEnabled()`       | `bool`     | Status toggle           |

### CRUD Shortcuts

| Method                               | Return  | Deskripsi                          |
| ------------------------------------ | ------- | ---------------------------------- |
| `insert($table, $data)`              | `bool`  | Insert satu baris                  |
| `update($table, $data, $where)`      | `int`   | Update baris, return affected rows |
| `delete($table, $where)`             | `int`   | Delete baris, return affected rows |
| `select($table, $cols, $where, ...)` | `array` | Select dengan filter               |

### Raw Query

| Method                        | Return          | Deskripsi                |
| ----------------------------- | --------------- | ------------------------ |
| `query($sql)`                 | `void`          | Prepare statement        |
| `bind($param, $value, $type)` | `void`          | Bind parameter           |
| `execute()`                   | `bool`          | Execute statement        |
| `resultSet()`                 | `array`         | Fetch banyak baris       |
| `single()`                    | `object\|false` | Fetch satu baris         |
| `rowCount()`                  | `int`           | Jumlah baris affected    |
| `lastInsertId()`              | `string`        | Last auto-increment ID   |
| `statement($sql, $bindings)`  | `bool`          | Execute DDL/non-query    |
| `unprepared($sql)`            | `bool`          | Execute raw (no binding) |
| `raw($value)`                 | `Expression`    | Raw SQL expression       |
| `quote($value)`               | `string`        | Quote value aman         |

### Transactions

| Method                              | Return  | Deskripsi                |
| ----------------------------------- | ------- | ------------------------ |
| `beginTransaction()`                | `bool`  | Mulai transaction        |
| `commit()`                          | `bool`  | Commit transaction       |
| `rollBack()`                        | `bool`  | Rollback transaction     |
| `transaction($callback, $attempts)` | `mixed` | Auto transaction + retry |
| `savepoint($name)`                  | `void`  | Buat savepoint           |
| `rollbackTo($name)`                 | `void`  | Rollback ke savepoint    |
| `releaseSavepoint($name)`           | `void`  | Hapus savepoint          |

### Info & Debug

| Method                 | Return         | Deskripsi            |
| ---------------------- | -------------- | -------------------- |
| `getDatabaseName()`    | `string`       | Nama database        |
| `getDriverName()`      | `string`       | Driver (mysql/pgsql) |
| `getServerVersion()`   | `string`       | Versi server         |
| `getPdo()`             | `PDO`          | Raw PDO instance     |
| `tableExists($table)`  | `bool`         | Cek tabel ada        |
| `table($name)`         | `QueryBuilder` | Buat QueryBuilder    |
| `getQueryLog()`        | `array`        | Log query            |
| `flushQueryLog()`      | `void`         | Clear query log      |
| `benchmark($callback)` | `array`        | Benchmark query      |
| `summary()`            | `string`       | Ringkasan status     |

---

## 🔗 Related Documentation

- [Database (Basic)](database.md) — Panduan dasar database
- [Query Builder](query-builder.md) — Fluent query builder
- [ORM & Models](orm.md) — Active Record ORM
- [Migrations](migrations.md) — Database version control
- [Schema Builder](schema-builder.md) — Tipe kolom, indexes, foreign keys

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
