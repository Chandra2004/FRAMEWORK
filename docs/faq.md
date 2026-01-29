# ❓ Frequently Asked Questions (FAQ)

Common questions and answers about The Framework.

---

## 📑 Daftar Isi

- [🏠 Umum (General)](#general)
- [📥 Instalasi (Installation)](#installation)
- [🗄️ Database](#database)
- [🔒 Keamanan (Security)](#security)
- [⚡ Performa (Performance)](#performance)
- [🚀 Deployment](#deployment)
- [🛠️ Pengembangan (Development)](#development)
- [🐞 Debugging & Error](#errors--debugging)
- [📚 Lain-lain (Miscellaneous)](#miscellaneous)

---

## General

### Q: Apa bedanya The Framework dengan Laravel?

**A:** The Framework dirancang khusus untuk shared hosting dan developer dengan resource terbatas:

| Feature            | Laravel       | The Framework                   |
| ------------------ | ------------- | ------------------------------- |
| **Hosting**        | Perlu VPS/SSH | ✅ Works on free shared hosting |
| **Size**           | ~70MB         | ~15MB (lightweight)             |
| **Learning Curve** | Medium-High   | Low-Medium                      |
| **Web Management** | ❌ No         | ✅ Web Command Center           |
| **Syntax**         | Laravel       | Laravel-like                    |

**Use Laravel jika:** Anda punya budget VPS dan butuh ecosystem lengkap  
**Use The Framework jika:** Anda pakai shared hosting atau budget terbatas

---

### Q: Apakah production-ready?

**A:** **YA** untuk aplikasi skala kecil-menengah. Version 5.0.0 memiliki security grade **A-** dan sudah melewati comprehensive security audit.

**Production-ready untuk:**

- ✅ Company profile
- ✅ Blog / CMS
- ✅ Landing pages
- ✅ Small SaaS (< 1000 users/day)
- ✅ Internal tools

**Belum optimal untuk:**

- ⚠️ High-traffic apps (10K+ users/day)
- ⚠️ Real-time applications
- ⚠️ Microservices architecture

---

### Q: Apakah gratis?

**A:** **YA, 100% gratis** dan open-source (MIT License). Anda bebas:

- ✅ Menggunakan untuk projek komersial
- ✅ Memodifikasi source code
- ✅ Mendistribusikan ulang

---

## Installation

### Q: Composer install gagal, bagaimana?

**A:** Coba langkah ini:

```bash
# 1. Clear cache
composer clear-cache

# 2. Update composer
composer self-update

# 3. Install ulang
rm -rf vendor/
composer install

# 4. Jika masih error, install without scripts
composer install --no-scripts

# 5. Lalu dump autoload manual
composer dump-autoload
```

---

### Q: Bisa install tanpa Composer?

**A:** **YA**, untuk shared hosting tanpa composer:

1. Install di local (dengan composer)
2. Upload folder `vendor/` via FTP
3. Upload file lainnya
4. Setup `.env`
5. Run migration via Web Command Center

📖 [Deployment Guide](deployment.md)

---

### Q: PHP version harus 8.3?

**A:** **YA**, minimum PHP 8.3. Ini requirement karena:

- Type declarations (return types)
- Match expressions
- Named arguments
- Modern security features

Jika hosting Anda PHP 7.x, upgrade dulu atau ganti hosting.

---

## Database

### Q: Support database apa saja?

**A:** Currently:

- ✅ **MySQL** (fully tested)
- ✅ **MariaDB** (fully tested)
- ⚠️ **PostgreSQL** (experimental)
- ❌ SQLite (not tested)
- ❌ SQL Server (not supported)

---

### Q: Bagaimana cara migrate database di shared hosting?

**A:** Gunakan **Web Command Center**:

```
1. Upload files via FTP
2. Create database via cPanel
3. Edit .env dengan credentials database
4. Akses: https://yoursite.com/_system/migrate
```

📖 [Web Command Center Guide](web-command-center.md)

---

### Q: Database connection timeout?

**A:** Edit `.env`:

```bash
DB_CONNECTION=mysql
DB_HOST=localhost  # Coba ganti ke 127.0.0.1
DB_PORT=3306
DB_NAME=your_db
DB_USER=your_user
DB_PASS=your_pass

# Tambahkan:
DB_TIMEZONE=+00:00
```

---

## Security

### Q: Apakah aman untuk production?

**A:** **YA**, dengan catatan:

**Built-in Security (v5.0.0):**

- ✅ SQL Injection protection (Prepared Statements)
- ✅ XSS protection (Auto-escaping)
- ✅ CSRF protection (Token validation)
- ✅ Command Injection protection (WAF)
- ✅ Path Traversal protection (Realpath validation)
- ✅ Security headers (HSTS, CSP, X-Frame-Options)

**Yang HARUS Anda lakukan:**

- ✅ Set `APP_DEBUG=false` di production
- ✅ Use HTTPS
- ✅ Generate strong `APP_KEY`
- ✅ Disable Web Command Center setelah deployment
- ✅ Regular updates

---

### Q: Web Command Center aman?

**A:** **YA**, jika dikonfigurasi benar (v5.0.0):

**3-Layer Security:**

1. Feature Toggle (`ALLOW_WEB_MIGRATION`)
2. IP Whitelist (`SYSTEM_ALLOWED_IPS`)
3. Basic Auth (required)

**Best Practice:**

```bash
# Production .env
ALLOW_WEB_MIGRATION=false  # Disable setelah deploy!
SYSTEM_ALLOWED_IPS=YOUR_OFFICE_IP  # NEVER '*'
SYSTEM_AUTH_USER=admin
SYSTEM_AUTH_PASS=strong_password_here
```

---

## Performance

### Q: Framework ini lambat?

**A:** Tidak! Benchmark:

| Framework         | Cold Start | Warm Request | Memory |
| ----------------- | ---------- | ------------ | ------ |
| **The Framework** | ~50ms      | ~15ms        | ~5MB   |
| Laravel 11        | ~80ms      | ~25ms        | ~15MB  |
| CodeIgniter 4     | ~40ms      | ~12ms        | ~3MB   |

**Tips untuk performa maksimal:**

- ✅ Enable OPcache
- ✅ Use route caching
- ✅ Eager load relationships
- ✅ Cache query results

---

### Q: Bagaimana cara enable caching?

**A:**

```php
// Query caching
$posts = Post::query()
    ->where('published', true)
    ->remember(3600)  // Cache 1 hour
    ->get();

// Route caching
composer dump-autoload  // Auto-cache routes
```

---

## Deployment

### Q: Hosting mana yang recommended?

**A:**

**Free Hosting (Good for Learning):**

- ✅ InfinityFree (**recommended**)
- ✅ 000webhost
- ⚠️ Hostinger Free (limited)

**Paid Hosting (Production):**

- ✅ Hostinger (₹59/month)
- ✅ Niagahoster ($1/month)
- ✅ DigitalOcean ($6/month VPS)

---

### Q: Bisa deploy di Vercel/Netlify?

**A:** **TIDAK**. Vercel/Netlify untuk static sites atau serverless. Framework ini butuh:

- PHP runtime
- MySQL database
- Persistent file storage

**Alternative:**

- Railway.app (support PHP)
- Heroku (support PHP)
- VPS (DigitalOcean, Linode)

---

## Development

### Q: Artisan command custom bisa dibuat?

**A:** **YA**!

```bash
php artisan make:command SendNewsletterCommand
```

📖 [Artisan Guide](artisan.md)

---

### Q: Support real-time features (WebSocket)?

**A:** **BELUM**. Version 5.0.0 tidak include WebSocket server.

**Alternative:**

- Use Pusher API
- Use Firebase Realtime Database
- Self-host Socket.io server

Planned untuk v6.0.0.

---

## Errors & Debugging

### Q: "Class not found" error?

**A:**

```bash
composer dump-autoload

# Jika masih error
composer dump-autoload -o
```

---

### Q: "Permission denied" untuk storage/?

**A:**

```bash
chmod 755 -R .
chmod 777 -R storage/
chmod 777 -R storage/logs/
chmod 777 -R storage/framework/views/
```

---

### Q: Dimana error logs?

**A:** `storage/logs/app-YYYY-MM-DD.log`

```bash
# View latest log
tail -f storage/logs/app-2026-01-24.log
```

---

### Q: 404 di semua routes kecuali homepage?

**A:** Apache `mod_rewrite` tidak aktif.

**Fix:**

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo service apache2 restart

# Check .htaccess exists
ls -la public/.htaccess
```

---

## Miscellaneous

### Q: Bisa pakai Blade template engine?

**A:** **YA**! Framework menggunakan **Illuminate/View** (Blade engine dari Laravel).

```blade
@extends('layouts.app')

@section('content')
    <h1>{{ $title }}</h1>
@endsection
```

---

### Q: Support API development?

**A:** **YA**!

```php
// routes/api.php
Router::group(['prefix' => '/api'], function() {
    Router::get('/users', [ApiController::class, 'users']);
    Router::post('/posts', [ApiController::class, 'createPost']);
});

// Return JSON
public function users()
{
    $users = User::all();

    header('Content-Type: application/json');
    echo json_encode(['data' => $users]);
}
```

---

### Q: Dokumentasi kurang lengkap?

**A:** Kami terus update! Saat ini ada **27+ documentation files**.

**Missing docs?** Create issue di GitHub:  
https://github.com/chandra2004/the-framework/issues

---

### Q: Bagaimana cara contribute?

**A:**

1. Fork repository
2. Create branch: `git checkout -b feature/amazing-feature`
3. Commit: `git commit -m 'Add amazing feature'`
4. Push: `git push origin feature/amazing-feature`
5. Open Pull Request

📖 [CONTRIBUTING.md](../CONTRIBUTING.md)

---

### Q: Ada community/forum?

**A:** **Coming soon!**

Sementara:

- 📧 Email: support@the-framework.ct.ws
- 🐛 GitHub Issues: Report bugs
- 💬 Discord: _(planned)_

---

## Still Have Questions?

- 📖 Read [Full Documentation](README.md)
- 📧 Email: support@the-framework.ct.ws
- 🐛 GitHub: https://github.com/chandra2004/the-framework

---

<div align="center">

**Can't find your answer? Ask us!**

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
