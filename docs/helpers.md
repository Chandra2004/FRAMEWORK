# 🛠️ Helpers & Utilities (v5.0 - Paten)

Helpers adalah sekumpulan fungsi global dan class utilitas yang dirancang untuk mempercepat proses pengembangan Anda. The Framework v5.0 menyertakan sistem Helper yang bahkan lebih kaya fitur daripada framework populer lainnya.

---

## 🏗️ Magic Global Functions

Anda dapat memanggil fungsi-fungsi ini di mana saja (Controller, View, atau Middleware).

### URL & Asset
- `url($path)`: Membuat URL lengkap aplikasi.
- `asset($path)`: Membuat URL untuk file di folder `public/assets`.
- `current_url()`: Mendapatkan URL saat ini.

### Request & Response
- `request($key, $default)`: Mengambil data input. Jika dipanggil tanpa argumen, mengembalikan **Request Engine**.
- `view($name, $data)`: Render view (Blade-like).
- `redirect($url, $status, $message)`: Redirect ke halaman lain. Bisa dipanggil tanpa argumen: `redirect()->back()` atau `redirect()->to()`.
- `json($data, $status)`: Mengembalikan response JSON dengan status code tertentu.
- `response()`: Mengakses fluent response builder (mis. `response()->json()`).
- `back()`: Shortcut untuk kembali ke halaman sebelumnya.
- `abort($code, $message)`: Menghentikan eksekusi dengan HTTP status code (memanggil ErrorController internal).
- `flash($key)`: Mengambil flash data (notifikasi) sekaligus menghapusnya dari session.
- `session($key, $default)`: Mengambil atau menyimpan data session. Jika array, maka menyimpan: `session(['key' => 'val'])`.

### Security
- `csrf_token()`: Mendapatkan token CSRF aktif.
- `csrf_field()`: Membuat input hidden berisi token CSRF untuk form.
- `method_field($verb)`: Membuat input hidden untuk Method Spoofing (PUT/DELETE).
- `e($value)`: Melakukan sanitasi HTML (Escape).

### Formatting & Others
- `rupiah($number)`: Format angka ke mata uang Rupiah.
- `collect($array)`: Mengubah array menjadi **Collection Object** yang fluida.
- `now()`: Mendapatkan objek DateTime saat ini (Asia/Jakarta).
- `dd(...$vars)`: "Dump and Die" premium dengan interface Bug Hunter yang cantik.
- `config($key)`: Mengambil nilai dari konfigurasi `.env`.

---

## 🧵 Logic Classes (Advanced)

Untuk operasi yang lebih kompleks, gunakan class-class helper berikut:

### 🔠 `Str` (String Manipulation)
```php
use TheFramework\Helpers\Str;

Str::slug('Judul Berita Baru'); // judul-berita-baru
Str::random(32);              // String acak aman
Str::limit($text, 50);       // Potong text dengan ...
Str::mask($email, '*', 3);   // cha***@gmail.com (Premium!)
```

### 🔢 `Arr` (Array Management)
Mendukung sintaks **dot-notation** untuk memanipulasi array dalam.
```php
use TheFramework\Helpers\Arr;

$data = ['user' => ['profile' => ['name' => 'Chandra']]];
Arr::get($data, 'user.profile.name'); // Chandra
Arr::set($data, 'settings.theme', 'dark'); // Auto-create array kedalam.
```

### 📦 `Collection` (Fluid Arrays)
Mirip dengan Laravel Collections, memungkinkan chain-method.
```php
$data = collect([1, 2, 3, 4, 5]);
$result = $data->filter(fn($n) => $n > 2)
               ->map(fn($n) => $n * 10)
               ->all(); 
// Hasil: [30, 40, 50]
```

---

## 🗄️ `DatabaseHelper` (Audit Mode)

Anda bisa melakukan audit koneksi database untuk melihat latensi:

```php
use TheFramework\Helpers\DatabaseHelper;

$info = DatabaseHelper::auditConnection();
// Return: [connected, latency_ms, version, error, timestamp]
```

---

## ⚡ TFWire & Turbo (v2.0 UI Update)

Fungsi-fungsi khusus untuk membangun UI reaktif tanpa JavaScript.

- `tfwire($class, $id, $params)`: Render komponen TFWire ke Blade. Mendukung *Auto-Resolve Namespace* dan *Nested Components*.
- `turbo_stream()`: Mengakses fluent TurboStream builder untuk update DOM spesifik dari controller.
- `is_turbo_request()`: Cek apakah request datang dari Hotwire Turbo.
- `is_tfwire_request()`: Cek apakah request adalah internal TFWire action call.

---

## 📝 Tips Developer
Anda dapat menambahkan fungsi helper buatan sendiri di file `app/Helpers/custom_helpers.php` (silakan buat file ini jika diperlukan) dan masukkannya ke dalam `composer.json` bagian `autoload.files`.
