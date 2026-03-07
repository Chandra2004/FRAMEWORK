# 🧪 Testing

Framework ini dibuat dengan pola pikir "Testable". Kami menggunakan PHPUnit sebagai runner testing utama.

---

## 📋 Daftar Isi

1.  [Konfigurasi PHPUnit](#konfigurasi-phpunit)
2.  [Membuat Test Case](#membuat-test-case)
3.  [Menjalankan Test](#menjalankan-test)
4.  [Assertions Dasar](#assertions-dasar)

---

## Konfigurasi PHPUnit

File konfigurasi `phpunit.xml` ada di root folder. Anda bisa mengatur environment variabel khusus testing di sini (misal database in-memory SQLite).

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

---

## Membuat Test Case

Buat file test baru di folder `tests/`.

**Contoh: `tests/ExampleTest.php`**

```php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_basic_arithmetic()
    {
        $result = 1 + 1;
        $this->assertEquals(2, $result);
    }

    public function test_string_contains()
    {
        $string = 'The Framework';
        $this->assertStringContainsString('Framework', $string);
    }
}
```

---

## Menjalankan Test

Gunakan wrapper artisan untuk tampilan yang lebih cantik:

```bash
php artisan test
```

Atau jalankan PHPUnit manual:

```bash
./vendor/bin/phpunit
```

---

## Assertions Dasar

PHPUnit menyediakan ratusan metode assertion. Ini yang paling sering dipakai:

- `$this->assertTrue($condition)`
- `$this->assertFalse($condition)`
- `$this->assertEquals($expected, $actual)`
- `$this->assertCount($count, $array)`
- `$this->assertNull($variable)`
- `$this->assertIsArray($variable)`
