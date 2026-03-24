# 🧪 Advanced Testing System

The Framework menyediakan sistem testing yang setara dengan Laravel, namun dengan tambahan fitur **Performance Profiling** bawaan yang tidak dimiliki Laravel secara native.

---

## 📋 Daftar Isi

1. [Persiapan & Konfigurasi](#persiapan--konfigurasi)
2. [Membuat Test Case (Unit vs Feature)](#membuat-test-case)
3. [Feature Testing (HTTP Request)](#feature-testing-http-request)
4. [Database Testing & Transactions](#database-testing--transactions)
5. [Authentication & Session](#authentication--session)
6. [Response Assertions](#response-assertions)
7. [⚡ Performance Assertions (Beyond Laravel)](#-performance-assertions-beyond-laravel)
8. [🧪 TFWire Component Testing (Next Generation)](#-tfwire-component-testing-next-generation)

---

## Persiapan & Konfigurasi

Pastikan `phpunit.xml` Anda sudah diatur. Framework menyarankan penggunaan **SQLite in-memory** agar testing berjalan secepat kilat.

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

---

## Membuat Test Case

Selalu gunakan `Tests\TestCase` sebagai base class agar Anda mendapatkan akses ke seluruh helper premium framework.

### Unit Test (Logika Murni)
Gunakan untuk mengetes fungsi kecil tanpa HTTP.
```php
namespace Tests\Unit;
use Tests\TestCase;

class MathTest extends TestCase {
    public function test_penjumlahan() {
        $this->assertEquals(4, 2 + 2);
    }
}
```

### Feature Test (Simulasi User)
Gunakan untuk mengetes Endpoint/URL aplikasi.
```php
namespace Tests\Feature;
use Tests\TestCase;

class HomeTest extends TestCase {
    public function test_halaman_utama_bisa_diakses() {
        $response = $this->get('/');
        $response->assertSuccessful()->assertSee('Welcome');
    }
}
```

---

## Feature Testing (HTTP Request)

Anda dapat mensimulasikan berbagai metode HTTP dengan mudah:

```php
$this->get('/profile');
$this->post('/register', ['name' => 'Budi', 'email' => 'budi@mail.com']);
$this->put('/api/user/1', ['name' => 'Budi Update']);
$this->patch('/api/status', ['status' => 'active']);
$this->delete('/post/1');
$this->json('POST', '/api/data', ['key' => 'val']); // Request dengan Content-Type: JSON
```

---

## Database Testing & Transactions

Framework secara otomatis mendukung **Database Transactions**. Perubahan data saat testing akan di-rollback otomatis sehingga database Anda tetap bersih.

Cukup gunakan trait `DatabaseTransactions` di dalam class test Anda:

```php
use Tests\Traits\DatabaseTransactions;

class PostTest extends TestCase {
    use DatabaseTransactions;

    public function test_bisa_simpan_data() {
        $this->post('/posts', ['title' => 'Halo']);
        
        // Assertions Database:
        $this->assertDatabaseHas('posts', ['title' => 'Halo']);
        $this->assertDatabaseMissing('posts', ['title' => 'Hacker']);
        $this->assertDatabaseCount('posts', 1);
    }
}
```

---

## Authentication & Session

Simulasikan pengguna yang sedang login:

```php
public function test_halaman_admin() {
    $user = ['id' => 1, 'role' => 'admin'];
    
    $this->actingAs($user)
         ->get('/admin')
         ->assertSuccessful();
}

public function test_dengan_session_kustom() {
    $this->withSession(['cart' => [1, 2, 3]])
         ->get('/checkout')
         ->assertSee('Total');
}
```

---

## Response Assertions

Setelah melakukan request, Anda mendapatkan object `TestResponse` dengan helper berikut:

| Assertion | Deskripsi |
|-----------|-----------|
| `assertStatus(code)` | Memastikan HTTP Status Code |
| `assertSuccessful()` | Memastikan status 2xx |
| `assertRedirect(url)` | Memastikan terjadi pengalihan |
| `assertSee(text)` | Memastikan teks tertentu muncul di UI |
| `assertDontSee(text)` | Memastikan teks tertentu TIDAK muncul |
| `assertJson(array)` | Memastikan response JSON mengandung data tertentu |
| `assertNotFound()` | Alias untuk status 404 |
| `assertForbidden()` | Alias untuk status 403 |

---

## ⚡ Performance Assertions (Beyond Laravel)

The Framework menyertakan detektor performa bawaan agar aplikasi Anda tidak hanya "berfungsi", tapi juga **"cepat dan efisien"**.

### 1. Mengetes Kecepatan Eksekusi
Gunakan ini untuk mencegah terjadinya _latency_ tinggi pada endpoint krusial (misal API).
```php
$this->get('/api/heavy-data')
     ->assertExecutionTimeUnder(200); // Gagal jika eksekusi > 200ms
```

### 2. Mengetes Efisiensi Database (Anti N+1 Query)
Seringkali kita tidak sadar aplikasi kita melakukan ratusan query database karena loop yang tidak efisien. Framework bisa membatasi jumlah query yang diizinkan.
```php
$this->get('/users-list')
     ->assertQueryCount(2) // Gagal jika query bukan tepat 2
     ->assertQueriesLessThan(5); // Gagal jika query > 5 (Deteksi N+1)
```

---

## Menjalankan Test

Gunakan artisan untuk hasil yang rapi:
```bash
php artisan test
```

Metode manual PHPUnit:
```bash
./vendor/bin/phpunit
```
