# 🗄️ Database Documentation (v5.0)

Database engine dalam The Framework dirancang untuk memberikan performa maksimal dengan sintaks yang sangat mirip dengan Laravel Eloquent, namun tetap ringan untuk dijalankan di shared hosting.

---

## 🏗️ Komponen Utama

Database Framework dibagi menjadi dua lapisan:

1.  **Lapisan Internal (`app/App/Database`)**: Berisi engine utama seperti `QueryBuilder`, `Model`, dan `Connection Handler`.
2.  **Lapisan Developer (`app/Database`)**: Berisi Base Class untuk `Migration` dan `Seeder`.

---

## 🗄️ Konfigurasi `.env`

Pastikan kredensial database Anda sudah terisi di file `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nama_db
DB_USER=root
DB_PASS=password
```

---

## 🏗️ Migrasi (Schema Management)

Migrasi adalah sistem kontrol versi untuk database Anda. Setiap migrasi harus meng-extend `TheFramework\Database\Migration`.

### Contoh File Migrasi:

```php
use TheFramework\Database\Migration;
use TheFramework\App\Schema\Schema;
use TheFramework\App\Schema\Blueprint;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
```

### Navigasi Transaksi:

Migration sekarang mendukung transaksi database secara native:

- `$this->beginTransaction()`
- `$this->commit()`
- `$this->rollback()`

---

## 🌱 Seeding (Populasi Data)

Seeder digunakan untuk mengisi database dengan data awal atau data testing. Base Class `TheFramework\Database\Seeder` memiliki fitur cerdas untuk mengisi data secara _batch_.

### Penggunaan Fluent Seeder:

```php
use TheFramework\Database\Seeder;

class ProductSeeder extends Seeder {
    public function run(): void {
        $this->table('products')->insert([
            ['name' => 'MacBook Pro', 'price' => 25000000],
            ['name' => 'iPhone 15', 'price' => 15000000],
        ]);
    }
}
```

### Memanggil Seeder Lain (`call()`)

Sangat disarankan untuk memisahkan Seeder dan memanggilnya secara terpusat dari satu **DatabaseSeeder**:

```php
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            CategorySeeder::class
        ]);
    }
}
```

Metode `call` secara pintar mendukung pemuatan namespace otomatis jika tidak disertakan, serta melakukan pengecekan jika instance run berhasil digunakan.

### Eksekusi di CLI:

```bash
php artisan db:seed                    # (Otomatis menjalankan DatabaseSeeder)
php artisan db:seed UserSeeder         # (Menjalankan Seeder spesifik)
```

## 📊 Query Builder & ORM

The Framework menyertakan Query Builder yang sangat kuat dan ORM yang mendukung Relationship.

### Contoh Query Builder:

```php
use TheFramework\App\Database\Database;

$users = Database::table('users')
    ->where('active', 1)
    ->orderBy('name', 'desc')
    ->get();
```

### Contoh ORM (Model):

```php
use TheFramework\Models\User;

// Ambil semua user beserta post mereka (Eager Loading)
$users = User::with('posts')->get();

// Create data baru
User::create([
    'name' => 'Chandra',
    'email' => 'chandra@example.com'
]);
```

---

## 🔍 Debugging

Jika terjadi error database, Framework akan menampilkan **Premium Error UI** yang menunjukkan:

- Query SQL yang gagal.
- Parameter Bindings yang dikirim.
- Detail konfigurasi `.env` yang digunakan.
