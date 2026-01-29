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

```php
$builder->join('profiles', 'users.id', '=', 'profiles.user_id');
$builder->join('posts', 'users.id', '=', 'posts.user_id', 'LEFT');
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
