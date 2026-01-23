# Localization (Multi-Language Support)

The Framework menyediakan fitur **Internationalization (i18n)** yang memudahkan Anda membuat aplikasi dalam berbagai bahasa.

## 🌟 Fitur Utama

- **Auto Detection**: Mendeteksi bahasa dari Query Parameter (`?lang=id`) atau Session.
- **Key-Based Translation**: Menggunakan kunci (misal `messages.welcome`) untuk mengambil teks.
- **Placeholder Replacement**: Mendukung penggantian variabel dinamis (misal `:name`).
- **Fallback Locale**: Otomatis kembali ke bahasa default (Inggris) jika terjemahan tidak ditemukan.

## 📂 Struktur File

File bahasa disimpan di direktori `resources/lang`.

```bash
resources/
└── lang/
    ├── en/
    │   └── messages.php  (Bahasa Inggris)
    └── id/
        └── messages.php  (Bahasa Indonesia)
```

### Contoh File Bahasa (`resources/lang/en/messages.php`)

```php
<?php

return [
    'welcome' => 'Welcome to our application',
    'greeting' => 'Hello, :name!',
];
```

## 🛠️ Cara Penggunaan

### 1. Di View (Blade)

Gunakan helper `__()` atau `trans()` untuk menampilkan teks.

```html
<h1>{{ __('messages.welcome') }}</h1>

<!-- Dengan Parameter -->
<p>{{ __('messages.greeting', ['name' => 'Chandra']) }}</p>
```

### 2. Di Controller / PHP Code

```php
$message = __('messages.welcome');

// Atau menggunakan facade class
use TheFramework\App\Lang;

$message = Lang::get('messages.welcome');
```

## 🔄 Mengganti Bahasa (Language Switcher)

Framework otomatis menangani penggantian bahasa melalui middleware. Anda cukup membuat link dengan parameter `lang`.

```html
<a href="?lang=en">English 🇺🇸</a> <a href="?lang=id">Indonesia 🇮🇩</a>
```

Setelah diklik, preferensi bahasa akan disimpan di **Session** user, sehingga mereka tidak perlu memilih ulang di halaman berikutnya.

## ⚙️ Middleware

System ini didukung oleh `TheFramework\Middleware\LanguageMiddleware`. Middleware ini:

1.  Mengecek input `$_GET['lang']`.
2.  Menyimpan pilihan ke `$_SESSION['app_locale']`.
3.  Mengatur locale global aplikasi via `Lang::setLocale()`.

Pastikan middleware ini terdaftar di `routes/web.php` (biasanya sudah default).
