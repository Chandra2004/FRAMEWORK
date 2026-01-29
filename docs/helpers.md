# 🛠️ Helper Functions

Framework ini dilengkapi dengan kumpulan fungsi bantuan statis melalui class `TheFramework\Helpers\Helper`. Fungsi-fungsi ini dirancang untuk tugas umum seperti manipulasi string, keamanan, sesi, dan navigasi.

---

## 📋 Daftar Isi

1. [Cara Menggunakan](#cara-menggunakan)
2. [Sesi & Flash Data](#sesi--flash-data)
3. [Keamanan & Enkripsi](#keamanan--enkripsi)
4. [Validasi & Input](#validasi--input)
5. [URL & Navigasi](#url--navigasi)
6. [Format & Utilitas](#format--utilitas)
7. [Autentikasi & Role](#autentikasi--role)

---

## Cara Menggunakan

Panggil class Helper di mana saja (Controller, View, atau Service) dengan meng-import namespace-nya:

```php
use TheFramework\Helpers\Helper;

// Contoh penggunaan:
$url = Helper::url('/home');
```

Di dalam file **View**, Helper biasanya sudah tersedia otomatis atau bisa dipanggil langsung.

---

## 💾 Sesi & Flash Data

### `Helper::set_flash($key, $message)`

Menyimpan data sementara di session yang akan terhapus setelah diambil satu kali. Berguna untuk notifikasi sukses/gagal.

### `Helper::get_flash($key)`

Mengambil data flash dan langsung menghapusnya dari session. Jika data memiliki `expires_at`, fungsi ini akan mengecek masa berlakunya.

### `Helper::session_get($key, $default = null)`

Mengambil nilai dari session secara permanen (tidak terhapus).

### `Helper::session_write($key, $value, $overwrite = false)`

Menulis data ke session. Jika `$overwrite` false, data akan di-merge (jika berupa array).

### `Helper::session_destroy_all()`

Menghapus seluruh data session dan menghancurkan session id.

---

## 🔒 Keamanan & Enkripsi

### `Helper::generateCsrfToken()`

Membuat dan menyimpan token CSRF baru ke dalam session.

### `Helper::validateCsrfToken($token)`

Memverifikasi apakah token CSRF yang dikirim valid dan cocok dengan yang ada di session (menggunakan `hash_equals` untuk mencegah timing attacks).

### `Helper::hash_password($password)`

Melakukan hashing password menggunakan algoritma BCRYPT yang aman.

### `Helper::verify_password($password, $hashedPassword)`

Memverifikasi input password mentah terhadap hash dari database.

### `Helper::sanitizeInput($input)`

Membersihkan input dari tag HTML dan whitespace (`trim` + `strip_tags`). Mendukung sanitasi array secara rekursif.

---

## ✅ Validasi & Input

### `Helper::request($key = null, $default = null)`

Mengambil data dari `$_GET` dan `$_POST`. Mengembalikan anonymous class dengan helper `get()` dan `is()`.

### `Helper::validation_errors($field = null)`

Mengambil pesan error validasi yang disimpan di session. Jika `$field` diisi, hanya mengembalikan error untuk field tersebut.

### `Helper::has_error($field)`

Mengecek apakah terdapat error validasi untuk field tertentu.

### `Helper::old($field, $default = null)`

Mengambil input lama dari session (biasanya digunakan agar form tidak kosong saat validasi gagal).

---

## 🌐 URL & Navigasi

### `Helper::url($path = '')`

Menghasilkan URL absolut berdasarkan `BASE_URL`.

### `Helper::redirect($url, $status = null, $message = null, $duration = 10)`

Mengalihkan halaman. Jika `$status` dan `$message` diisi, otomatis membuat flash notification. Mendukung redirect via JSON jika request adalah AJAX.

### `Helper::is_ajax()`

Mengecek apakah request saat ini dikirim via AJAX (`X-Requested-With`).

### `Helper::is_post()` & `Helper::is_get()`

Mengecek metode HTTP request saat ini.

### `Helper::get_client_ip()`

Mendapatkan alamat IP user dengan dukungan proxy (Cloudflare/Load Balancer).

---

## 📝 Format & Utilitas

### `Helper::e($string)`

Alias untuk `htmlspecialchars`. Wajib digunakan saat menampilkan data user ke HTML (Anti XSS).

### `Helper::rupiah($angka)`

Memformat angka ke mata uang Rupiah (`Rp 50.000`).

### `Helper::random_string($length = 16)`

Menghasilkan string acak yang aman secara kriptografi.

### `Helper::uuid()`

Menghasilkan UUID v4 (36 karakter) yang unik.

### `Helper::slugify($text)`

Mengubah kalimat menjadi slug URL (`Judul Artikel Ini` -> `judul-artikel-ini`).

### `Helper::current_date($format = 'Y-m-d H:i:s')`

Mendapatkan waktu saat ini dengan default timezone Asia/Jakarta.

---

## 👤 Autentikasi & Role

### `Helper::hasRole($role)`

Mengecek apakah user yang sedang login memiliki role tertentu berdasarkan session `$_SESSION['user']['role_name']`.

### `Helper::authToken($data)`

Menghasilkan signature SHA256 untuk autentikasi yang digabung dengan `APP_KEY`.
