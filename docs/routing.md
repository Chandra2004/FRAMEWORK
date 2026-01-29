# 🛣️ Routing

Routing adalah peta jalan aplikasi Anda. Ia mengatur URL mana yang akan memicu Controller mana.

---

## 📋 Daftar Isi

1.  [Dasar Routing](#dasar-routing)
2.  [Parameter Rute](#parameter-rute)
3.  [Named Routes](#named-routes)
4.  [Grouping & Prefix](#grouping--prefix)
5.  [Middleware pada Route](#middleware-pada-route)
6.  [Method Spoofing (Form PUT/DELETE)](#method-spoofing)

---

## Dasar Routing

Definisikan rute di `routes/web.php`.

### Basic Verbs

The Framework mendukung semua verb HTTP standar.

```php
use TheFramework\App\Router;

Router::add('GET', '/home', HomeController::class, 'index');
Router::add('POST', '/post/store', PostController::class, 'store');
Router::add('PUT', '/post/update', PostController::class, 'update');
Router::add('DELETE', '/post/delete', PostController::class, 'destroy');
```

### Menggunakan Closure (Fungsi Langsung)

Untuk halaman statis sederhana, Anda tidak wajib membuat controller.

```php
Router::add('GET', '/about', function() {
    return View::render('about');
});
```

---

## Parameter Rute

Anda sering perlu menangkap ID dari URL (misal `/user/5`).

### Parameter Wajib `{param}`

```php
// URL: /user/5
Router::add('GET', '/user/{id}', UserController::class, 'show');
```

Di Controller, parameter ini otomatis di-mappping ke argumen method:

```php
public function show($id) {
    echo "User ID: " . $id;
}
```

### Parameter Ganda

```php
// URL: /post/10/comments/50
Router::add('GET', '/post/{postId}/comments/{commentId}', function($postId, $commentId) {
    // ...
});
```

> **Info Teknis:** `{id}` diparsing menjadi RegEx `(?P<id>[^/]+)` yang berarti "string apapun kecuali garis miring".

---

## Grouping & Prefix

Mengelompokkan rute yang mirip membuat kode lebih rapi.

```php
Router::group(['prefix' => '/admin', 'middleware' => ['AuthMiddleware']], function() {

    // URL: /admin/dashboard
    Router::add('GET', '/dashboard', AdminController::class, 'dashboard');

    // URL: /admin/users
    Router::add('GET', '/users', AdminController::class, 'users');

});
```

---

## Middleware pada Route

Anda bisa memasang middleware (filter) pada rute spesifik.

```php
Router::add('GET', '/profile', UserController::class, 'profile', [AuthMiddleware::class]);
```

Rute ini hanya bisa diakses jika `AuthMiddleware` meloloskan request.

---

## Method Spoofing

Browser HTML Form hanya mendukung `GET` dan `POST`. Untuk melakukan `PUT` atau `DELETE`, gunakan teknik _Method Spoofing_.

Dalam form `resources/views/edit.php`:

```html
<form action="/users/update/1" method="POST">
  <!-- Tambahkan input hidden ini -->
  <input type="hidden" name="_method" value="PUT" />

  <button type="submit">Update</button>
</form>
```

Framework akan membaca `_method` dan memperlakukannya sebagai request `PUT`.
