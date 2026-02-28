# 🚀 REST API Development Tutorial

**Coming Soon!**

This tutorial will cover:

1. RESTful API Principles
2. JSON Response Formatting
3. API Authentication (JWT/Bearer Token)
4. Rate Limiting
5. API Versioning
6. Error Handling
7. API Documentation (Swagger)

---

### 3. API Authentication (Bearer Token)

Framework mendukung autentikasi API menggunakan Token. Berikut cara proteksi rute API:

**Rute API dengan Middleware:**

```php
Router::group(['prefix' => '/api/v1', 'middleware' => [ApiAuthMiddleware::class]], function() {
    Router::get('/profile', [ProfileController::class, 'index']);
});
```

**Controller Login (Generate Token):**

```php
public function login() {
    $user = User::where('email', $_POST['email'])->first();

    if ($user && Helper::verify_password($_POST['password'], $user->password)) {
        $token = Helper::authToken(['id' => $user->id]); // Buat token JWT-like
        return $this->json(['token' => $token]);
    }

    return $this->json(['error' => 'Unauthorized'], 401);
}
```

---

## 🛡️ Rate Limiting

Lindungi API Anda dari serangan brute force atau spamming.

```php
use TheFramework\App\RateLimiter;

public function createUser() {
    if (RateLimiter::tooManyAttempts('api-create-' . Helper::get_client_ip(), 5, 60)) {
        return $this->json(['error' => 'Too many requests'], 429);
    }

    // ... proses simpan ...
}
```

---

## 📝 Dokumentasi API (OpenAPI)

Sangat disarankan untuk mendokumentasikan API Anda menggunakan standar OpenAPI/Swagger.

1. Simpan spesifikasi API Anda di `public/api-docs.json`.
2. Gunakan **Swagger UI** (tersedia sebagai paket composer atau via CDN) untuk menampilkan dokumentasi yang interaktif.

```html
<!-- Contoh di view docs-api.blade.php -->
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
<script>
  SwaggerUIBundle({
    url: "/api-docs.json",
    dom_id: "#swagger-ui",
  });
</script>
```

---

## 🔍 Tips API Development

1. **Versioning:** Gunakan prefix `/v1/`, `/v2/` agar perubahan di masa depan tidak merusak aplikasi user yang sudah ada.
2. **Standard Status Codes:**
   - `200 OK`: Berhasil.
   - `201 Created`: Berhasil membuat data baru.
   - `400 Bad Request`: Input salah.
   - `401 Unauthorized`: Belum login/token salah.
   - `403 Forbidden`: Login berhasil tapi tidak punya akses.
   - `429 Too Many Requests`: Kena batasan rate limit.
3. **JSON Only:** Pastikan header `Content-Type: application/json` selalu dikirim.
