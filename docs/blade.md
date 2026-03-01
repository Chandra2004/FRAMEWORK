# 🎨 Blade Engine (BladeInit)

Framework ini menggunakan **Illuminate Blade** dari Laravel sebagai template engine utamanya, ditambah dengan **custom directives** yang memudahkan development.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Arsitektur Internal](#arsitektur-internal)
3. [Custom Directives](#custom-directives)
4. [View Namespaces](#view-namespaces)
5. [Cache Management](#cache-management)
6. [Contoh Penggunaan](#contoh-penggunaan)

---

## Overview

`BladeInit` adalah class singleton yang bertanggung jawab menginisialisasi Blade template engine. File ini berada di `app/BladeInit.php` dan menggunakan package `illuminate/view` dari Laravel.

### Fitur Utama

| Fitur                    | Deskripsi                                                               |
| ------------------------ | ----------------------------------------------------------------------- |
| **Singleton Pattern**    | Instance Blade hanya dibuat sekali, digunakan berulang.                 |
| **Anti-Recursion Guard** | Mencegah infinite loop saat error terjadi saat inisialisasi.            |
| **Custom Directives**    | `@csrf`, `@auth`, `@guest`, `@error`, `@rupiah`, `@session`, `@config`. |
| **View Namespaces**      | Dukungan namespace `Internal::` untuk views framework internal.         |
| **Auto Cache Dir**       | Otomatis membuat direktori cache di `storage/framework/views`.          |
| **Data Sharing**         | Kemampuan `View::share()` untuk data yang bisa diakses seluruh halaman. |

---

## Arsitektur Internal

```
BladeInit::init()
    │
    ├── 1. Buat Filesystem instance
    ├── 2. Buat EngineResolver
    │       ├── Register 'blade' engine (BladeCompiler + Custom Directives)
    │       └── Register 'php' engine (PhpEngine)
    ├── 3. Buat FileViewFinder
    │       ├── Path utama: resources/views/
    │       └── Namespace 'Internal': app/App/Internal/Views/
    └── 4. Return Factory instance
```

### Dependency Tree

```
BladeInit
├── Illuminate\View\Factory
├── Illuminate\View\Engines\EngineResolver
│   ├── Illuminate\View\Engines\CompilerEngine
│   │   └── Illuminate\View\Compilers\BladeCompiler
│   └── Illuminate\View\Engines\PhpEngine
├── Illuminate\View\FileViewFinder
├── Illuminate\Filesystem\Filesystem
└── Illuminate\Events\Dispatcher
```

---

## Custom Directives

### `@csrf` — CSRF Token

Meng-generate hidden input field dengan token CSRF untuk melindungi form dari serangan Cross-Site Request Forgery.

```blade
<form method="POST" action="/profile">
    @csrf
    <input type="text" name="name">
    <button type="submit">Update</button>
</form>
```

**Output HTML:**

```html
<input type="hidden" name="_token" value="abc123..." />
```

---

### `@auth` / `@guest` — Conditional Authentication

Menampilkan konten berdasarkan status autentikasi user.

```blade
@auth
    <p>Selamat datang, {{ session('user')['name'] }}!</p>
    <a href="/logout">Logout</a>
@endauth

@guest
    <a href="/login">Login</a>
    <a href="/register">Daftar</a>
@endguest
```

**Cara Kerja:**

| Directive | Kondisi                                       |
| --------- | --------------------------------------------- |
| `@auth`   | `session('user') !== null` → User sudah login |
| `@guest`  | `session('user') === null` → User belum login |

---

### `@error('field')` — Validation Error

Menampilkan pesan error untuk field validasi tertentu.

```blade
<input type="email" name="email" value="{{ old('email') }}">
@error('email')
    <span class="text-danger">{{ $message }}</span>
@enderror
```

**Cara Kerja:** Memanggil global function `has_error($expression)` yang memeriksa `$_SESSION['errors']`.

---

### `@rupiah($amount)` — Format Mata Uang

Format angka menjadi format Rupiah Indonesia.

```blade
<p>Harga: @rupiah($product->price)</p>
{{-- Output: Rp 1.500.000 --}}

<p>Total: @rupiah($order->total)</p>
{{-- Output: Rp 25.000.000 --}}
```

**Cara Kerja:** Memanggil global function `rupiah()`.

---

### `@session('key')` — Session Check

Cek apakah session key tertentu ada, lalu tampilkan konten.

```blade
@session('success')
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endsession

@session('error')
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endsession
```

---

### `@config('key')` — Configuration Value

Menampilkan nilai konfigurasi langsung di template tanpa perlu memanggil helper `config()`.

```blade
<title>@config('app.name')</title>
<meta name="version" content="@config('app.version')">

<footer>
    &copy; {{ date('Y') }} @config('app.name')
</footer>
```

---

### 🛡️ Standard Blade Directives

Karena framework ini menggunakan `illuminate/view`, semua direktori standar Laravel berikut ini **tersedia dan bekerja 100%**:

- **Control Structures**: `@if`, `@else`, `@elseif`, `@unless`, `@switch`.
- **Loops**: `@foreach`, `@forelse`, `@for`, `@while`.
- **Layouts**: `@extends`, `@section`, `@yield`, `@stack`, `@push`.
- **Includes**: `@include`, `@each`.
- **PHP Blocks**: `@php ... @endphp`.

```blade
@if($users->isEmpty())
    <p>Data kosong.</p>
@else
    @foreach($users as $user)
        <li>{{ $user->name }}</li>
    @endforeach
@endif
```

---

## View Namespaces

### User Views (Default)

Path: `resources/views/`

```php
// Controller
return View::render('welcome');         // resources/views/welcome.blade.php
return View::render('blog.index');      // resources/views/blog/index.blade.php
return View::render('layouts.app');     // resources/views/layouts/app.blade.php
```

> **⚠️ Penting:** View namespace `Internal::` dicadangkan untuk framework. Developer sebaiknya menempatkan view mereka di `resources/views/`.

---

## 💎 Advanced Techniques

### Global Data Sharing

Gunakan `View::share()` untuk membagikan data ke **seluruh haman** secara otomatis (misalnya data menu, setting profile, atau notifikasi global). Biasanya dilakukan di `AppServiceProvider` atau Middleware.

```php
// Di logic Controller atau Provider
View::share('site_version', '5.0.1');
View::share('current_user', session('user'));

// Di file Blade manapun
<p>Version: {{ $site_version }}</p>
```

### Path & Naming Convention

Framework mendukung dua gaya penulisan path view:

1. **Dot Notation** (Rekomendasi): `auth.login` → `resources/views/auth/login.blade.php`
2. **Slash Notation**: `auth/login` → `resources/views/auth/login.blade.php`

### Dot Notation vs Variable

Ingat bahwa Blade sangat sensitif. Pastikan tidak ada spasi di antara nama view:

- ✅ `view('users.index')`
- ❌ `view('users . index')`

---

## 🌍 Localization dalam Blade

Gunakan helper `__()` untuk menampilkan teks multibahasa (sesuai `app.locale` di `.env`).

```blade
<h1>{{ __('messages.welcome') }}</h1>

{{-- Dengan Parameter --}}
<p>{{ __('messages.greet', ['name' => session('user')['name']]) }}</p>
```

---

## Cache Management

### Lokasi Cache

```
storage/framework/views/
```

Blade mengompilasi template `.blade.php` menjadi file PHP murni dan menyimpannya di direktori cache. Framework otomatis:

1. Membuat direktori cache jika belum ada
2. Meng-set permission `0777` jika tidak writable
3. Error handling jika gagal membuat cache directory

### Membersihkan Cache

```bash
# Via Artisan CLI
php artisan cache:clear

# Via Web Command Center
https://yoursite.com/_system/clear-cache
```

### Kapan Harus Clear Cache?

- ✅ Setelah mengubah file `.blade.php`
- ✅ Setelah deploy ke production
- ✅ Setelah mengubah custom directives
- ✅ Saat ada error "Undefined variable" yang aneh

---

## Contoh Penggunaan

### Layout Master

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <title>@yield('title', config('app.name'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @auth
        @include('partials.navbar')
    @endauth

    <main class="container">
        @session('success')
            <div class="alert alert-success">{{ session('success') }}</div>
        @endsession

        @session('error')
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endsession

        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} @config('app.name')</p>
    </footer>
</body>
</html>
```

### Halaman Form

```blade
{{-- resources/views/users/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat User Baru')

@section('content')
<h1>Buat User Baru</h1>

<form method="POST" action="/users/store">
    @csrf

    <div class="mb-3">
        <label>Nama</label>
        <input type="text" name="name" value="{{ old('name') }}">
        @error('name')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}">
        @error('email')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>

    <button type="submit">Simpan</button>
</form>
@endsection
```

### Rendering Harga (Paten)

```blade
{{-- resources/views/products/show.blade.php --}}
<div class="product-card">
    <h2>{{ $product->name }}</h2>
    <p class="price">@rupiah($product->price)</p>
    <p class="discount">Diskon: @rupiah($product->discount)</p>
    <p class="total"><strong>Total: @rupiah($product->price - $product->discount)</strong></p>
</div>
```

### Asset Management

Selalu gunakan helper `asset()` di Blade untuk cache busting otomatis (menambahkan versioning string berdasarkan file modification time).

```blade
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
{{-- Output: <link rel="stylesheet" href="/assets/css/app.css?v=1704067200"> --}}
```

---

## ⚙️ Method Reference

| Method                     | Return          | Deskripsi                             |
| -------------------------- | --------------- | ------------------------------------- |
| `BladeInit::init()`        | `Factory\|null` | Inisialisasi Blade engine (singleton) |
| `BladeInit::getInstance()` | `Factory\|null` | Alias untuk `init()`                  |

---

## 🔗 Related Documentation

- [Views & Templating](views.md) — Panduan view PHP native
- [Helpers](helpers.md) — Global functions (`csrf_token()`, `session()`, dll)
- [Security](security.md) — CSRF Protection
- [Validation](validation.md) — Error messages dan old input

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
