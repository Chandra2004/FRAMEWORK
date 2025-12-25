# DATABASE & ORM GUIDE

Framework ini memiliki Database Layer yang kuat namun ringan wrapper di atas `PDO Native`, memberikan kontrol penuh atas Query SQL (Raw) sekaligus kemudahan ala Query Builder (Eloquent-like).

## Koneksi Database

Konfigurasi database ada di file `.env`:

```ini
DB_HOST=127.0.0.1
DB_DATABASE=mydatabase
DB_USERNAME=root
DB_PASSWORD=secret
```

Koneksi bersifat **Lazy Loading**: Koneksi ke database server TIDAK akan dibuat sampai query pertama dijalankan. Ini sangat menghemat resource.

## Menggunakan Model

Model menghubungkan kode OOP Anda dengan Tabel di database.

### 1. Membuat Model

Gunakan Artisan generator:

```bash
php artisan make:model Product
```

Ini akan membuat file `app/Models/Product.php`.

### 2. Struktur Model

Secara default, model akan menebak nama tabel (skema `Snake Case Plural`, misal `Product` -> `products`).

```php
namespace TheFramework\Models;

use TheFramework\App\Model;

class Product extends Model
{
    // Override jika nama tabel beda
    // protected $table = 'master_products';

    // Override primary key (default 'id')
    // protected $primaryKey = 'uuid';
}
```

## CRUD Operation

```php
// 1. Create
$product = new Product();
$product->insert([
    'name' => 'Laptop Gaming',
    'price' => 15000000
]);

// 2. Read (All)
$products = $product->all();

// 3. Read (Find by ID)
$item = $product->find(1);

// 4. Update
$product->update(['price' => 14000000], 1); // ID 1

// 5. Delete
$product->delete(1);
```

## Query Builder (Fluent Interface)

Anda bisa melakukan chaining method query yang kompleks:

```php
$users = (new User())->query()
    ->select('name', 'email')
    ->where('active', '=', 1)
    ->where('created_at', '>=', '2024-01-01')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get(); // Eksekusi dan return array
```

Method tersedia:

- `where($col, $operator, $val)`
- `orWhere(...)`
- `whereIn($col, array)`
- `join($table, $first, $operator, $second)`
- `leftJoin(...)`
- `groupBy(...)`
- `having(...)`
- `orderBy($col, $directon)`
- `limit($int)`
- `offset($int)`

## Advanced Features

### 1. Pessimistic Locking

Untuk transaksi keuangan atau stok (Flash Sale), gunakan `lockForUpdate` untuk mencegah Race Condition.

```php
$db->beginTransaction();

// Select... FOR UPDATE
$wallet = $this->query()->where('uid', '=', $uid)->lockForUpdate()->first();

// Update saldo aman
$this->update(['saldo' => $wallet['saldo'] - 100], $uid);

$db->commit();
```

### 2. Transaction Management

```php
try {
    $db->beginTransaction();
    // ... multiple queries ...
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

---

## Migrations

Mengelola skema database via kode (Version Control untuk Database).

```bash
# Buat migration baru
php artisan make:migration CreateOrdersTable

# Jalankan migrasi
php artisan migrate

# Rollback langkah terakhir
php artisan migrate:rollback
```

Contoh File Migrasi:

```php
Schema::create('orders', function($table) {
    $table->id(); // Auto increment ID
    $table->string('code')->unique();
    $table->integer('total_amount');
    $table->timestamps(); // created_at, updated_at
});
```

## Seeding

Mengisi database dengan data dummy untuk testing.

```bash
# Buat seeder
php artisan make:seeder UserSeeder

# Jalankan seeder
php artisan db:seed
```
