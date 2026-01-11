# Views & Blade Template

The Framework menggunakan mesin templating **Blade** (diadaptasi dari Laravel/Illuminate) untuk memisahkan logic aplikasi dari presentasi HTML.

## Lokasi View

Semua file view disimpan di `resources/views/`.

## Basic Usage

Di controller Anda, return view menggunakan helper global `view()` atau class `View`.

```php
use TheFramework\App\View;

public function index() {
    $data = ['name' => 'Chandra'];
    return View::render('home', $data);
}
```

File: `resources/views/home.blade.php`

## Blade Syntax

### Echoing Data

```html
<h1>Hello, {{ $name }}</h1>
<p>Current Time: {{ time() }}</p>
```

_Note: `{{ }}` otomatis melakukan escaping `htmlspecialchars` untuk mencegah XSS._

### Control Structures

```html
@if (count($users) > 0)
<ul>
  @foreach ($users as $user)
  <li>{{ $user['name'] }}</li>
  @endforeach
</ul>
@else
<p>No users found.</p>
@endif
```

### Layouts & Inheritance

Definisikan layout utama di `resources/views/layouts/app.blade.php`:

```html
<!-- layouts/app.blade.php -->
<html>
  <head>
    <title>My App - @yield('title')</title>
  </head>
  <body>
    <nav>...</nav>

    <div class="container">@yield('content')</div>
  </body>
</html>
```

Gunakan di page lain:

```html
<!-- dashboard.blade.php -->
@extends('layouts.app') @section('title', 'Dashboard') @section('content')
<h1>Welcome to Dashboard</h1>
@endsection
```

## Cache View

Blade meng-compile view menjadi kode PHP native untuk performa. File hasil kompilasi disimpan di `storage/cache/views/`. Pastikan folder ini writable.
