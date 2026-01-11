# Database & Models

The Framework menyediakan layer database yang powerful namun ringan, menggabungkan fleksibilitas Query Builder dengan kenyamanan Active Record ala Eloquent.

## Konfigurasi

Pastikan konfigurasi database di `.env` sudah sesuai:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nama_database
DB_USER=root
DB_PASS=password
```

## Basic Usage (Query Builder)

Anda bisa menggunakan Query Builder secara langsung tanpa membaut Model class, namun disarankan menggunakan Model.

```php
use TheFramework\App\Database;
use TheFramework\App\QueryBuilder;

$db = Database::getInstance();
$query = new QueryBuilder($db);

$users = $query->table('users')
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'desc')
    ->get(); // Mengembalikan array of arrays
```

---

## Models

Model adalah representasi PHP dari tabel database Anda. Model disimpan di `app/Models/`.

### Membuat Model

Buat file baru misal `User.php` di `app/Models/`:

```php
namespace TheFramework\Models;

use TheFramework\App\Model;

class User extends Model
{
    protected $table = 'users'; // Opsional jika nama class = nama tabel (singular/plural handled)
    protected $primaryKey = 'id';

    // Kolom yang boleh diisi via create/update (Mass Assignment Protection)
    protected $fillable = ['name', 'email', 'password', 'status'];

    // Kolom yang disembunyikan saat toArray/JSON
    protected $hidden = ['password'];
}
```

### Retrieving Data

```php
// Ambil semua data
$users = User::all();

// Cari berdasarkan Primary Key
$user = User::find(1);

// Query Builder Chain
$activeUsers = User::where('status', 'active')
    ->orderBy('name', 'asc')
    ->limit(10)
    ->get();

// Pagination
$users = User::paginate(15); // 15 item per halaman
```

### Creating & Updating

```php
// Create
$newUser = User::create([
    'name' => 'Chandra',
    'email' => 'chandra@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT)
]);

// Update
User::where('id', 1)->update(['status' => 'inactive']);

// Delete
User::delete(1); // Delete by ID
User::where('status', 'banned')->delete(); // Bulk Delete
```

---

## Relationships

Framework ini mendukung definisi relasi antar tabel untuk memudahkan pengambilan data terkait.

### Mendefinisikan Relasi

```php
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function profile() {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

class Post extends Model {
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

### Eager Loading (N+1 Problem Solution)

Gunakan `with()` untuk mengambil data relasi secara efisien (hanya 2 query SQL, bukan N+1).

```php
$users = User::with(['posts', 'profile'])->get();

foreach($users as $user) {
    echo $user['name'];
    // Data posts sudah tersedia, tidak ada query tambahan
    foreach($user['posts'] as $post) {
        echo $post['title'];
    }
}
```

---

## 🚀 Advanced Features

### Automatic Query Caching

Fitur baru di versi 4.0! Anda bisa men-cache hasil query database untuk performa maksimal.

```php
// Cache hasil query selama 1 jam (3600 detik)
$stats = Transaction::where('status', 'success')
    ->remember(3600)
    ->count();

// Cache list produk populer selama 10 menit
$products = Product::where('is_popular', 1)
    ->with(['category'])
    ->remember(600)
    ->get();
```

_Note: Cache akan otomatis dibuat unik berdasarkan SQL query dan binding parameternya._

### Pessimistic Locking

Untuk mencegah Race Condition pada sistem saldo/stok.

```php
$db->beginTransaction();

$product = Product::where('id', 1)
    ->lockForUpdate() // Menambahkan FOR UPDATE
    ->first();

// Lakukan update stok aman...

$db->commit();
```
