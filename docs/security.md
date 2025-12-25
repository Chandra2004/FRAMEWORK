> **Version**: 4.0.0 | **Author**: Chandra Tri A | **Updated**: 2025

# 🛡️ Security Features Guide

Framework ini menganut filosofi **"Security by Design"**. Fitur-fitur keamanan berikut aktif secara default untuk melindungi aplikasi Anda dari serangan umum OWASP Top 10.

---

## 1. WAF Middleware (Web Application Firewall)

Setiap request yang masuk akan diperiksa oleh `WAFMiddleware` sebelum mencapai Controller. Middleware ini memindai pola serangan pada `GET`, `POST`, dan URI.

**Serangan yang Diblokir:**

- **SQL Injection**: Pattern seperti `UNION SELECT`, `DROP TABLE`, `1=1`.
- **XSS (Cross Site Scripting)**: Tag `<script>`, event handler `onload=`, `javascript:`.
- **Path Traversal**: Percobaan akses file sistem seperti `../etc/passwd`.
- **Command Injection**: Fungsi eksekusi shell `exec(`, `system(`.

**Konfigurasi:**
File: `app/Middleware/WAFMiddleware.php`

- Anda dapat menyesuaikan pola Regex jika terjadi _false positive_.
- Di mode `local`, WAF akan menampilkan JSON detail serangan.
- Di mode `production`, WAF akan langsung memutus koneksi (403 Forbidden) tanpa detail.

---

## 2. CSRF Protection (Cross-Site Request Forgery)

Mencegah serangan di mana website berbahaya memaksa browser user untuk melakukan aksi yang tidak diinginkan di aplikasi Anda.

**Cara Kerja:**
Framework menghasilkan token unik setiap session. Setiap form `POST`, `PUT`, `DELETE` wajib menyertakan token ini.

**Penggunaan di Blade:**

```html
<form method="POST" action="/profile/update">
  @csrf
  <!-- Output: <input type="hidden" name="_token" value="a1b2c3..."> -->

  <button type="submit">Simpan</button>
</form>
```

**Pada Request AJAX:**
Ambil token dari helper atau meta tag:

```javascript
let token = "{{ csrf_token() }}";
// Sertakan dalam data POST sebagai '_token'
```

---

## 3. SQL Injection Prevention (PDO Binding)

Core Database (`app/App/Database.php`) menggunakan **PDO (PHP Data Objects)** dengan **Prepared Statements** sepenuhnya.

**JANGAN** melakukan ini (Raw Query tidak aman):

```php
// ❌ BAHAYA! Rentan SQL Injection
$db->query("SELECT * FROM users WHERE email = '$email'");
```

**LAKUKAN** ini (Aman):

```php
// ✅ AMAN! Menggunakan Parameter Binding
$db->query("SELECT * FROM users WHERE email = :email");
$db->bind(':email', $inputEmail);
$results = $db->resultSet();
```

Atau gunakan Query Builder yang otomatis aman:

```php
// ✅ AMAN & PRAKTIS
$user = User::where('email', $inputEmail)->first();
```

---

## 4. XSS Protection (Output Escaping)

Saat menampilkan data di View (Blade), framework otomatis melakukan escaping karakter berbahaya HTML.

**Escaped Output (Default):**

```html
<!-- Input: <script>alert('hack')</script> -->
Hello, {{ $name }}
<!-- Output: Hello, &lt;script&gt;alert('hack')&lt;/script&gt; -->
```

**Raw Output (Hati-hati):**
Gunakan hanya jika Anda yakin data tersebut aman (misal HTML dari editor teks terpercaya).

```html
{!! $content !!}
```

---

## 5. Rate Limiting (Anti-Brute Force & DDoS)

Mencegah client membanjiri server dengan terlalu banyak request dalam waktu singkat.

**Konfigurasi Default:**
Lokasi: `bootstrap/app.php`

- **Limit**: 100 request
- **Window**: 120 detik (2 menit)
- Penyimpanan: File-based cache di `storage/cache/ratelimit/`.

Jika limit terlampaui, server akan merespon dengan **HTTP 429 Too Many Requests**.

---

## 6. Secure Headers

Framework secara otomatis menambahkan HTTP Security Headers pada setiap respons (`bootstrap/app.php`) untuk mengeraskan pertahanan browser.

| Header                      | Value                        | Fungsi                                        |
| :-------------------------- | :--------------------------- | :-------------------------------------------- |
| `X-Frame-Options`           | `DENY`                       | Mencegah Clickjacking (tidak bisa di-iframe). |
| `X-Content-Type-Options`    | `nosniff`                    | Mencegah browser menebak MIME type file.      |
| `X-XSS-Protection`          | `1; mode=block`              | Mengaktifkan filter XSS bawaan browser.       |
| `Referrer-Policy`           | `no-referrer-when-downgrade` | Menjaga privasi URL referrer.                 |
| `Strict-Transport-Security` | `max-age=31536000`           | Memaksa HTTPS (HSTS).                         |

---

## 7. Secure Session & Cookie `HttpOnly`

Session dikonfigurasi (`app/App/SessionManager.php`) agar:

- **HttpOnly**: Cookie session tidak bisa diakses via JavaScript (anti XSS session hijacking).
- **Secure**: Cookie hanya dikirim via HTTPS (jika server support).
- **SameSite**: Strict/Lax untuk perlindungan CSRF tambahan.

---

## 8. Secure File Upload

`UploadHandler` (`app/Config/UploadHandler.php`) melakukan validasi berlapis:

1.  **MIME Type Check**: Menggunakan `finfo_file` (Magic Bytes) untuk memastikan file asli gambar, bukan script PHP yang di-rename `.jpg`.
2.  **Extension Whitelist**: Hanya mengizinkan ekstensi tertentu.
3.  **Auto Rename**: File di-rename menjadi random string (`5f3a...jpg`) untuk mencegah eksekusi file berbahaya yang mungkin lolos filter nama.
