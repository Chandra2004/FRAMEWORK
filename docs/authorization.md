# Otorisasi & RBAC

The Framework menyediakan cara native yang mirip dengan Laravel untuk menangani Autentikasi (Auth) dan Role-Based Access Control (RBAC). Arsitektur Autentikasi dan Otorisasi yang baru ini berpusat pada `AuthManager`, `Gate`, dan trait `Authorizable`, memberikan Anda kendali penuh atas apa yang bisa dan tidak bisa dilakukan oleh pengguna.

## Pendahuluan

Selain autentikasi standar, Anda sering kali perlu mengotorisasi tindakan pengguna terhadap sumber daya tertentu. The Framework membuatnya sangat mudah melalui helper global `auth()`, facade `Gate`, dan trait langsung pada model.

### Helper `auth()`

Cara termudah untuk berinteraksi dengan autentikasi adalah menggunakan helper global `auth()`:

```php
// Mendapatkan user yang sedang login
$user = auth()->user();

// Mengecek apakah sudah login
if (auth()->check()) { ... }

// Mengecek apakah guest (belum login)
if (auth()->guest()) { ... }

// Mendapatkan ID user
$id = auth()->id();

// Logout
auth()->logout();
```

Sebagai alternatif, helper global `auth_user()` tersedia untuk langsung mengambil pengguna yang saat ini terautentikasi (`TheFramework\App\Auth\AuthManager::user()`).

---

## Role-Based Access Control (RBAC)

Jika model `User` Anda mengimplementasikan metode `hasRole`, Anda dapat dengan cepat memeriksa apakah seorang pengguna memiliki peran tertentu menggunakan helper global `is_role()`:

```php
// Cek apakah user saat ini adalah admin
if (is_role('admin')) {
    // Tampilkan dashboard admin
}

// Cek apakah user memiliki peran 'admin' atau 'editor'
if (is_role('admin', 'editor')) {
    // Tampilkan alat editor
}
```

---

## Mendefinisikan Kemampuan (Gates)

Gates adalah closure yang menentukan apakah seorang pengguna berhak melakukan tindakan tertentu. Anda dapat mendefinisikan gates di Service Providers Anda (misalnya, `AppServiceProvider` atau `AuthServiceProvider`).

```php
namespace App\Providers;

use TheFramework\App\Auth\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::define('update-post', function ($user, $post) {
            return $user->id === $post->user_id;
        });

        Gate::define('delete-post', function ($user, $post) {
            return is_role('admin') || $user->id === $post->user_id;
        });
    }
}
```

### Mengecek Kemampuan

Anda dapat mengecek kemampuan melalui kelas `Gate`, atau melalui helper global `can()` dan `cannot()`.

```php
if (can('update-post', $post)) {
    // User dapat memperbarui postingan...
}

if (cannot('delete-post', $post)) {
    // User tidak dapat menghapus postingan...
    abort(403, 'Unauthorized.');
}
```

### Trait Authorizable

Jika Anda menggunakan trait `Authorizable` pada model `User` Anda, Anda dapat mengecek izin secara langsung dan elegan (fluent) melalui instance model:

```php
if ($user->can('update-post', $post)) {
    // Diizinkan
}

if ($user->cannot('update-post', $post)) {
    // Ditolak
}
```
