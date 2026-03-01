# 🔀 Middleware (v5.0.1 - Paten)

Middleware adalah layer yang memproses HTTP request sebelum mencapai controller (atau setelah response dikembalikan). Pada v5.0.1, middleware telah dimodernisasi menggunakan **Fluent Helpers**.

---

## 🏗️ How it Works

```
Request → Middleware 1 → Middleware 2 → Controller → Response
          (before)       (before)        (logic)
```

Setiap middleware harus mengimplementasikan `TheFramework\Middleware\Middleware` interface yang memiliki dua method: `before()` dan `after()`.

---

## 🛡️ Built-in Middleware (Premium Security)

Framework ini menyertakan sistem pertahanan "Tri-Layer" secara default:

| Middleware           | Purpose                                      | Status    |
| -------------------- | -------------------------------------------- | --------- |
| `WAFMiddleware`      | Menangkal SQLi, XSS, RCE, & Path Traversal   | ✅ Global |
| `CsrfMiddleware`     | Validasi Token CSRF (POST, PUT, DELETE, etc) | ✅ Global |
| `AuthMiddleware`     | Proteksi auth session & token validation     | ❌ Manual |
| `ApiAuthMiddleware`  | Dual-path Auth (CSRF Web & Bearer Token API) | ❌ Manual |
| `LanguageMiddleware` | Penanganan multi-bahasa otomatis             | ✅ Global |

---

## 📝 Creating Middleware

### Generate Middleware

```bash
php artisan make:middleware LogRequestMiddleware
```

### Modern Structure (Fluent DX)

Gunakan global helpers agar kode middleware Anda bersih dan "Paten":

```php
<?php

namespace TheFramework\Middleware;

class LogRequestMiddleware implements Middleware
{
    public function before()
    {
        // 1. Ambil data via request() helper
        $path = request()->path();
        $method = request()->method();
        $ip = request()->ip();

        // 2. Gunakan logic Anda
        error_log("[$ip] $method: $path");

        // 3. (Opsional) Hentikan request jika perlu
        if ($path === '/terlarang') {
            return abort(403, 'Akses ke area ini dilarang!');
        }
    }

    public function after() { /* Optional */ }
}
```

---

## 🚀 Applying Middleware

### Route-Level Middleware

```php
use TheFramework\Middleware\AuthMiddleware;

// Single middleware
Router::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware([AuthMiddleware::class]);

// Multiple middleware
Router::get('/admin/users', [AdminController::class, 'index'])
    ->middleware([AuthMiddleware::class, AdminMiddleware::class]);
```

### Group Middleware

```php
Router::group(['middleware' => [AuthMiddleware::class]], function() {
    Router::get('/profile', [ProfileController::class, 'index']);
    Router::post('/profile', [ProfileController::class, 'update']);
});
```

---

## 🧩 Premium Built-in Examples

### 1. `AuthMiddleware` (Modern Version)

Mengecek login sekaligus memvalidasi integritas token autentikasi.

```php
public function before()
{
    if (!session('user.uid') || !session('auth_token')) {
        return redirect('/login', 'error', 'Sesi Berakhir.');
    }

    // Validasi token tambahan via Helper
    if (!Helper::validateAuthToken(session('auth_token'), session('user.uid'))) {
        return redirect('/login', 'error', 'Token tidak valid.');
    }
}
```

### 2. `ApiAuthMiddleware` (Dual Path)

Mendukung validasi internal (CSRF) dan eksternal (Bearer Token).

```php
public function before()
{
    // Cek CSRF untuk Web Request
    if (request()->header('X-CSRF-TOKEN')) {
        // ... logic csrf
        return;
    }

    // Cek Bearer Token untuk API Request
    $token = request()->bearerToken();
    if (!$token || !isValid($token)) {
        return json(['error' => 'Unauthorized'], 401);
    }
}
```

### 3. `WAFMiddleware` (Security Shield)

Secara otomatis memblokir request mencurigakan. Anda dapat mengaturnya di `.env`:

- `APP_ENV=production` -> Error akan di-hidden (Security silent).
- `APP_ENV=local` -> Detail blokir akan ditampilkan (Debug mode).

---

## 💡 Terminating Request

Pada v5.0.1, disarankan menghentikan request menggunakan helper:

- `return abort(403, $msg)`: Menampilkan halaman error 403 cantik.
- `return redirect($url, $status, $msg)`: Mengalihkan ke halaman lain.
- `return json($data, $status)`: Memberikan response JSON (untuk API).

---

## 📝 Tips & Best Practices

1.  **Fail Fast**: Taruh pengecekan keamanan paling atas di method `before()`.
2.  **LIFO Execution**: Method `after()` dijalankan secara terbalik (LIFO - Last In, First Out).
3.  **No Heavy Logic**: Jangan lakukan query database yang berat di middleware kecuali sangat diperlukan (seperti cek token).
4.  **Use Helpers**: Gunakan `session()`, `request()`, `config()`, dan `flash()` agar kode konsisten dengan framework.

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
