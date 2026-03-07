# ⚡ Cache — Sistem Caching (v5.1.0)

Cache menyimpan data yang sering diakses ke penyimpanan yang lebih cepat, sehingga aplikasi tidak perlu menghitung ulang atau query database berulang kali. Framework ini menyediakan sistem caching premium yang terinspirasi dari Laravel namun dioptimalkan untuk performa tinggi.

---

## 📑 Daftar Isi

- [Fluent Global Helper](#fluent-global-helper-) — **NEW 🚀**
- [Konfigurasi via .env](#konfigurasi-via-env)
- [Pilihan Driver](#pilihan-driver)
- [Basic Operations](#basic-operations)
- [Advanced Pattern (SWR & Cascade)](#advanced-pattern-swr--cascade)
- [Atomic Locks & Tags](#atomic-locks--tags)
- [Web Management & Monitoring](#web-management--monitoring)

---

## 🚀 Fluent Global Helper (v5.1.0)

Anda sekarang dapat menggunakan helper `cache()` yang lebih ringkas daripada memanggil class static.

```php
// 1. Get Value
$data = cache('users');
$data = cache('users', 'default_value');

// 2. Put Value (Array syntax)
cache(['my_key' => 'my_value'], 3600);

// 3. Access CacheManager Instance
cache()->flush();
cache()->increment('visits');
```

---

## ⚙️ Konfigurasi via .env

Pengaturan cache dipusatkan di `.env` dan di-load via `config('app.php')`.

```env
# Pilihan: file, array, database
CACHE_DRIVER=file
CACHE_PREFIX=tf_prod_
CACHE_TTL=3600
```

Konfigurasi ini akan dibaca secara otomatis oleh framework tanpa perlu inisialisasi manual.

---

## 🏗️ Pilihan Driver

| Driver           | Kecepatan | Persisten | Rekomendasi Penggunaan                      |
| ---------------- | --------- | --------- | ------------------------------------------- |
| `file` (default) | ⚡⚡      | ✅ Ya     | Shared hosting / VPS standard.              |
| `array`          | ⚡⚡⚡    | ❌ Tidak  | Unit Testing saja (data tidak disimpan).    |
| `database`       | ⚡        | ✅ Ya     | Multi-server / Jika ingin cache lebih rapi. |

### Setup Database Driver

Jika menggunakan driver `database`, jalankan perintah berikut sekali saja untuk membuat tabel cache:

```bash
php artisan migrate # Atau jalankan di Web Command Center
```

_Catatan: Pastikan tabel `cache` sudah terdaftar di sistem migrasi Anda._

---

## 🔨 Basic Operations

### Simpan & Ambil

```php
use TheFramework\App\Cache\CacheManager;

// Cara Class (Statik)
CacheManager::put('api_data', $result, 600);
$data = CacheManager::get('api_data');

// Ambil lalu hapus (Flash-style cache)
$token = CacheManager::pull('temp_token');
```

### Cek & Hapus

```php
if (cache()->has('user.1')) {
    cache()->forget('user.1');
}

// Hapus SEGALANYA (Hard Reset)
cache()->flush();
```

---

## 💎 Advanced Patterns

### Remember Pattern (Terpopuler)

Seringkali Anda hanya ingin melakukan query ke database jika data tidak tersedia di dalam cache.

```php
$users = cache()->remember('users_active', 3600, function() {
    return User::where('active', 1)->get();
});
```

### SWR (Stale-While-Revalidate) 🚀

Cocok untuk dashboard yang butuh kecepatan kilat. User akan melihat data lama (stale) secara instan, sementara sistem me-refresh data baru di background.

```php
$stats = cache()->swr('dashboard_stats', 60, 300, function() {
    return calculateExpensiveStats();
});
```

### Cascade Fallback

Coba ambil key user, jika tidak ada key group, jika tidak ada key global.

```php
$color = cache()->cascade(['user.pref', 'group.pref', 'site.theme'], 'blue');
```

---

## 🔒 Atomic Locks & Tags

### Atomic Locks

Mencegah proses ganda jika dua user menekan tombol submit bersamaan.

```php
$lock = cache()->lock('process-payment', 10);

if ($lock->get()) {
    // Jalankan transaksi...
    $lock->release();
}
```

### Tags (Grouping)

Hapus semua cache untuk produk tanpa mengganggu cache user.

```php
cache()->tags(['products'])->put('item.1', $data);
cache()->tags(['products'])->flush(); // Hanya cache product yang hilang
```

---

## 🧹 Garbage Collection

File cache yang expired tidak menghilang otomatis dari disk untuk efisiensi IO. Pembersihan dapat dilakukan secara otomatis:

```php
// Jalankan ini di Middleware atau AppServiceProvider
// Akan menghapus file expired dengan probabilitas 1/100 request
cache()->gcProbabilistic(100);
```

---

## 📊 Monitoring (Paten Feature)

Framework menyediakan statistik real-time penggunaan cache aplikasi.

```php
$stats = cache()->stats();
// Menghasilkan hit rate, misses, dan kapasitas penyimpanan.

echo cache()->summary();
// Menampilkan tabel statistik premium di log atau terminal.
```

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
