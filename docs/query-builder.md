# 🔍 Query Builder

Query Builder framework menyediakan antarmuka yang lancar (_fluent interface_) untuk membuat dan menjalankan query database. Secara otomatis menggunakan **PDO Prepared Statements** untuk melindungi aplikasi Anda dari serangan SQL Injection.

---

## 🚀 Memulai

Anda dapat mengakses Query Builder melalui Model atau melalui class `Database`.

```php
use TheFramework\Models\User;

$users = User::query()->select('name', 'email')->get();
```

---

## 📑 Daftar Perintah

### Select

```php
$builder->select('id', 'name as full_name');
$builder->distinct(); // Ambil hasil unik
```

### Where Clauses

```php
$builder->where('status', '=', 'active');
$builder->orWhere('role', '=', 'admin');
$builder->whereIn('id', [1, 2, 3]);
$builder->whereNotIn('category', ['draft', 'deleted']);
```

### Raw Queries (⚠️ Gunakan dengan Hati-hati)

Gunakan `whereRaw` jika Anda butuh ekspresi SQL yang kompleks. Pastikan menggunakan _bindings_ untuk keamanan.

```php
$builder->whereRaw('age > ? AND points < ?', [18, 100]);
```

### Joins

Framework mendukung berbagai tipe JOIN:

```php
// INNER JOIN (default) - hanya data yang cocok di kedua tabel
$builder->join('profiles', 'users.id', '=', 'profiles.user_id');
$builder->innerJoin('profiles', 'users.id', '=', 'profiles.user_id');

// LEFT JOIN - semua data dari tabel kiri
$builder->leftJoin('posts', 'users.id', '=', 'posts.user_id');

// RIGHT JOIN - semua data dari tabel kanan
$builder->rightJoin('posts', 'users.id', '=', 'posts.user_id');

// LEFT OUTER JOIN (sama dengan LEFT JOIN)
$builder->leftOuterJoin('posts', 'users.id', '=', 'posts.user_id');

// RIGHT OUTER JOIN (sama dengan RIGHT JOIN)
$builder->rightOuterJoin('posts', 'users.id', '=', 'posts.user_id');

// FULL OUTER JOIN - semua data dari kedua tabel
// Note: MySQL tidak support native, framework akan gunakan UNION
$builder->fullOuterJoin('posts', 'users.id', '=', 'posts.user_id');

// CROSS JOIN - cartesian product (tidak perlu ON clause)
$builder->crossJoin('categories');
```

**Contoh Praktis:**

```php
// Contoh: Ambil semua posts dengan nama author
$posts = Post::query()
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author')
    ->get();

// Multiple joins
$posts = Post::query()
    ->leftJoin('users', 'posts.user_id', '=', 'users.id')
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
    ->select('posts.*', 'users.name as author', 'categories.name as category')
    ->where('posts.published', '=', true)
    ->get();
```

### Ordering & Grouping

```php
$builder->orderBy('created_at', 'DESC');
$builder->orderByRaw('RAND()');
$builder->groupBy('category_id');
```

### Pagination

Framework memudahkan pembuatan sistem halaman.

```php
$results = User::query()->paginate(15, $currentPage);

// Hasil berupa array:
// ['data' => [...], 'total' => 100, 'last_page' => 7, ...]
```

---

## 💾 Eksekusi

| Method          | Deskripsi                                         |
| :-------------- | :------------------------------------------------ |
| `get()`         | Mengambil semua hasil sebagai array object/model. |
| `first()`       | Mengambil baris pertama saja.                     |
| `count()`       | Menghitung jumlah total baris.                    |
| `pluck('col')`  | Mengambil array nilai dari satu kolom saja.       |
| `insert($data)` | Menambah data baru.                               |
| `update($data)` | Mengubah data (Wajib didahului `where`).          |
| `delete()`      | Menghapus data (Wajib didahului `where`).         |

---

## 🔒 Advanced Features

### Pessimistic Locking

Mencegah _race condition_ pada data sensitif (misal: stok produk atau saldo).

```php
// Harus dijalankan di dalam Database Transaction
User::query()
    ->where('id', '=', 1)
    ->lockForUpdate()
    ->first();
```

### Query Caching

Simpan hasil query di cache untuk mempercepat aplikasi.

```php
$posts = Post::query()
    ->where('category', 'news')
    ->remember(3600) // Simpan selama 1 jam
    ->get();
```

### Eager Loading

Mencegah masalah N+1 query dengan me-load relasi sekaligus.

```php
$posts = Post::query()->with(['author', 'comments'])->get();
```
