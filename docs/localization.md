# 🌍 Localization (Multi-Bahasa)

Fitur lokalisasi memudahkan Anda membuat aplikasi yang mendukung berbagai bahasa (misal: Indonesia & Inggris).

## Konfigurasi

Atur bahasa default di `.env`.

```env
APP_LOCALE=id
APP_LOCALE_FALLBACK=en
```

## File Bahasa

Simpan string terjemahan file di folder `resources/lang/{kode_bahasa}/messages.php`.

**Contoh: `resources/lang/id/messages.php`**

```php
return [
    'welcome' => 'Selamat Datang',
    'goodbye' => 'Sampai Jumpa',
    'login'   => [
        'title'   => 'Halaman Masuk',
        'button'  => 'Masuk Sekarang'
    ]
];
```

**Contoh: `resources/lang/en/messages.php`**

```php
return [
    'welcome' => 'Welcome',
    'goodbye' => 'Goodbye'
];
```

## Mengambil Terjemahan

Gunakan helper `__('key')`.

```php
echo __('messages.welcome');
// Output (jika APP_LOCALE=id): Selamat Datang
```

### Parameter Dinamis (Placeholders)

Anda bisa menyisipkan variabel ke dalam string terjemahan.

**File Lang:** `'greeting' => 'Halo, :name!'`

```php
echo __('messages.greeting', ['name' => 'Chandra']);
// Output: Halo, Chandra!
```

---

## 🔄 Pemindah Bahasa (Switcher)

Anda dapat mengubah bahasa secara dinamis menggunakan rute atau controller.

### 1. Buat Rute

```php
Router::add('GET', '/lang/{code}', function($code) {
    if (in_array($code, ['id', 'en'])) {
        Helper::session_write('app_locale', $code);
    }
    Helper::redirect('/');
});
```

### 2. Middleware (Opsional)

Untuk menerapkan bahasa dari session ke aplikasi, pastikan `AppServiceProvider` atau `Middleware` memanggil:

```php
$lang = Helper::session_get('app_locale', Config::get('APP_LOCALE'));
App::setLocale($lang);
```

---

## 👥 Pluralization (Jamak)

Urusi perbedaan kata benda tunggal dan jamak (Bahasa Inggris).

**File Lang:**

```php
'apples' => '{0} No apples|{1} One apple|[2,*] :count apples'
```

**Penggunaan:**

```php
echo trans_choice('messages.apples', 0); // No apples
echo trans_choice('messages.apples', 1); // One apple
echo trans_choice('messages.apples', 5); // 5 apples
```

_(Catatan: Fitur pluralization memerlukan library `illuminate/translation` yang sudah terintegrasi jika Anda menggunakan template Blade)._
