# 🛣️ Routing & Controller Guide

Panduan lengkap mengatur URL dan logika aplikasi.

---

## 1. Routing (`routes/web.php`)

Semua rute aplikasi didefinisikan di file `routes/web.php`. Framework ini menggunakan custom High-Performance Router.

### Dasar Routing

Format: `Router::add(METHOD, PATH, CONTROLLER, FUNCTION, [MIDDLEWARE])`

```php
use TheFramework\App\Router;
use TheFramework\Http\Controllers\HomeController;

// GET Request
Router::add('GET', '/', HomeController::class, 'index');

// POST Request
Router::add('POST', '/login', AuthController::class, 'login');

// Route dengan Parameter Dinamis
Router::add('GET', '/user/{id}', UserController::class, 'show');
// URL: /user/5 -> Masuk ke method show($id) dengan $id = 5
```

### Route Groups (Prefix & Middleware)

Mengelompokkan route yang memiliki prefix URL atau middleware yang sama.

```php
use TheFramework\Middleware\AuthMiddleware;
use TheFramework\Middleware\AdminMiddleware;

Router::group([
    'prefix' => '/admin',
    'middleware' => [AuthMiddleware::class, AdminMiddleware::class]
], function() {

    // URL: /admin/dashboard
    Router::add('GET', '/dashboard', AdminController::class, 'dashboard');

    // URL: /admin/users
    Router::add('GET', '/users', AdminController::class, 'users');

    // URL: /admin/users/delete/5
    Router::add('POST', '/users/delete/{id}', AdminController::class, 'deleteUser');

});
```

---

## 2. Controllers (`app/Http/Controllers`)

Controller adalah tempat logika aplikasi Anda berada.

### Membuat Controller

Gunakan perintah Artisan:

```bash
php artisan make:controller ProductController
```

### Struktur Controller

Controller dasar biasanya terlihat seperti ini:

```php
namespace TheFramework\Http\Controllers;

use TheFramework\App\View;
use TheFramework\Helpers\Helper;

class ProductController extends Controller
{
    // Menerima parameter dari URL otomatis
    public function show($id)
    {
        // Logika bisnis...
        return View::render('products.detail', ['id' => $id]);
    }
}
```

---

## 3. Request Handling

Anda bisa mengambil data input user menggunakan Helper Global `request()` atau class `TheFramework\Http\Requests\Request`.

### Mengambil Input (GET/POST)

```php
public function store()
{
    // Ambil single value (dengan default value jika kosong)
    $name = request('name', 'Anonymous');

    // Ambil input file
    $file = $_FILES['photo'] ?? null;

    // Cek method
    if (Helper::is_post()) {
        // Proses simpan...
    }
}
```

### Form Request Validation

Untuk validasi yang lebih rapi, gunakan Form Request Class.

1. Buat Request:
   `php artisan make:request StoreProductRequest`

2. Definisikan Rules:

   ```php
   public function rules(): array
   {
       return [
           'name' => 'required|min:3',
           'price' => 'required|numeric',
           'photo' => 'nullable|file|mimes:jpg,png|max:2048'
       ];
   }
   ```

3. Gunakan di Controller:

   ```php
   use TheFramework\Http\Requests\StoreProductRequest;

   public function store()
   {
       $request = new StoreProductRequest();

       // Validasi otomatis dijalankan di constructor/validated()
       $data = $request->validated();

       // Jika lolos, $data berisi input yang bersih
       Product::create($data);
   }
   ```

---

## 4. Responses

Controller dapat mengembalikan berbagai jenis respons.

### Return View (Halaman Web)

Merender file Blade template dari `resources/views/`.

```php
// Render 'resources/views/profile.blade.php'
return View::render('profile', [
    'user' => $userData,
    'title' => 'Profile Page'
]);
```

### Return JSON (API)

Mengembalikan data JSON (otomatis set header `Content-Type: application/json` dan exit).

```php
return Helper::json([
    'status' => 'success',
    'data' => $products
], 200);
```

### Redirect

Mengalihkan user ke halaman lain.

```php
// Redirect biasa
Helper::redirect('/login');

// Redirect dengan Flash Message (Session)
Helper::redirect('/dashboard', 'success', 'Selamat datang kembali!');
```

_Di View, flash message bisa diambil dengan `get_flash('success')`._

### Error Pages

Menampilkan halaman error standar (404/500).

```php
// Redirect ke 404 Not Found
return Helper::redirectToNotFound();
```
