# ⚡ Cache — Sistem Caching

Cache menyimpan data yang sering diakses ke penyimpanan yang lebih cepat, sehingga aplikasi tidak perlu menghitung ulang atau query database berulang kali.

---

## 📑 Daftar Isi

- [Konfigurasi](#konfigurasi)
- [Basic Operations](#basic-operations)
- [Remember Pattern](#remember-pattern)
- [Batch Operations](#batch-operations)
- [Counter Operations](#counter-operations)
- [Tags](#tags)
- [Atomic Locks](#atomic-locks)
- [Garbage Collection](#garbage-collection)
- [Statistik & Monitoring](#statistik--monitoring)
- [Fitur Beyond Laravel 🚀](#fitur-beyond-laravel-)

---

## Konfigurasi

### Driver

CacheManager mendukung 3 driver:

| Driver           | Kecepatan | Persisten | Use Case                                   |
| ---------------- | --------- | --------- | ------------------------------------------ |
| `file` (default) | ⚡⚡      | ✅ Ya     | Production — shared hosting                |
| `array`          | ⚡⚡⚡    | ❌ Tidak  | Testing — data hilang saat request selesai |
| `database`       | ⚡        | ✅ Ya     | Multi-server — shared cache via DB         |

```php
use TheFramework\App\Cache\CacheManager;

// Set driver
CacheManager::driver('file');      // Default
CacheManager::driver('array');     // Untuk testing
CacheManager::driver('database');  // Jika perlu shared cache

// Untuk database driver, buat tabel dulu
CacheManager::createCacheTable();
```

### Konfigurasi Lainnya

```php
// Custom cache directory (file driver)
CacheManager::setCacheDir('/path/to/cache');

// Set prefix untuk semua keys
CacheManager::setPrefix('myapp_');

// Set default TTL (detik)
CacheManager::setDefaultTtl(7200); // 2 jam
```

---

## Basic Operations

### Simpan & Ambil

```php
// Simpan ke cache (TTL 1 jam)
CacheManager::put('user.1', $userData, 3600);

// Simpan tanpa expiry
CacheManager::forever('settings', $appSettings);

// Ambil dari cache
$user = CacheManager::get('user.1');          // null jika tidak ada
$user = CacheManager::get('user.1', 'guest'); // 'guest' jika tidak ada

// Default bisa berupa Closure
$user = CacheManager::get('user.1', fn() => User::find(1));
```

### Cek Keberadaan

```php
if (CacheManager::has('user.1')) {
    // Key ada dan belum expired
}

if (CacheManager::missing('user.1')) {
    // Key tidak ada atau sudah expired
}
```

### Simpan Kondisional

```php
// Simpan HANYA jika key belum ada
$stored = CacheManager::add('lock.key', 'value', 60);
// true = berhasil, false = key sudah ada
```

### Ambil & Hapus

```php
// Ambil nilainya lalu langsung hapus dari cache
$token = CacheManager::pull('one-time-token');
```

### Hapus

```php
// Hapus satu key
CacheManager::forget('user.1');

// Hapus banyak key sekaligus
CacheManager::forget(['user.1', 'user.2', 'settings']);

// Hapus SEMUA cache
CacheManager::flush();
```

---

## Remember Pattern

Pattern yang paling sering dipakai: ambil dari cache, jika tidak ada jalankan callback dan simpan hasilnya.

```php
// Remember dengan TTL
$users = CacheManager::remember('all_users', 3600, function () {
    return User::all(); // Query hanya dijalankan jika cache miss
});

// Remember forever
$config = CacheManager::rememberForever('app_config', function () {
    return Config::all();
});

// Refresh: hapus cache lama, generate ulang
$users = CacheManager::refresh('all_users', function () {
    return User::all();
}, 3600);
```

---

## Batch Operations

```php
// Ambil banyak sekaligus
$data = CacheManager::many(['user.1', 'user.2', 'user.3']);
// ['user.1' => ..., 'user.3' => ...] (yang miss tidak masuk)

// Simpan banyak sekaligus
CacheManager::putMany([
    'user.1' => $user1,
    'user.2' => $user2,
    'user.3' => $user3,
], 3600);
```

---

## Counter Operations

Operasi atomic increment/decrement — sangat berguna untuk counter, view count, rate limiting.

```php
// Increment (otomatis membuat key jika belum ada)
$newCount = CacheManager::increment('page_views');     // 1
$newCount = CacheManager::increment('page_views');     // 2
$newCount = CacheManager::increment('page_views', 5);  // 7

// Decrement
$newCount = CacheManager::decrement('stock', 1);  // 6
```

---

## Tags

Grouping cache keys dengan tags memungkinkan Anda menghapus cache secara selektif.

```php
// Simpan dengan tags
CacheManager::tags(['users', 'admins'])->put('admin.1', $admin, 3600);
CacheManager::tags(['users'])->put('user.1', $user, 3600);
CacheManager::tags(['products'])->put('product.1', $product, 3600);

// Ambil cached data
$admin = CacheManager::tags(['users', 'admins'])->get('admin.1');

// Remember dengan tags
$users = CacheManager::tags(['users'])->remember('all_users', 3600, function () {
    return User::all();
});

// Flush hanya cache bertag 'users' (admin.1 DAN user.1 terhapus, product.1 aman)
CacheManager::tags(['users'])->flush();

// Cek keberadaan
CacheManager::tags(['users'])->has('user.1'); // false (sudah di-flush)
CacheManager::tags(['products'])->has('product.1'); // true (aman!)
```

---

## Atomic Locks

Mencegah race condition saat multiple request mengakses resource yang sama.

```php
// Coba acquire lock
$lock = CacheManager::lock('process-order', 10); // Lock selama 10 detik

if ($lock->get()) {
    // Berhasil dapat lock — jalankan operasi
    processOrder($orderId);
    $lock->release();
}

// Atau dengan callback (auto-release)
CacheManager::lock('send-report', 30)->get(function () {
    // Lock otomatis di-release setelah callback selesai
    generateReport();
});

// Block: tunggu sampai lock tersedia (max 5 detik)
CacheManager::lock('deploy', 60)->block(5, function () {
    // Akan menunggu max 5 detik untuk mendapatkan lock
    deployApplication();
});

// Cek status lock
$lock = CacheManager::lock('process');
$lock->isLocked();    // true/false
$lock->getOwner();    // owner ID
$lock->forceRelease(); // Paksa release (abaikan ownership)
```

### Use Case: Prevent Double Submit

```php
public function submitOrder(Request $request)
{
    $lockKey = 'order:' . $request->user_id;
    $lock = CacheManager::lock($lockKey, 10);

    if (!$lock->get()) {
        return response('Order sedang diproses, mohon tunggu.', 429);
    }

    try {
        $order = Order::create($request->data);
        return response('Order berhasil!');
    } finally {
        $lock->release();
    }
}
```

---

## Garbage Collection

File cache yang expired tidak otomatis terhapus. Gunakan GC untuk membersihkannya.

```php
// Manual GC
$deleted = CacheManager::gc(); // Return: jumlah file yang dihapus

// Probabilistic GC (dijalankan 1 dari 100 request)
CacheManager::gcProbabilistic(100);
```

**Tip:** Letakkan `gcProbabilistic(100)` di middleware atau bootstrap agar GC berjalan otomatis tanpa mempengaruhi performa.

---

## Statistik & Monitoring

```php
// Statistik hit/miss
$stats = CacheManager::stats();
// [
//   'driver'   => 'file',
//   'hits'     => 45,
//   'misses'   => 12,
//   'writes'   => 15,
//   'hit_rate' => '78.9%',
//   'total'    => 57,
// ]

// Hitung items
CacheManager::count(); // 42

// Ukuran total (file driver, dalam bytes)
CacheManager::size(); // 1048576

// Formatted summary
echo CacheManager::summary();
```

Output:

```
📊 Cache Summary (file driver)
────────────────────────────────────────
   Items:    42
   Size:     1.0MB
   Hits:     45
   Misses:   12
   Writes:   15
   Hit Rate: 78.9%
```

```php
// Reset statistik
CacheManager::resetStats();

// List semua cache keys (admin/debug)
$keys = CacheManager::keys();
// [
//   ['key' => 'user.1', 'expires_at' => '2026-02-26 12:00:00', 'size' => 256],
//   ['key' => 'settings', 'expires_at' => 'forever', 'size' => 1024],
// ]
```

---

## Fitur Beyond Laravel 🚀

### Rate Limiter

Rate limiting langsung via cache — tanpa perlu package tambahan.

```php
// Izinkan max 60 request per menit per IP
$allowed = CacheManager::rateLimit("api:{$ip}", 60, 60);

if (!$allowed) {
    $remaining = CacheManager::rateLimitRemaining("api:{$ip}", 60);
    return response("Rate limit exceeded. Remaining: {$remaining}", 429);
}
```

### Cascade Fallback

Coba beberapa cache keys berurutan — ambil yang pertama ketemu.

```php
// Coba cache user-specific, lalu role-specific, lalu global
$settings = CacheManager::cascade([
    "settings:user:{$userId}",
    "settings:role:{$roleId}",
    "settings:global",
], fn() => defaultSettings());
```

### SWR (Stale-While-Revalidate) 🚀

Return data stale langsung (cepat!) sambil refresh di belakang layar.

```php
// Data fresh selama 60s, tapi data stale masih bisa dipakai sampai 300s
$data = CacheManager::swr('dashboard_stats', 60, 300, function () {
    return calculateExpensiveStats(); // Hanya dijalankan saat refresh
});
```

Ini mirip strategi caching yang dipakai CDN (Cloudflare, Vercel) — user selalu mendapat respons cepat.

---

## Contoh Lengkap: Controller dengan Caching

```php
class ProductController
{
    public function index()
    {
        // Cache daftar produk selama 1 jam
        $products = CacheManager::remember('products.all', 3600, function () {
            return Product::with('category')->active()->get();
        });

        return view('products.index', compact('products'));
    }

    public function show($id)
    {
        // Cache per produk selama 30 menit
        $product = CacheManager::remember("product.{$id}", 1800, function () use ($id) {
            return Product::with(['reviews', 'category'])->findOrFail($id);
        });

        // Increment view counter
        CacheManager::increment("product.{$id}.views");

        return view('products.show', compact('product'));
    }

    public function update($id, Request $request)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        // Hapus cache terkait
        CacheManager::forget(["product.{$id}", 'products.all']);

        // Atau pakai tags
        CacheManager::tags(['products'])->flush();

        return redirect()->back()->with('success', 'Produk diperbarui!');
    }
}
```

---

## Database Driver Setup

Jika menggunakan driver `database`:

```php
// 1. Buat tabel (sekali saja)
CacheManager::createCacheTable();

// 2. Set driver
CacheManager::driver('database');

// 3. Gunakan seperti biasa
CacheManager::put('key', 'value', 3600);
```

Struktur tabel yang dibuat:

| Kolom        | Tipe            | Deskripsi                           |
| ------------ | --------------- | ----------------------------------- |
| `key`        | VARCHAR(255) PK | Cache key (dengan prefix)           |
| `value`      | LONGTEXT        | Serialized value                    |
| `expires_at` | INT UNSIGNED    | Unix timestamp expiry (0 = forever) |

---

📖 **Selanjutnya:** [Performance](performance.md) | [Database](database.md) | [Queue](queue.md)
