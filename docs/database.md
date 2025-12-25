# 🗄️ Database & Models Guide

Panduan lengkap pengelolaan database di TheFramework, mulai dari Schema, Seeding, hingga ORM.

## 1. Konfigurasi

Database dikonfigurasi melalui file `.env`. Framework mendukung koneksi MySQL/MariaDB secara default.

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=the_framework
DB_USER=root
DB_PASS=
```

---

## 2. Migrations

Migration adalah version control untuk struktur database Anda.

### Membuat Migration Baru

Gunakan perintah Artisan:

```bash
php artisan make:migration CreateProductsTable
```

File akan dibuat di `database/migrations/`.

### Struktur File Migration

```php
use TheFramework\Database\Migration;
use TheFramework\App\Blueprint;
use TheFramework\App\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // Primary Key Auto Increment
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
```

### Menjalankan Migration

```bash
php artisan migrate           # Jalankan semua migrasi pending
php artisan migrate:rollback  # Batalkan migrasi terakhir
php artisan migrate:fresh     # Hapus semua tabel & migrasi ulang
```

---

## 3. Seeders

Seeder digunakan untuk mengisi database dengan data awal atau data dummy.

### Membuat Seeder

```bash
php artisan make:seeder ProductSeeder
```

### Struktur File Seeder

```php
namespace TheFramework\Database;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Cara 1: Insert Single
        $this->db->table('products')->insert([
            'name' => 'Kopi Arabika',
            'price' => 50000,
            'stock' => 100
        ]);

        // Cara 2: Panggil Seeder Lain
        // $this->call(CategorySeeder::class);
    }
}
```

### Menjalankan Seeder

```bash
php artisan db:seed                # Jalankan semua seeder terdaftar
php artisan db:seed ProductSeeder  # Jalankan seeder spesifik
```

---

## 4. Models (ORM)

Model adalah representasi PHP dari tabel database.

### Membuat Model

```bash
php artisan make:model Product
```

### Konfigurasi Model

```php
namespace TheFramework\Models;

use TheFramework\App\Model;

class Product extends Model
{
    protected $table = 'products';     // Nama tabel (default: plural)
    protected $primaryKey = 'id';      // Primary Key (default: id)
    protected $fillable = ['name', 'price', 'stock']; // White-list kolom
}
```

### CRUD Operations

#### Read (Mengambil Data)

```php
// Ambil semua
$all = Product::all();

// Filter
$active = Product::where('stock', '>', 0)->get();

// Single Record
$item = Product::find(1);
$item = Product::where('email', 'user@example.com')->first();

// Ordering & Limit
$latest = Product::orderBy('created_at', 'DESC')->limit(5)->get();
```

#### Create (Menambah Data)

```php
$product = Product::create([
    'name' => 'Meja Belajar',
    'price' => 750000
]);
```

#### Update (Mengubah Data)

```php
// Cara 1: Via Instance
$product = Product::find(1);
$product->name = 'Meja Baru';
$product->save();

// Cara 2: Mass Update
Product::where('category_id', 5)->update(['active' => 0]);
```

#### Delete (Menghapus Data)

```php
// Cara 1: Via Instance
$product = Product::find(1);
$product->delete();

// Cara 2: Mass Delete
Product::where('stock', 0)->delete();
```

---

## 5. Relationships

Framework mendukung relasi antar tabel dasar.

### One to Many

Contoh: 1 Kategori punya banyak Produk.

**Di Model Category:**

```php
public function products()
{
    return $this->hasMany(Product::class, 'category_id', 'id');
}
```

**Penggunaan:**

```php
$category = Category::find(1);
$products = $category->products(); // Mengambil array produk
```

### Belongs To (Many to One)

Contoh: Produk milik 1 Kategori.

**Di Model Product:**

```php
public function category()
{
    return $this->belongsTo(Category::class, 'category_id', 'id');
}
```

---

## 6. Lanjutan

### Pagination

Membuat pagination otomatis sangat mudah.

```php
// Di Controller
$page = request('page', 1);
$limit = 10;
$products = Product::paginate($limit, $page);

// Output JSON otomatis ada metadata: total, per_page, current_page, last_page
return Helper::json($products);
```

### Transactions

Gunakan Database Transaction untuk memastikan integritas data (All or Nothing).

```php
use TheFramework\App\Database;

try {
    Database::beginTransaction();

    $user = User::create([...]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0]);

    Database::commit(); // Simpan permanen
} catch (\Exception $e) {
    Database::rollBack(); // Batalkan semua perubahan jika error
    throw $e;
}
```

### Pessimistic Locking

Mencegah race condition saat trafik tinggi (misal: sistem flash sale).

```php
Database::beginTransaction();

// lockForUpdate() menahan baris ini agar tidak dibaca/diedit proses lain
$product = Product::where('id', 1)->lockForUpdate()->first();

if ($product->stock > 0) {
    $product->stock -= 1;
    $product->save();
    Database::commit();
} else {
    Database::rollBack();
}
```
