# 🛠️ Utility Classes (Arr, Str, Collection, DatabaseHelper)

Framework menyediakan **4 utility class** untuk manipulasi data tingkat lanjut. Class-class ini menyediakan method static (Arr, Str, DatabaseHelper) dan fluent interface (Collection) yang mempermudah operasi data sehari-hari.

---

## 📋 Daftar Isi

1. [Arr — Array Helper](#arr--array-helper)
2. [Str — String Helper](#str--string-helper)
3. [Collection — Fluent Data Wrapper](#collection--fluent-data-wrapper)
4. [DatabaseHelper — Database Utility](#databasehelper--database-utility)

---

## Arr — Array Helper

`TheFramework\Helpers\Arr`

Manipulasi array tingkat tinggi dengan dukungan **dot notation** (akses nested array menggunakan titik).

### `Arr::get()` — Ambil Data (Dot Notation)

```php
use TheFramework\Helpers\Arr;

$config = [
    'database' => [
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306
        ]
    ]
];

Arr::get($config, 'database.mysql.host');         // 'localhost'
Arr::get($config, 'database.mysql.port');         // 3306
Arr::get($config, 'database.pgsql.host', 'N/A');  // 'N/A' (default)
```

### `Arr::set()` — Set Data (Dot Notation)

```php
$data = [];

Arr::set($data, 'user.name', 'Chandra');
Arr::set($data, 'user.email', 'admin@example.com');
Arr::set($data, 'user.preferences.theme', 'dark');

// $data = [
//   'user' => [
//     'name' => 'Chandra',
//     'email' => 'admin@example.com',
//     'preferences' => ['theme' => 'dark']
//   ]
// ]
```

### `Arr::has()` — Cek Key Ada

```php
Arr::has($config, 'database.mysql.host');  // true
Arr::has($config, 'database.pgsql');       // false
```

### `Arr::forget()` — Hapus Key

```php
Arr::forget($data, 'user.preferences');
Arr::forget($data, ['user.name', 'user.email']); // Hapus multiple
```

### `Arr::only()` — Ambil Subset

```php
$user = ['name' => 'Chandra', 'email' => 'a@b.com', 'password' => 'secret'];

Arr::only($user, ['name', 'email']);
// ['name' => 'Chandra', 'email' => 'a@b.com']
```

### `Arr::except()` — Exclude Subset

```php
Arr::except($user, ['password']);
// ['name' => 'Chandra', 'email' => 'a@b.com']
```

### `Arr::dot()` — Flatten Nested Array

```php
$nested = [
    'app' => [
        'name' => 'My App',
        'debug' => true,
        'database' => ['host' => 'localhost']
    ]
];

Arr::dot($nested);
// [
//   'app.name' => 'My App',
//   'app.debug' => true,
//   'app.database.host' => 'localhost'
// ]
```

### Method Reference — Arr

| Method                             | Return  | Deskripsi                   |
| ---------------------------------- | ------- | --------------------------- |
| `Arr::has($array, $key)`           | `bool`  | Cek key ada (dot notation)  |
| `Arr::get($array, $key, $default)` | `mixed` | Ambil value (dot notation)  |
| `Arr::set(&$array, $key, $value)`  | `array` | Set value (dot notation)    |
| `Arr::forget(&$array, $keys)`      | `void`  | Hapus key(s) (dot notation) |
| `Arr::only($array, $keys)`         | `array` | Ambil subset                |
| `Arr::except($array, $keys)`       | `array` | Exclude subset              |
| `Arr::dot($array, $prepend)`       | `array` | Flatten ke dot notation     |

---

## Str — String Helper

`TheFramework\Helpers\Str`

Manipulasi string tingkat lanjut dengan dukungan Unicode (multibyte safe).

### `Str::slug()` — URL Slug

```php
use TheFramework\Helpers\Str;

Str::slug('Belajar PHP Framework');    // 'belajar-php-framework'
Str::slug('Halo Dunia!');              // 'halo-dunia'
Str::slug('Hello World', '_');         // 'hello_world'
```

### `Str::random()` — Random String (Crypto-Safe)

```php
Str::random();     // 'a1b2c3d4e5f6g7h8' (16 chars)
Str::random(32);   // 32 karakter random hex
Str::random(8);    // 8 karakter random hex
```

### `Str::limit()` — Potong String

```php
Str::limit('Framework PHP yang modern dan powerful', 20);
// 'Framework PHP yang m...'

Str::limit('Short text', 50);    // 'Short text' (tidak dipotong)
Str::limit('Hello World', 5, ' →');  // 'Hello →'
```

### `Str::contains()` — Cek Substring

```php
Str::contains('Hello World', 'World');        // true
Str::contains('Hello World', 'world');        // false (case sensitive)
Str::contains('Hello World', ['Foo', 'World']); // true (any match)
```

### `Str::startsWith()` / `Str::endsWith()`

```php
Str::startsWith('Hello World', 'Hello');  // true
Str::startsWith('/api/users', '/api');    // true

Str::endsWith('photo.jpg', '.jpg');       // true
Str::endsWith('document.pdf', ['.jpg', '.pdf']); // true
```

### `Str::camel()` — CamelCase

```php
Str::camel('user_name');       // 'userName'
Str::camel('get-all-posts');   // 'getAllPosts'
Str::camel('hello world');     // 'helloWorld'
```

### `Str::snake()` — snake_case

```php
Str::snake('userName');        // 'user_name'
Str::snake('getAllPosts');      // 'get_all_posts'
Str::snake('HTMLParser');       // 'h_t_m_l_parser'
```

### `Str::mask()` — String Masking

```php
// Masking email
Str::mask('chandra@gmail.com', '*', 3, 5);
// 'cha*****gmail.com'

// Masking nomor telepon
Str::mask('081234567890', '*', 4, 6);
// '0812******7890'

// Masking dari belakang
Str::mask('1234567890', '*', -4);
// '123456****'
```

### Method Reference — Str

| Method                                    | Return   | Deskripsi                   |
| ----------------------------------------- | -------- | --------------------------- |
| `Str::slug($title, $sep)`                 | `string` | Buat URL slug               |
| `Str::random($length)`                    | `string` | Random string (crypto-safe) |
| `Str::limit($value, $limit, $end)`        | `string` | Potong string               |
| `Str::contains($haystack, $needles)`      | `bool`   | Cek substring               |
| `Str::startsWith($haystack, $needles)`    | `bool`   | Cek awalan                  |
| `Str::endsWith($haystack, $needles)`      | `bool`   | Cek akhiran                 |
| `Str::camel($value)`                      | `string` | Convert ke camelCase        |
| `Str::snake($value, $delim)`              | `string` | Convert ke snake_case       |
| `Str::mask($string, $char, $index, $len)` | `string` | Masking string              |

---

## Collection — Fluent Data Wrapper

`TheFramework\Helpers\Collection`

Wrapper objek untuk manipulasi array dengan **fluent interface** (method chaining). Mirip Laravel Collection.

### Membuat Collection

```php
use TheFramework\Helpers\Collection;

// Dari array
$collection = Collection::make([1, 2, 3, 4, 5]);
$collection = new Collection(['a', 'b', 'c']);
```

### Iterasi & Akses

```php
$users = Collection::make($userArray);

$users->first();    // User pertama
$users->last();     // User terakhir
$users->count();    // Jumlah items
$users->all();      // Kembali ke array biasa

// Foreach (implements IteratorAggregate)
foreach ($users as $user) {
    echo $user->name;
}

// Array access (implements ArrayAccess)
echo $users[0]->name;
```

### Transformasi

```php
$names = Collection::make($users)
    ->map(fn($user) => $user->name)        // Ambil nama saja
    ->filter(fn($name) => strlen($name) > 3) // Filter nama > 3 huruf
    ->sort()                                  // Urutkan A-Z
    ->all();                                  // Convert ke array
```

### Pluck — Ambil Satu Kolom

```php
$emails = Collection::make($users)->pluck('email');
// Collection: ['admin@mail.com', 'user@mail.com', ...]
```

### Sort & Reverse

```php
$sorted = Collection::make([3, 1, 4, 1, 5])->sort();    // [1, 1, 3, 4, 5]
$desc = Collection::make([1, 2, 3])->reverse();           // [3, 2, 1]

// Custom sort
$byAge = Collection::make($users)->sort(fn($a, $b) => $a->age - $b->age);
```

### JSON Serialization

```php
$json = Collection::make($data)->toJson();
// '["item1","item2","item3"]'

// Otomatis saat json_encode (implements JsonSerializable)
echo json_encode($collection);
```

### Method Reference — Collection

| Method                     | Return       | Deskripsi             |
| -------------------------- | ------------ | --------------------- |
| `Collection::make($items)` | `Collection` | Buat collection baru  |
| `->all()`                  | `array`      | Convert ke array      |
| `->first()`                | `mixed`      | Item pertama          |
| `->last()`                 | `mixed`      | Item terakhir         |
| `->count()`                | `int`        | Jumlah items          |
| `->map($callback)`         | `Collection` | Transform setiap item |
| `->filter($callback)`      | `Collection` | Filter items          |
| `->sort($callback)`        | `Collection` | Urutkan items         |
| `->reverse()`              | `Collection` | Balik urutan          |
| `->pluck($key)`            | `Collection` | Ambil satu kolom      |
| `->toJson()`               | `string`     | Convert ke JSON       |

### Interfaces yang Diimplementasikan

| Interface           | Manfaat                         |
| ------------------- | ------------------------------- |
| `ArrayAccess`       | Akses dengan `$collection[0]`   |
| `IteratorAggregate` | Bisa di-`foreach`               |
| `Countable`         | Bisa pakai `count($collection)` |
| `JsonSerializable`  | Bisa `json_encode($collection)` |

---

## DatabaseHelper — Database Utility

`TheFramework\Helpers\DatabaseHelper`

Helper untuk debugging dan monitoring database. Berguna untuk health check dan audit koneksi.

### Enable / Disable Database

```php
use TheFramework\Helpers\DatabaseHelper;

// Matikan database (untuk mode maintenance)
DatabaseHelper::disable();

// Aktifkan kembali
DatabaseHelper::enable();

// Cek status
DatabaseHelper::isEnabled(); // true/false
```

### Test Connection

```php
// Test cepat (return boolean)
if (DatabaseHelper::testConnection()) {
    echo "Database OK!";
} else {
    echo "Database unreachable";
}
```

### Audit Connection (Detail)

```php
$audit = DatabaseHelper::auditConnection();

// [
//   'connected'  => true,
//   'latency_ms' => 2.45,
//   'version'    => '8.0.32-MariaDB',
//   'error'      => null,
//   'timestamp'  => '2026-02-28 11:45:00'
// ]

// Contoh penggunaan di health check endpoint
echo "Status: " . ($audit['connected'] ? '✅' : '❌');
echo "Latency: {$audit['latency_ms']}ms";
echo "Version: {$audit['version']}";
```

### Method Reference — DatabaseHelper

| Method                              | Return  | Deskripsi            |
| ----------------------------------- | ------- | -------------------- |
| `DatabaseHelper::disable()`         | `void`  | Matikan database     |
| `DatabaseHelper::enable()`          | `void`  | Aktifkan database    |
| `DatabaseHelper::isEnabled()`       | `bool`  | Cek status           |
| `DatabaseHelper::testConnection()`  | `bool`  | Test koneksi cepat   |
| `DatabaseHelper::auditConnection()` | `array` | Audit koneksi detail |

---

## 🔗 Related Documentation

- [Helpers](helpers.md) — Global functions (`Helper` class + `helpers.php`)
- [Database Engine](database-engine.md) — Database class detail
- [ORM](orm.md) — Model operations
- [Query Builder](query-builder.md) — Fluent query builder

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
