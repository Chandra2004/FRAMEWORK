# 🗄️ Database & Query Builder

The Framework menyediakan akses database yang aman dan ekspresif menggunakan PDO Wrapper dan Query Builder. Mendukung MySQL/MariaDB.

---

## 📋 Daftar Isi

1.  [Konfigurasi](#konfigurasi)
2.  [Menjalankan Raw SQL](#menjalankan-raw-sql)
3.  [Query Builder (Fluent)](#query-builder-fluent)
4.  [Database Transactions](#database-transactions)
5.  [Model (Active Record)](#model-active-record)

---

## Konfigurasi

Pastikan `.env` sudah diisi dengan benar.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=the_framework_db
DB_USER=root
DB_PASS=secret
DB_TIMEZONE=+07:00
```

---

## Menjalankan Raw SQL

Untuk query yang sangat kompleks, Anda bisa mengeksekusi SQL mentah secara aman (Prepared Statement).

```php
use TheFramework\App\Database;

// Select
$users = Database::query("SELECT * FROM users WHERE active = ?", [1]);

// Insert/Update/Delete
Database::execute("UPDATE users SET active = ? WHERE id = ?", [0, 5]);
```

---

## Query Builder & Model

Di framework ini, cara utama berinteraksi dengan database adalah melalui **Model**. Anda jarang perlu memanggil `QueryBuilder` secara manual, kecuali untuk query kompleks yang tidak terkait model tertentu.

### Insert Data (Create)

Menggunakan Model (Disarankan):

```php
use TheFramework\Models\User;

// Cara 1: Create (Return User object)
$user = User::create([
    'email' => 'john@example.com',
    'votes' => 0
]);
echo $user->id; // Auto-generated ID

// Cara 2: Instance + Save
$user = new User();
$user->email = 'jane@example.com';
$user->save(); // Auto INSERT

// Cara 3: Mass Fill
$user = new User();
$user->fill(['email' => 'bob@example.com', 'votes' => 5]);
$user->save();
```

### Mengupdate Data (Update)

```php
// Cara 1: Active Record (Instance Update) - RECOMMENDED
$user = User::find(1);
$user->email = 'newemail@example.com';
$user->votes = 100;
$user->save(); // Auto UPDATE, return true/false

// Cara 2: Batch Update
$affected = User::where('active', 0)->update(['active' => 1]);
// Return: integer (jumlah row yang diupdate)
```

### Menghapus Data (Delete)

```php
// Cara 1: Active Record (Instance Delete)
$user = User::find(1);
$user->delete(); // Return: affected rows

// Cara 2: Batch Delete
$deleted = User::where('votes', '<', 5)->delete();
// Return: integer (jumlah row yang dihapus)
```

### Mengambil Data (Read)

```php
// Ambil semua data
$users = User::all();

// Ambil berdasarkan ID
$user = User::find(1);

// Query Fluent (Chainable)
$admins = User::where('role', 'admin')
              ->orderBy('created_at', 'DESC')
              ->get();
```

---

## Query Builder (Low Level)

Jika Anda ingin melakukan query tanpa Model (misal ke tabel pivot atau report), Anda bisa mengaksesnya via helper `Database`.

```php
use TheFramework\Database\QueryBuilder;

QueryBuilder::table('logs')->insert(['action' => 'login']);
```

---

## Database Transactions

Memastikan sekumpulan operasi database berhasil semua atau gagal semua (Atomic).

```php
try {
    Database::beginTransaction();

    // Operasi 1: Potong saldo
    User::where('id', 1)->decrement('balance', 5000);

    // Operasi 2: Tambah riwayat
    Transaction::create(['amount' => 5000]);

    Database::commit(); // Simpan perubahan
} catch (\Exception $e) {
    Database::rollback(); // Batalkan semua jika ada error
}
```

---

## Model (Active Record)

Model adalah cara berinteraksi dengan tabel layaknya sebuah Objek.

Extends class `TheFramework\Database\Model`.

```php
class User extends Model {
    protected $table = 'users'; // Nama tabel (opsional, auto-plural)
    protected $primaryKey = 'id';
}
```

### Penggunaan Model

Sama seperti Query Builder, tapi lebih ringkas.

```php
// Ambil semua
$all = User::all();

// Find by Primary Key
$user = User::find(1);

// Static call ke Query Builder
$active = User::where('active', 1)->get();
```

---

## Hubungan Antar Model (ORM Relationships)

Framework ini mendukung relasi antar tabel yang ekspresif, mirip Eloquent di Laravel.

### 1. One to One (Satu ke Satu)

Contoh: Satu `User` memiliki satu `Phone`.

Di Model `User`:

```php
public function phone() {
    return $this->hasOne(Phone::class, 'user_id', 'id');
}
```

Di Model `Phone`:

```php
public function user() {
    return $this->belongsTo(User::class, 'user_id', 'id');
}
```

### 2. One to Many (Satu ke Banyak)

Contoh: Satu `Post` memiliki banyak `Comment`.

Di Model `Post`:

```php
public function comments() {
    return $this->hasMany(Comment::class, 'post_id', 'id');
}
```

Di Model `Comment` (Inverse):

```php
public function post() {
    return $this->belongsTo(Post::class, 'post_id', 'id');
}
```

### 3. Many to Many (Banyak ke Banyak)

Contoh: `User` memiliki banyak `Role`, dan `Role` dimiliki banyak `User`.
Membutuhkan tabel pivot (misal: `role_user`).

Di Model `User`:

```php
public function roles() {
    return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
}
```

---

## Mengakses Relasi

Saat Anda memanggil relasi sebagai properti dinamis, framework otomatis menjalankan querynya.

```php
$post = Post::find(1);

// Lazy Loading: Akses sebagai property
$comments = $post->comments; // Auto query

foreach ($comments as $comment) {
    echo $comment->body;
}

// Relation Chaining: Akses sebagai method
$publishedComments = $post->comments()
    ->where('approved', 1)
    ->orderBy('created_at', 'DESC')
    ->get();

// Create via Relation (Auto set foreign key!)
$post->comments()->create([
    'body' => 'Great post!',
    'user_id' => 1
]); // Otomatis set post_id = $post->id
```

---

## Eager Loading (N+1 Problem Solver)

Jika Anda meloop 100 Post dan mengakses `$post->author`, framework akan menjalankan 100 query tambahan. Ini lambat!

Gunakan `with()` untuk memuat data di awal (hanya 2 query total: 1 untuk post, 1 untuk semua author).

```php
// Buruk (N+1 Query)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}

// Bagus (Eager Loading)
$posts = Post::with(['author', 'comments'])->get();
foreach ($posts as $post) {
    echo $post->author->name; // Tidak ada query tambahan di sini
}
```

Anda bahkan bisa melakukan Eager Loading bersarang (Nested):

```php
// Ambil Post beserta Author-nya, DAN Profile dari Author tersebut
$posts = Post::with(['author.profile'])->get();
```
