# 🚀 Kinerja & Optimasi

Agar aplikasi Anda tetap ngebut meskipun di hosting murah, terapkan tips berikut ini.

## 1. Caching Configuration

Setiap kali aplikasi berjalan, framework meload `.env` dan berbagai file konfigurasi. Di production, proses ini bisa dipercepat dengan menjalankan:

```bash
php artisan optimize
```

Perintah ini akan membersihkan cache lama.

## 2. Route Caching

Framework ini melakukan parsing RegEx yang kompleks untuk Routing. Untuk project dengan ratusan rute, sangat disarankan untuk menggunakan fitur cache rute (jika tersedia di versi artisan Anda) atau meminimalisir penggunaan Closure di `routes/web.php` dan menggantinya dengan Controller.

## 3. Database Indexing

Pastikan kolom yang sering digunakan untuk pencarian (`WHERE`) atau pengurutan (`ORDER BY`) memiliki Index database. Migrasi `id()` otomatis membuat Primary Key Index.

```php
// Contoh query lambat:
User::where('email', $email)->first(); // Lambat jika email tidak di-index
```

## 4. Web Utilities: Optimize

Jika Anda menggunakan hosting tanpa SSH, gunakan fitur **Web Optimizer** secara berkala (terutama setelah update kode):

URL: `/_system/optimize`

Ini akan:

- Menghapus view cache (file `.php` hasil kompilasi Blade).
- Mereset PHP OpCache (jika aktif di server), agar kode PHP terbaru langsung terbaca.

## 5. Session Driver

Secara default framework menggunakan `file` session driver. Ini aman dan mudah untuk hosting murah. Pastikan folder `storage/framework/sessions` (jika ada) atau folder `tmp` server bisa ditulis dengan cepat.

## 7. Benchmarks (Hellorld Request)

| Framework         | Cold Start | Warm Request | Memory |
| :---------------- | :--------- | :----------- | :----- |
| **The Framework** | ~50ms      | ~15ms        | ~5MB   |
| Laravel 11        | ~80ms      | ~25ms        | ~15MB  |
| CodeIgniter 4     | ~40ms      | ~12ms        | ~3MB   |

---

## 8. Server-Side Optimization

### OPcache

Sangat disarankan untuk mengaktifkan **PHP OPcache** pada server produksi. OPcache meningkatkan performa PHP dengan menyimpan bytecode skrip yang sudah dikompilasi di memori bersama, sehingga PHP tidak perlu memuat dan mem-parsing skrip pada setiap permintaan.

```ini
; Rekomendasi php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Autoload Optimization

Selalu gunakan flag `--optimize-autoloader` saat menginstal dependensi di server produksi:

```bash
composer install --optimize-autoloader --no-dev
```
