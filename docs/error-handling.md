# 🚨 Penanganan Error & Exception

The Framework menyediakan mekanisme terpusat untuk menangani error dan exception agar aplikasi Anda tetap aman dan memberikan feedback yang berguna bagi developer maupun user.

---

## 🛠️ Konsep Dasar

Framework menggunakan `try-catch` block global di `bootstrap/app.php` untuk menangkap semua `Throwable`.

### Mode Debug (`APP_DEBUG`)

- **`true`**: Menampilkan stack trace lengkap, pesan error detail, dan baris kode yang menyebabkan error. Sangat berguna untuk development.
- **`false`**: Menampilkan halaman error 500 yang ramah user (tanpa membocorkan detail teknis yang bisa dieksploitasi hacker).

---

## 🚦 Pola Penanganan Error

Ada dua pola utama yang disarankan dalam framework ini:

### 1. Menggunakan `abort()` (Rekomendasi)

Gunakan helper ini jika Anda ingin menghentikan eksekusi dan langsung menampilkan halaman error HTTP (seperti 403 atau 404).

```php
if (!$user) {
    // Menampilkan halaman error 404
    Router::redirectToNotFound();
}

if (!$user->isAdmin()) {
    // Menampilkan halaman error 403
    Router::handleAbort("Anda tidak memiliki akses ke area ini.");
}
```

### 2. Menggunakan Exception

Gunakan Exception untuk error logika yang harus ditangkap oleh layer di atasnya atau global handler.

```php
if ($amount < 0) {
    throw new Exception("Jumlah saldo tidak boleh negatif");
}
```

---

## 🎨 Kustomisasi Tampilan Error

Tampilan error tersimpan di folder `resources/views/errors/`:

- `403.blade.php`: Tampilan akses ditolak.
- `404.blade.php`: Tampilan halaman tidak ditemukan.
- `500.blade.php`: Tampilan error server internal.

Anda dapat mengubah desain file-file tersebut sesuai dengan tema aplikasi Anda.

---

## 📝 Logging Error

Setiap error yang terjadi akan otomatis dicatat ke dalam file log:
`storage/logs/app.log`

Pastikan folder ini memiliki izin tulis (_write permission_) pada server produksi Anda.
