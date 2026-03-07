# 🛣️ Deep Dive: Routing System (v5.0.1)

Routing adalah infrastruktur vital yang menghubungkan request URL dari browser menuju logika bisnis di Controller. The Framework menyediakan sistem routing yang cepat, fleksibel, dan mendukung _Nested Grouping_ serta _Dependency Injection_.

---

## 📋 Daftar Isi

- [Dasar-Dasar Routing](#dasar-dasar-routing)
- [Shorthand & Special Routes](#shorthand--special-routes)
- [Resourceful Routing (CRUD)](#resourceful-routing-crud)
- [Parameter & Regex Parsing](#parameter--regex-parsing)
- [Named Routes](#named-routes)
- [Middleware & Grouping](#middleware--grouping)
- [Dependency Injection di Route](#dependency-injection-di-route)
- [Optimasi & Caching](#optimasi--caching)
- [Method Spoofing](#method-spoofing)
- [App Modes Override](#app-modes-override)

---

## Dasar-Dasar Routing

Semua rute didefinisikan di dalam file `routes/web.php`. Anda dapat menggunakan method `Router::add()` atau method shorthand untuk fleksibilitas maksimal.

### 1. Shorthand Methods (Recommended)

Cara termudah dan paling bersih untuk mendefinisikan rute:

```php
use TheFramework\App\Http\Router;

Router::get('/profile', UserController::class, 'show');
Router::post('/profile', UserController::class, 'update');
Router::put('/user/{id}', UserController::class, 'update');
Router::patch('/user/{id}', UserController::class, 'updatePartial');
Router::delete('/user/{id}', UserController::class, 'destroy');
Router::any('/webhook', WebhookController::class, 'handle');
```

---

## Shorthand & Special Routes

Selain verb HTTP standar, tersedia method khusus untuk menghemat penulisan kode:

### 1. View Route

Gunakan `Router::view()` jika rute tersebut hanya bertugas menampilkan halaman statis (Blade).

```php
// Tanpa Controller
Router::view('/welcome', 'interface.welcome', ['title' => 'Selamat Datang']);
```

### 2. Redirect Route

Berguna untuk mengarahkan rute lama ke rute baru secara instan.

```php
Router::redirect('/old-about', '/about-us', 301);
```

### 3. Fallback Route (404 Handler)

Mendefinisikan rute yang akan dieksekusi jika tidak ada satupun rute di atas yang cocok.

```php
Router::fallback(function() {
    return view('errors.404');
});
```

---

## Resourceful Routing (CRUD)

Untuk mengotomatisasi 7 rute standar CRUD (Create, Read, Update, Delete), gunakan `Router::resource`. Fitur ini sangat menghemat waktu saat membangun modul.

```php
Router::resource('/products', ProductController::class);
```

**Rute yang dihasilkan secara otomatis:**

| Verb | URI                     | Action  | Nama Method Controller |
| :--- | :---------------------- | :------ | :--------------------- |
| GET  | `/products`             | index   | `index()`              |
| GET  | `/products/create`      | create  | `create()`             |
| POST | `/products`             | store   | `store()`              |
| GET  | `/products/{id}`        | show    | `show($id)`            |
| GET  | `/products/{id}/edit`   | edit    | `edit($id)`            |
| POST | `/products/{id}`        | update  | `update($id)`          |
| POST | `/products/{id}/delete` | destroy | `destroy($id)`         |

---

## Parameter & Regex Parsing

Framework menggunakan RegEx engine yang efisien untuk menangkap variabel dari URL.

### Parameter Wajib

Gunakan kurung kurawal `{name}`. Parameter ini akan dikirimkan ke argumen method Controller Anda berdasarkan urutan.

```php
Router::get('/post/{slug}/{id}', function($slug, $id) {
    echo "Membaca post $slug dengan ID: $id";
});
```

> **Catatan Teknis:** Secara default, parameter akan menangkap karakter apapun kecuali `/`. Jika Anda butuh validasi format ID, rute tersebut dapat dikombinasikan dengan middleware validasi.

---

## Named Routes

Memberikan nama pada rute sangat krusial agar aplikasi Anda tetap _maintainable_. Jika suatu saat folder atau URL berubah, Anda tidak perlu mengubah link satu per satu di file Blade.

```php
Router::get('/user/dashboard/main', AdminController::class, 'index')->name('admin.home');
```

**Cara Menggunakannya di View/Code:**

```php
// Di Blade
<a href="{{ Helper::url('admin.home') }}">Dashboard</a>

// Di Controller
Helper::redirect('admin.home');
```

---

## Middleware & Grouping

Organisir rute Anda menggunakan `Router::group`. Anda bisa menerapkan _Prefix_ (URL awalan) dan _Middleware_ (Keamanan) sekaligus.

```php
Router::group(['prefix' => '/api/v1', 'middleware' => [AuthMiddleware::class]], function() {

    Router::get('/users', UserController::class, 'index'); // URL: /api/v1/users
    Router::get('/posts', PostController::class, 'index');  // URL: /api/v1/posts

});
```

The Framework mendukung **Nested Groups** (Grup di dalam grup), di mana prefix dan middleware akan digabungkan (merge) secara otomatis.

---

## Dependency Injection di Route

Router The Framework memiliki fitur **Auto-Wiring**. Anda dapat meminta objek apapun di argumen Closure atau Controller, dan Router akan menyediakannya secara otomatis dari Container.

```php
use TheFramework\App\Http\Request;
use TheFramework\Services\PaymentService;

Router::post('/checkout', function(Request $request, PaymentService $payment) {
    $amount = $request->input('amount');
    $payment->pay($amount);
});
```

Sistem akan cerdas memisahkan mana yang merupakan **Parameter URL** (seperti `{id}`) dan mana yang merupakan **Dependency Service**.

---

## Optimasi & Caching

Untuk aplikasi skala besar, pencarian rute via RegEx setiap kali request datang bisa memakan waktu. Gunakan perintah Artisan untuk mengunci rute ke dalam file cache yang cepat.

```bash
php artisan route:cache  # Mengunci rute
php artisan route:clear  # Menghapus cache rute
```

---

## Method Spoofing

Browser lama (HTML Form) hanya mengenal GET dan POST. Untuk menjalankan method PUT atau DELETE, Framework mendukung spoofing melalui field `_method`.

```html
<form action="/update/1" method="POST">
  <input type="hidden" name="_method" value="PUT" />
  <button type="submit">Update</button>
</form>
```

---

## App Modes Override

Variabel `APP_ENV` di file `.env` dapat mengubah perilaku Router secara global tanpa harus mengubah kode rute:

1.  **Maintenance Mode**: Jika `APP_ENV=maintenance`, semua rute akan diarahkan ke halaman pemeliharaan (kecuali rute internal `_system`).
2.  **Payment Mode**: Berguna jika aplikasi Anda berbasis langganan. Jika diset `payment`, user akan diarahkan ke halaman invoice/pembayaran.
3.  **Local Mode**: Di mode ini, Router akan otomatis melayani static assets dari folder `resources` jika file fisik di folder `public` belum tersedia (Sangat berguna saat development).

---

## 🔍 Debugging Rute

Gunakan perintah Artisan untuk melihat visualisasi peta rute aplikasi Anda:

```bash
php artisan route:list
```

Anda akan melihat tabel berisi **Method**, **URI**, **Name**, **Action**, dan **Middleware** yang terpasang secara transparan.

---

<div align="center">

**Routing — Pintu gerbang utama menuju efisiensi kode Anda.**

[Kembali ke Dokumentasi Utama](README.md)

</div>
