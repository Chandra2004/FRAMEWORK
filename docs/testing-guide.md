# 🧪 Testing Guide

The Framework dibangun dengan fondasi yang mendukung pengujian otomatis menggunakan **PHPUnit**. Dokumentasi ini akan membantu Anda menulis tes untuk memastikan aplikasi Anda tetap berjalan dengan benar saat fitur baru ditambahkan.

---

## ⚙️ Persiapan

### 1. File Konfigurasi

Pastikan file `phpunit.xml` sudah ada di root proyek. Framework menggunakan database MySQL khusus untuk testing (biasanya namanya `your_db_test`) agar data asli Anda tidak terhapus.

### 2. Jalankan Tes

Buka terminal dan ketik:

```bash
vendor/bin/phpunit
```

---

## 📂 Struktur Folder

- `tests/Unit/`: Untuk mengetes fungsi kecil secara terisolasi (misal: Helper, utilitas).
- `tests/Feature/`: Untuk mengetes alur aplikasi dari sudut pandang user (misal: buka halaman login, simpan data).

---

## 📝 Contoh Unit Test

Gunakan Unit Test jika Anda hanya ingin mengetes logika kode tanpa menyentuh database.

```php
namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\Helpers\Helper;

class UtilityTest extends TestCase
{
    public function test_slug_generator()
    {
        $result = Helper::slugify('Belajar PHP Framework');
        $this->assertEquals('belajar-php-framework', $result);
    }
}
```

---

## 🌐 Contoh Feature Test

Gunakan Feature Test untuk mengetes Response HTTP dan interaksi antar komponen.

```php
namespace Tests\Feature;

use Tests\TestCase;

class ContactTest extends TestCase
{
    public function test_page_contact_is_accessible()
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Hubungi Kami');
    }

    public function test_submit_form_contact()
    {
        $response = $this->post('/contact/send', [
            'name' => 'Chandra',
            'message' => 'Halo framework!'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }
}
```

---

## 🛠️ Rekomendasi Testing

1.  **Gunakan Mocking:** Hindari mengirim email asli atau memproses pembayaran asli saat testing.
2.  **Isolated Database:** Selalu gunakan database terpisah untuk testing.
3.  **Tulis Tes Sebelum Kode (TDD):** Menulis tes terlebih dahulu membantu Anda merancang arsitektur kode yang lebih bersih.
4.  **Cek Log:** Jika tes gagal dengan error 500, cek file `storage/logs/` untuk detailnya.
