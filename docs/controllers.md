# 🎮 Controllers

Controller adalah pusat logika aplikasi. Tugasnya menerima HTTP request, berinteraksi dengan model/service, dan mengembalikan response (HTML atau JSON).

---

## 🏗️ Struktur Dasar

Controller dapat dibuat dengan cepat menggunakan Artisan:

```bash
php artisan make:controller UserController
```

File akan dibuat di: `app/Http/Controllers/UserController.php`.

```php
namespace TheFramework\Http\Controllers;

use TheFramework\Models\User;

class UserController extends Controller
{
    public function index()
    {
        // Mengambil data dari Model
        $users = User::all();

        // Mengembalikan view dengan data
        return view('users.index', compact('users'));
    }
}
```

---

## 💉 Dependency Injection

Framework ini mendukung injection service atau class apapun via Constructor atau langsung pada Method.

### 1. Constructor Injection

Cocok untuk service yang dipakai di banyak method dalam satu class.

```php
private UserService $userService;

public function __construct(UserService $userService)
{
    $this->userService = $userService;
}

public function index() {
    $data = $this->userService->getAll();
    return view('users.index', compact('data'));
}
```

### 2. Method Injection

Sangat praktis untuk class yang hanya dibutuhkan pada aksi tertentu.

```php
public function show($id, UserAnalytics $analytics)
{
    $analytics->trackVisit($id); // Auto-injected oleh Container
    return view('users.show', ['user' => User::find($id)]);
}
```

---

## ✅ Premium Validation (FormRequest)

Validasi manual yang panjang di dalam controller dapat digantikan dengan **FormRequest Injection** untuk kode yang lebih bersih.

```php
use TheFramework\Http\Requests\UserRequest;

// Validasi otomatis dijalankan SEBELUM method ini dipanggil
public function store(UserRequest $request)
{
    // Jika sampai sini, berarti input sudah valid
    User::create($request->all());

    return redirect('/users', 'success', 'User berhasil didaftarkan!');
}
```

> [!TIP]
> Jika validasi gagal, sistem akan otomatis melakukan redirect back dengan menyimpan pesan error ke session dan mengembalikan input lama (_old input_).

---

## 📥 Request Handling

Gunakan global helper `request()` untuk mengakses data input dari user.

```php
// Ambil satu input
$name = request('name');

// Ambil semua input (sebagai array)
$data = request()->all();

// Ambil hanya field tertentu
$subset = request()->only(['email', 'username']);

// Cek keberadaan input
if (request()->has('profile_picture')) { ... }
```

---

## 📤 Responses & Helpers

Framework menyediakan helper global yang _expressive_ dan _fluent_.

### 1. HTML View

```php
return view('path.to.view', ['key' => 'value']);
```

### 2. JSON Response (API)

Cocok untuk pembuatan REST API.

```php
return json([
    'status' => 'success',
    'data' => $users
], 200);
```

### 3. Redirect & Notifications

Sistem redirect ini mendukung pengiriman notifikasi secara instan dalam satu baris.

```php
// Redirect dasar
return redirect('/dashboard');

// Redirect + Notifikasi (Otomatis masuk ke flash message)
return redirect('/users', 'success', 'Data berhasil diperbarui!');

// Redirect Kembali (Back)
return redirect()->back('warning', 'Aksi dibatalkan oleh sistem');
// Atau gunakan helper singkat:
return back('error', 'Gagal memproses data');
```

---

## 📁 Internal vs Application Area

The Framework v5.0 menjaga integritas inti sistem dengan pemisahan folder:

| Folder                         | Tujuan                                 | Izin Ubah        |
| ------------------------------ | -------------------------------------- | ---------------- |
| `app/Http/Controllers`         | Area kerja Developer (Fitur Aplikasi)  | ✅ Bebas         |
| `app/App/Internal/Controllers` | Inti Framework (Debug, Sitemap, Error) | ❌ Jangan diubah |

Pelajari bagaimana framework menangani error atau sitemap dengan memeriksa file di folder `Internal`.

---

## 🚀 Resource Controller (CRUD)

Untuk efisiensi tinggi, controller CRUD lengkap dapat dibuat sekaligus:

```bash
php artisan make:controller ProductController --resource
```

Kemudian daftarkan di `routes/web.php`:

```php
Router::resource('/products', ProductController::class);
```

Sistem akan otomatis memetakan aksi `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`.

---

## Best Practices

1.  **Thin Controllers**: Jangan masukkan logika bisnis di controller. Pindahkan ke **Service** atau **Repository**.
2.  **Explicit Return**: Selalu gunakan `return view()` atau `return redirect()` agar alur eksekusi jelas terlihat.
3.  **Type Hinting**: Gunakan type hinting pada parameter method agar Container bisa melakukan _Auto-Wiring_ dengan tepat.

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
