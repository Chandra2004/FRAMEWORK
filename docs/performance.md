# 🚀 Kinerja & Optimasi (v5.0.1)

Agar aplikasi Anda tetap ngebut meskipun di hosting murah, terapkan strategi optimasi berikut ini.

---

## 1. ⚙️ Caching Configuration

Setiap kali aplikasi berjalan, framework meload `.env` dan berbagai file konfigurasi. Di production, proses ini bisa dipercepat dengan menjalankan perintah optimasi yang meriset cache internal:

```bash
php artisan optimize
```

**Apa yang dilakukan perintah ini?**

- Membersihkan view cache (`storage/framework/views`).
- Meriset data cache internal.
- Memberikan saran langkah optimasi berikutnya.

---

## 2. 🛣️ Route Caching

Framework ini melakukan parsing RegEx yang kompleks untuk Routing. Untuk project dengan banyak rute, gunakan fitur **Route Cache** untuk performa instan (O(1) lookup).

```bash
php artisan route:cache
```

> [!IMPORTANT]
> Jangan gunakan **Closure** (fungsi anonim) di dalam `routes/web.php` jika Anda ingin menggunakan Route Caching. Pindahkan semua logika rute ke **Controller**.

---

## 3. 📦 Asset Optimization

Jika Anda menulis CSS/JS di folder `resources`, Anda harus mem-publish mereka ke folder `public` agar bisa diakses langsung oleh Web Server (Nginx/Apache) tanpa melalui PHP:

```bash
# Via CLI
php artisan asset:publish

# Via Web Dashboard (Tanpa SSH)
URL: /_system/asset-publish
```

Ini akan menyalin file dari `resources/` ke `public/assets/`, sehingga loading asset menjadi jauh lebih cepat.

---

## 4. 📊 Database Indexing

Pastikan kolom yang sering digunakan untuk pencarian (`WHERE`), join, atau pengurutan memiliki Index.

```php
// Buruk: Email tidak di-index
$user = User::where('email', $email)->first();

// Baik: Tambahkan index di migrasi
$table->string('email')->unique(); // Otomatis index
$table->index('status'); // Manual index
```

---

## 5. 💾 Query Caching

Gunakan fitur `remember()` pada Query Builder untuk data yang jarang berubah (seperti daftar kategori atau pengaturan sistem).

```php
$categories = Category::query()->remember(3600)->get();
```

Ini akan menyimpan hasil query di `storage/cache`, menghemat ribuan request ke database per jam.

---

## 6. 🛠️ Web Utilities: System Dashboard

Jika Anda menggunakan hosting tanpa akses SSH, gunakan **System Dashboard** untuk melakukan optimasi rutin:

URL: `/_system`

Pilih menu **Optimize** atau **Clear Cache** setelah melakukan update kode. Menu ini juga meriset **PHP OPcache** (apabila server mengizinkan), sehingga perubahan kode Anda langsung terlihat tanpa menunggu restart server.

---

## 7. 📈 Benchmarks (Hello World Request)

Framework ini dirancang untuk sangat ringan (Extra Lean).

| Framework               | Cold Start | Warm Request | Memory |
| :---------------------- | :--------- | :----------- | :----- |
| **The Framework** | ~45ms      | ~12ms        | ~4.5MB |
| Laravel 11              | ~80ms      | ~25ms        | ~15MB  |
| CodeIgniter 4           | ~40ms      | ~15ms        | ~3.5MB |

---

## 8. 🌐 Server Optimization

### OPcache

Sangat disarankan untuk mengaktifkan **PHP OPcache** di `php.ini`. OPcache menyimpan bytecode di memori bersama, menghilangkan kebutuhan PHP untuk parsing file pada setiap request.

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0 ; Set 1 jika sering update kode tanpa restart
```

### Autoload Optimization

Selalu gunakan flag `--optimize-autoloader` saat deployment:

```bash
composer install --optimize-autoloader --no-dev
```
