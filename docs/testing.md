# UNIT & FEATURE TESTING

TheFramework menggunakan **PHPUnit** untuk automated testing.
Testing suite ini memastikan bahwa core framework dan aplikasi Anda berjalan sesuai ekspektasi.

## Persiapan

Pastikan PHPUnit terinstall (otomatis via `composer install` jika di mode dev, tapi perlu extension zip enabled di PHP agar cepat).

## Struktur Folder

- `tests/Unit`: Untuk tes logika class individual (isolated).
- `tests/Feature`: Untuk tes integrasi request HTTP (endpoint).

## Menjalankan Test

Cukup jalankan perintah berikut di terminal:

```bash
composer test
```

Atau manual:

```bash
vendor/bin/phpunit
```

## Menulis Test Baru

Buat file baru di folder `tests/Unit` atau `tests/Feature` dengan akhiran `Test.php`.

**Contoh Feature Test (tes halaman):**

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_profile_page_accessible()
    {
        $response = $this->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('My Profile');
    }
}
```

**Helper HTTP Tersedia:**

- `$this->get($uri)`
- `$this->post($uri, $data)`

Environment testing diatur di file `phpunit.xml`. Secara default menggunakan database SQLite in-memory agar testing sangat cepat tanpa menyentuh database asli.
