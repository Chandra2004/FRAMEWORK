# SECURITY GUIDE

Keamanan bukan fitur tambahan, melainkan fondasi utama dari The-Framework.

## 1. Web Application Firewall (WAF)

Middleware `WAFMiddleware` terpasang secara default untuk semua request HTTP. WAF ini memeriksa payload request (GET, POST, COOKIE) terhadap pola serangan umum:

- **SQL Injection**: `UNION SELECT`, `DROP TABLE`, dll.
- **XSS (Cross Site Scripting)**: `<script>`, `javascript:`, `onerror=`.
- **Path Traversal**: `../`, `/etc/passwd`.

Jika serangan terdeteksi, request langsung ditolak dengan status **403 Forbidden** sebelum menyentuh controller Anda.

## 2. CSRF Protection

Cross-Site Request Forgery dicegah menggunakan token yang divalidasi pada setiap request `POST`, `PUT`, `DELETE`.

- **Form Helper**: Gunakan directive `@csrf` di Blade view Anda.
  ```html
  <form method="POST">@csrf ...</form>
  ```
- **Ajax**: Token otomatis disisipkan di meta tag `csrf-token` dan header `X-CSRF-TOKEN`.

## 3. Secure Headers

Response HTTP otomatis menyertakan header keamanan modern:

- `X-Frame-Options: DENY`: Mencegah Clickjacking (iframe embedding).
- `X-Content-Type-Options: nosniff`: Mencegah MIME sniffing.
- `Strict-Transport-Security (HSTS)`: Memaksa browser menggunakan HTTPS.
- `Permissions-Policy`: Mematikan akses fitur sensitif browser (kamera, mic, lokasi) kecuali dibutuhkan.

## 4. Upload Security (UploadHandler)

Fitur upload file (`UploadHandler`) sangat ketat (Paranoid Mode):

- **Randomized Filename**: File di-rename menggunakan `uniqid` untuk mencegah eksekusi file berbahaya dengan nama yang bisa ditebak.
- **Extension & MIME Validation**: Memastikan file gambar benar-benar gambar (header content), bukan script PHP yang disamarkan.
- **WebP Conversion**: Upload gambar otomatis dikonversi ke WebP, stripping metadata berbahaya (EXIF) yang mungkin tertanam.

## 5. Rate Limiting

Mencegah Brute Force dan DoS serangan ringan.

- Config: `app/App/RateLimiter.php`
- Default: 100 request per menit per IP (bisa disesuaikan).
- Storage: Menggunakan File Cache yang cepat.

---

## Best Practices untuk Developer

1. **Gunakan Query Builder / Binding**: Jangan pernah melakukan raw query dengan konkatinasi string variabel input.

   - ❌ `$db->query("SELECT * FROM users WHERE name = '$name'")`
   - ✅ `$user->where('name', '=', $name)` (Parameter Binding otomatis)

2. **Output Escaping**: Blade `{{ $variable }}` otomatis melakukan `htmlspecialchars`. Gunakan `{!! $variable !!}` HANYA jika Anda yakin data tersebut aman (HTML terpercaya).
