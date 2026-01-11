# Routing

Routing adalah pintu gerbang aplikasi Anda. Semua definisi route terdapat di `routes/web.php`.

## Basic Routing

Gunakan `Router::add()` untuk mendefinisikan route.

```php
use TheFramework\App\Router;
use TheFramework\Http\Controllers\HomeController;

// Basic GET route
Router::add('GET', '/home', HomeController::class, 'index');

// Route dengan Parameter
Router::add('GET', '/user/{id}', HomeController::class, 'showUser');

// Route dengan multiple parameters
Router::add('GET', '/post/{postId}/comment/{commentId}', PostController::class, 'showComment');
```

## Route Groups

Grouping memudahkan Anda menerapkan prefix URL atau Middleware ke banyak route sekaligus.

```php
Router::group([
    'prefix' => '/admin',
    'middleware' => [AuthMiddleware::class]
], function () {

    // URL: /admin/dashboard
    Router::add('GET', '/dashboard', AdminController::class, 'dashboard');

    // URL: /admin/users
    Router::add('GET', '/users', AdminController::class, 'users');

});
```

## Middleware

Middleware dieksekusi sebelum controller. Cocok untuk autentikasi, proteksi CSRF, atau logging.

### Menggunakan Middleware

Anda bisa memasang middleware per-route atau per-grup.

```php
Router::add('POST', '/profile', UserController::class, 'update', [
    CsrfMiddleware::class,
    AuthMiddleware::class
]);
```

### Membuat Middleware

Buat class di `app/Middleware/` yang memiliki method `before()`.

```php
namespace TheFramework\Middleware;

class CheckAge
{
    public function before()
    {
        if ($_SESSION['age'] < 18) {
            header('Location: /restriction');
            exit;
        }
    }
}
```

## Resource Routing

Untuk mempercepat pembuatan CRUD, gunakan `Router::resource`.

```php
// Otomatis membuat route index, create, store, show, edit, update, destroy
Router::resource('/products', ProductController::class);
```

Default Mapping:

- GET `/products` -> index
- GET `/products/create` -> create
- POST `/products` -> store
- GET `/products/{id}` -> show
- ... dst

## Route Caching

Untuk performa production, framework mendukung route caching.
Pastikan untuk menjalankan perintah cache route (jika tersedia script build) saat deploy untuk menghindari parsing Route file setiap request.
