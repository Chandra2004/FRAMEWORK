# Arsitektur Core

## Service Container

TheFramework menggunakan **Dependency Injection Container** (`TheFramework\App\Container`) yang kuat untuk mengelola ketergantungan kelas secara otomatis.

```php
// Container menyelesaikan dependensi secara rekursif
$controller = Container::getInstance()->make(UserController::class);
```

## Request Lifecycle

1.  **Entry Point**: `public/index.php` menangkap request.
2.  **Bootstrap**: `bootstrap/app.php` memuat environment, error handler, dan session.
3.  **Routing**: `Router` mencocokkan URL dengan definisi route di `routes/web.php`.
4.  **Middleware**: Filter keamanan (CSRF, WAF) dijalankan.
5.  **Controller**: Logika aplikasi dieksekusi.
6.  **Response**: Output dikirim ke browser.

## Autoloading

Mengikuti standar **PSR-4**, semua claass dimuat otomatis oleh Composer. Tidak perlu `require` manual.
