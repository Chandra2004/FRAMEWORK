# 🚀 REST API Development — v5.0.1 Premium

Panduan ini menjelaskan cara membangun antarmuka pemrograman aplikasi (API) yang aman, cepat, dan terstandarisasi menggunakan **The Framework v5.0.1**.

---

## 🏗️ Struktur Dasar API

Respon API di The Framework selalu menggunakan format JSON yang konsisten.

### 1. Controller API

Gunakan helper `Helper::json()` atau method `$this->json()` (jika tersedia di base controller) untuk memberikan respon.

```php
namespace TheFramework\Http\Controllers\Api;

use TheFramework\Helpers\Helper;
use TheFramework\Models\Product;

class ProductApiController
{
    public function index()
    {
        $products = Product::latest()->get();

        return Helper::json([
            'status'  => 'success',
            'data'    => $products,
            'message' => 'Data produk berhasil dimuat.'
        ]);
    }
}
```

---

## 🛡️ Keamanan & Autentikasi (Bearer Token)

The Framework menyediakan `ApiAuthMiddleware` untuk melindungi endpoint Anda.

### 1. Mendaftarkan Rute Terproteksi

Tambahkan middleware pada grup rute API di `routes/web.php` atau `routes/api.php`:

```php
use TheFramework\App\Http\Router;
use TheFramework\Middleware\ApiAuthMiddleware;

Router::group(
    [
        'prefix' => '/api/v1',
        'middleware' => [ApiAuthMiddleware::class]
    ],
    function() {
        Router::get('/profile', ProfileApiController::class, 'me');
        Router::post('/products', ProductApiController::class, 'store');
    }
);
```

### 2. Cara Kerja Authentikasi

Middleware akan mengecek header `Authorization: Bearer <token>`. Token ini kemudian divalidasi ke kolom `api_token` di tabel `users`.

---

## ⏱️ Rate Limiting

Lindungi resource Anda dari serangan brute-force menggunakan `RateLimiter`.

```php
use TheFramework\App\Http\RateLimiter;
use TheFramework\Helpers\Helper;

public function login()
{
    $key = 'login-attempt-' . Helper::get_client_ip();

    // Batasi 5 kali percobaan per 60 detik
    RateLimiter::check($key, 5, 60);

    // Logika login...
}
```

---

## 🔍 Standar HTTP Status Codes

Gunakan status code yang tepat untuk setiap respon:

| Code  | Status            | Deskripsi                                |
| :---- | :---------------- | :--------------------------------------- |
| `200` | OK                | Request berhasil.                        |
| `201` | Created           | Resource baru berhasil dibuat.           |
| `400` | Bad Request       | Validasi gagal atau input salah.         |
| `401` | Unauthorized      | Token tidak valid atau tidak disertakan. |
| `403` | Forbidden         | Akses ditolak (izin tidak cukup).        |
| `404` | Not Found         | Resource tidak ditemukan.                |
| `429` | Too Many Requests | Terkena batas Rate Limit.                |
| `500` | Server Error      | Kesalahan internal pada server.          |

---

## 📝 Best Practices

1. **Versioning**: Selalu gunakan prefix versi (misal `/v1/`) agar integrasi aplikasi client tidak rusak saat ada perubahan besar di masa depan.
2. **HTTPS**: Selalu jalankan API di atas protokol HTTPS untuk mengamankan Bearer Token selama transmisi.
3. **Filtering & Pagination**: Jangan mengembalikan seluruh data ribuan baris sekaligus. Gunakan method `->paginate()` dari Model.
4. **Validation**: Gunakan `FormRequest` untuk memvalidasi input API agar pesan error tetap konsisten.

---

<div align="center">

**The Framework API Engine — Fast & Secure** ⚡

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
