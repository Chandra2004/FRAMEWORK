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

URL: `/_system/optimize?key=APP_KEY`

Ini akan:

- Menghapus view cache (file `.php` hasil kompilasi Blade).
- Mereset PHP OpCache (jika aktif di server), agar kode PHP terbaru langsung terbaca.

## 5. Session Driver

Secara default framework menggunakan `file` session driver. Ini aman dan mudah untuk hosting murah. Pastikan folder `storage/framework/sessions` (jika ada) atau folder `tmp` server bisa ditulis dengan cepat.
