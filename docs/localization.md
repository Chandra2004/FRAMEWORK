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

File Lang: `'greeting' => 'Halo, :name!'`

```php
echo __('messages.greeting', ['name' => 'Chandra']);
// Output: Halo, Chandra!
```
