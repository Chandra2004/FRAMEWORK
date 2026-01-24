# 🛠️ Helper Functions

Framework ini dilengkapi dengan kumpulan fungsi bantuan statis melalui class `TheFramework\Helpers\Helper`. Fungsi-fungsi ini dirancang untuk tugas umum seperti manipulasi string, keamanan, dan navigasi.

## Cara Menggunakan

Pastikan Anda meng-import class Helper di atas file PHP Anda:

```php
use TheFramework\Helpers\Helper;
```

## Daftar Fungsi

### 🔒 Keamanan & Auth

`Helper::hash_password($password)`
Melakukan hashing password dengan algoritma Bcrypt yang aman.

```php
$hash = Helper::hash_password('rahasia123');
```

`Helper::verify_password($input, $hash)`
Memverifikasi apakah input cocok dengan hash (untuk login).

```php
if (Helper::verify_password($input, $user->password)) {
    // Login Sukses
    // Catatan: Jika $user adalah array, gunakan $user['password']
}
```

`Helper::sanitizeInput($input)`
Membersihkan input dari tag HTML berbahaya (XSS Protection). Bisa menerima string atau array.

### 🌐 Navigasi & URL

`Helper::url($path)`
Menghasilkan URL absolut berdasarkan `APP_URL`.

```php
echo Helper::url('/users'); // Hasil: http://localhost:8080/users
```

`Helper::redirect($path)`
Mengalihkan pengguna ke halaman lain.

```php
Helper::redirect('/login');
```

`Helper::is_post()` & `Helper::is_get()`
Mengecek metode request saat ini. Berguna di Controller.

### 💾 Format Data

`Helper::rupiah($angka)`
Memformat angka menjadi format mata uang Rupiah.

```php
echo Helper::rupiah(50000); // Hasil: Rp 50.000
```

`Helper::uuid()`
Membuat UUID versi 4 acak (identitas unik).

```php
$uid = Helper::uuid();
```

`Helper::updateAt()`
Menghasilkan timestamp "Sekarang" sesuai Timezone di `.env`.

```php
'created_at' => Helper::updateAt()
```

`Helper::slugify($string)`
Mengubah teks menjadi slug URL-friendly (`Hello World` -> `hello-world`).

### 📝 Session & Flash Data

`Helper::set_flash($key, $message)`
Menyimpan pesan sementara di sesi (hilang setelah direfresh). Cocok untuk notifikasi "Sukses".

`Helper::get_flash($key)`
Mengambil dan menghapus pesan flash dari sesi.
