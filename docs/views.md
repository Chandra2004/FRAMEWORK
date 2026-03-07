# 🎨 Views & Templating — v5.0.1 Premium

The Framework v5.0 menyediakan sistem rendering yang fleksibel dengan dukungan **Hybrid Engine**. Anda dapat menggunakan **Illuminate Blade** untuk fitur modern yang kaya, atau tetap menggunakan **Native PHP** untuk performa mentah tanpa kompilasi.

---

## 📋 Daftar Isi

1. [Prioritas Rendering](#prioritas-rendering)
2. [Membuat & Merender View](#membuat--merender-view)
3. [Global Data Sharing](#global-data-sharing-) — **PREMIUM**
4. [Blade vs Native PHP (Cheatsheet)](#blade-vs-native-php-cheatsheet)
5. [Sistem Layouts](#sistem-layouts)
6. [Partial Views & Components](#partial-views--components)
7. [API & Method Reference](#api--method-reference)

---

## Prioritas Rendering

Framework akan mencari file view di folder `resources/views/` dengan urutan prioritas sebagai berikut:

1. `{view_name}.blade.php` (Jika ditemukan, akan dikompilasi oleh Blade)
2. `{view_name}.php` (Jika poin 1 tidak ada, akan dieksekusi sebagai PHP murni)

---

## Membuat & Merender View

Gunakan global helper `view()` untuk merender tampilan dari Controller.

```php
namespace TheFramework\Http\Controllers;

class WelcomeController extends Controller {
    public function index() {
        // Otomatis mencari resources/views/welcome.blade.php atau welcome.php
        return view('welcome', [
            'title' => 'The Framework v5.0',
            'version' => '5.0.1'
        ]);
    }
}
```

### Penulisan Path

Anda dapat menggunakan format dot-notation (direkomendasikan) atau slash:

- `auth.login` → `resources/views/auth/login.blade.php`
- `admin/dashboard` → `resources/views/admin/dashboard.php`

---

## Global Data Sharing 🚀

Anda dapat membagikan data ke **seluruh view** secara global tanpa perlu mengirimnya satu-per-satu di setiap controller. Sangat berguna untuk data user login, pengaturan situs, atau menu navigasi.

```php
use TheFramework\App\Http\View;

// Di AppServiceProvider atau Middleware
View::share('app_name', 'My Premium App');
View::share('user', session('user'));
```

Sekarang variabel `$app_name` dan `$user` tersedia di semua file Blade dan PHP native.

---

## Blade vs Native PHP (Cheatsheet)

The Framework v5 merekomendasikan **Blade** untuk frontend yang kompleks, namun **Native PHP** tetap tersedia sebagai alternatif ultra-cepat.

| Fitur                | Blade Style (`.blade.php`) | Native Style (`.php`)                      |
| :------------------- | :------------------------- | :----------------------------------------- |
| **Echo (Escaped)**   | `{{ $var }}`               | `<?= e($var) ?>`                           |
| **Raw Echo**         | `{!! $var !!}`             | `<?= $var ?>`                              |
| **Kondisi**          | `@if($check) ... @endif`   | `<?php if($check): ?> ... <?php endif; ?>` |
| **Perulangan**       | `@foreach(...)`            | `<?php foreach(...): ?>`                   |
| **CSRF Field**       | `@csrf`                    | `<?= csrf_field() ?>`                      |
| **Rupiah Formatter** | `@rupiah($val)`            | `<?= rupiah($val) ?>`                      |

---

## Sistem Layouts

### 1. Gaya Blade (Inheritance)

Metode paling modern menggunakan `@extends` dan `@section`.

`layouts/app.blade.php`:

```blade
<html>
    <head><title>@yield('title')</title></head>
    <body>
        @yield('content')
    </body>
</html>
```

`home.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
    <h1>Selamat Datang!</h1>
@endsection
```

### 2. Gaya Native (Include)

Sangat cepat dan sederhana.

`home.php`:

```php
<?php View::partial('layouts.header', ['title' => 'Home']); ?>

    <h1>Halaman Utama</h1>

<?php View::partial('layouts.footer'); ?>
```

---

## Partial Views & Components

Gunakan `View::partial()` untuk memecah UI menjadi potongan-potongan kecil yang dapat digunakan kembali (Reusable Components).

```php
// Di dalam file view manapun
<div class="sidebar">
    <?php View::partial('components.sidebar-menu', ['active' => 'dashboard']); ?>
</div>
```

---

## API & Method Reference

### Class: `TheFramework\App\Http\View`

| Method                               | Deskripsi                                                      |
| :----------------------------------- | :------------------------------------------------------------- |
| `render(string $view, array $data)`  | Merender view dan langsung mengirim output ke browser.         |
| `partial(string $view, array $data)` | Alias untuk `render()`, digunakan untuk sub-view.              |
| `share(string\|array $key, $val)`    | Bagikan data secara global ke semua view.                      |
| `exists(string $view)`               | Memeriksa apakah file view ada di direktori `resources/views`. |

### Global Helpers

- `view($name, $data)`: Fungsi cepat untuk merender view.
- `view_path($path)`: Mendapatkan path absolut ke folder `resources/views`.
- `e($value)`: Melakukan encode HTML (XSS Protection).
- `asset($path)`: Mendapatkan URL asset dengan dukungan **Auto-Versioning (Paten)**.

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
