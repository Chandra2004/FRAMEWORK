# Security Features

Security is a first-class citizen in The Framework. Kami menyediakan proteksi berlapis secara default.

## 1. Web Application Firewall (WAF) Middleware

`TheFramework\Middleware\WAFMiddleware` melindungi aplikasi dari serangan umum:

- SQL Injection (SQLi) patterns.
- Cross-Site Scripting (XSS) patterns.
- Local File Inclusion (LFI).
- User-Agent mencurigakan.

Gunakan middleware ini pada route publik Anda.

```php
Router::add('GET', '/', HomeController::class, 'index', [WAFMiddleware::class]);
```

## 2. CSRF Protection

Cross-Site Request Forgery (CSRF) dicegah dengan token yang divalidasi pada setiap request POST/PUT/DELETE.

1.  Pastikan `CsrfMiddleware` dipasang pada route yang mengubah state.
2.  Di View (Blade), gunakan helper `@csrf` di dalam form HTML.

```html
<form method="POST" action="/update">
  @csrf
  <input type="text" name="name" />
  <button type="submit">Save</button>
</form>
```

## 3. Secure Headers

Secara default, file `bootstrap/app.php` mengirimkan header HTTP keamanan standar industri:

- `X-Frame-Options: DENY` (Anti Clickjacking)
- `X-Content-Type-Options: nosniff` (Mencegah MIME sniffing)
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security` (HSTS)
- `Content-Security-Policy` (CSP) - _Perlu dikonfigurasi lebih lanjut sesuai kebutuhan aset Anda_.

## 4. Encryption

Framework menyediakan wrapper untuk library `defuse/php-encryption`.

Konfigurasi Key di `.env`:

```ini
ENCRYPTION_KEY=def00000...
```

Gunakan helper atau service untuk mengenkripsi data sensitif sebelum masuk database.

## 5. Private File Uploads

Folder `private-uploads/` di root proyek **TIDAK** dapat diakses langsung via browser (403 Forbidden oleh Web Server).

Untuk menyajikan file ini ke user yang terautentikasi, gunakan route khusus:
`/file/{filename}` yang dikontrol oleh `FileController`. Controller ini akan memverifikasi hak akses user sebelum mengirimkan konten file menggunakan `readfile()`.
