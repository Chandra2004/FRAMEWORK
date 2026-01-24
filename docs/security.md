# 🛡️ Keamanan (Security)

Keamanan adalah aspek paling krusial. Framework ini mengimplementasikan best practice modern untuk melindungi aplikasi dari kerentanan umum (OWASP Top 10).

---

## 📋 Daftar Isi

1.  [CSRF Protection](#csrf-protection)
2.  [XSS Protection](#xss-protection)
3.  [SQL Injection](#sql-injection)
4.  [Enkripsi Data](#enkripsi-data)
5.  [Secure Headers](#secure-headers)

---

## CSRF Protection

**Cross-Site Request Forgery** adalah serangan yang memaksa pengguna yang sudah login untuk melakukan aksi yang tidak diinginkan.

Framework memblokir ini menggunakan **CSRF Token**.

### Cara Kerja

Setiap sesi pengguna digenerate token acak. Token ini harus dikirimkan setiap kali melakukan request `POST`, `PUT`, atau `DELETE`.

### Implementasi di Form

Gunakan helper `csrf_field()` atau input manual.

```html
<form method="POST" action="/profile">
  <input
    type="hidden"
    name="_token"
    value="<?= \TheFramework\Helpers\Helper::generateCsrfToken() ?>"
  />

  <!-- Input lainnya -->
  <button type="submit">Simpan</button>
</form>
```

Middleware `TheFramework\Middleware\CsrfMiddleware` akan otomatis mencegat request yang tokennya salah atau kadaluarsa.

---

## XSS Protection

**Cross-Site Scripting** terjadi saat aplikasi menampilkan input pengguna mentah-mentah ke browser.

### Pencegahan

Selalu gunakan fungsi escaping saat mencetak variabel. Framework menyediakan helper `e()`.

```php
// ❌ TIDAK AMAN: <script>alert('hack')</script> akan jalan!
echo $userInput;

// ✅ AMAN: Output menjadi &lt;script&gt;...
echo Helper::e($userInput);
```

Jika Anda menggunakan syntax view `<?= $var ?>`, pastikan Anda MEMBUNGKUSNYA dengan `e()` jika itu input dari user luar.

---

## SQL Injection

Framework menggunakan **PDO Prepared Statements** untuk semua operasi database (baik lewat Model maupun Query Builder).

Ini artinya: Query SQL dan Data dipisahkan. Database server memperlakukan input pengguna murni sebagai data, bukan perintah SQL yang bisa dieksekusi.

```php
// ✅ AMAN (Prepared):
Database::query("SELECT * FROM users WHERE email = ?", [$email]);

// ❌ JANGAN PERNAH LAKUKAN INI!:
Database::query("SELECT * FROM users WHERE email = '$email'");
```

---

## Enkripsi Data

Framework menyediakan layanan enkripsi OpenSSL (AES-256-CBC) untuk menyimpan data sensitif.

Pastikan `APP_KEY` di `.env` sudah diisi (via `php artisan setup`) karena key ini digunakan sebagai salt enkripsi.

```php
use TheFramework\Helpers\Crypter;

// Enkripsi
$rahasia = Crypter::encrypt('Nomor KTP Saya');

// Dekripsi
$asli = Crypter::decrypt($rahasia);
```

---

## Secure Headers

Secara default, respon HTTP dari framework menyertakan header keamanan untuk memitigasi serangan browser:

- `X-Content-Type-Options: nosniff` (Mencegah sniffing MIME type).
- `X-Frame-Options: SAMEORIGIN` (Mencegah Clickjacking/Iframe embedding).
- `X-XSS-Protection: 1; mode=block` (Mengaktifkan filter XSS browser lama).
