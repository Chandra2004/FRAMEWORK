# ROUTING SYSTEM

Routing di `The-Framework` didesain untuk menjadi **ekspresif**, **sederhana**, dan **super cepat**.
Lokasi definisi route ada di File: `routes/web.php`.

## Dasar Routing

```php
use TheFramework\App\Router;
use TheFramework\Http\Controllers\HomeController;

// GET Request
Router::add('GET', '/about', HomeController::class, 'about');

// POST Request
Router::add('POST', '/login', AuthController::class, 'login');

// Parameter Dinamis
Router::add('GET', '/user/{id}', UserController::class, 'profile');
```

## Advanced Routing

### 1. Route Group & Middleware

Anda bisa mengelompokkan route yang memiliki prefix atau middleware yang sama.

```php
use TheFramework\Middleware\AuthMiddleware;

Router::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function() {

    // URL: /admin/dashboard
    Router::add('GET', '/dashboard', AdminController::class, 'index');

    // URL: /admin/users
    Router::add('GET', '/users', AdminController::class, 'users');

});
```

### 2. Resource (CRUD) Routing

Membuat route standar RESTful CRUD dalam satu baris.

```php
// Otomatis membuat route: index, create, store, show, edit, update, destroy
Router::resource('/products', ProductController::class);
```

### 3. Serving Assets (Development Mode)

Untuk kenyamanan development, framework bisa melayani file statis via PHP jika file public belum ada.

```php
// URL: /assets/css/style.css -> resources/css/style.css
// Note: Jangan digunakan di production demi performa.
```

---

## ⚡ Route Caching (Performance)

Secara default, Router akan memproses regex satu per satu (Iterative). Ini cepat untuk <50 routes, tapi lambat untuk aplikasi besar.

Untuk **Production**, aktifkan fitur cache:

```bash
php artisan route:cache
```

Framework akan mengubah `web.php` menjadi array statis `storage/cache/routes.php` yang diload secara instan.

**PENTING:**
Jika Anda mengubah file `web.php` setelah menjalankan `route:cache`, perubahan TIDAK akan terlihat sampai Anda menjalankan:

```bash
php artisan route:clear
# atau jalankan route:cache lagi
```
