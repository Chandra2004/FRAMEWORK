# 🗺️ SEO & Sitemap

The Framework memiliki fitur otomatis untuk membantu aplikasi Anda lebih mudah ditemukan oleh mesin pencari (_Search Engine Optimization_).

---

## 📑 Sitemap XML Otomatis

Sitemap membantu Google dan Bing mengindeks halaman aplikasi Anda. Framework menyediakan `SitemapController` yang meng-generate file XML secara dinamis.

### Cara Akses

Buka URL berikut di browser:
`https://yoursite.com/sitemap.xml`

### Cara Kerja

Framework akan secara otomatis memindai:

1.  **Rute Statis:** Semua rute `GET` yang terdaftar di `web.php` (kecuali rute sistem dan API).
2.  **Rute Dinamis (Blog):** Framework secara otomatis mencoba mengambil data dari model `Post` dan memasukkannya ke dalam sitemap.

---

## ⚙️ Konfigurasi `/robots.txt`

Pastikan Anda memiliki file `public/robots.txt` untuk mengizinkan bot mesin pencari:

```text
User-agent: *
Allow: /
Disallow: /_system/
Disallow: /api/

Sitemap: https://yoursite.com/sitemap.xml
```

---

## 🛠️ Optimasi Halaman (Meta Tags)

Gunakan helper `Helper::e()` untuk menampilkan meta tags dengan aman di dalam file Layout atau View:

```html
<title><?= $title ?? 'Default Title' ?></title>
<meta
  name="description"
  content="<?= Helper::e($description ?? 'Default description') ?>"
/>
<meta property="og:title" content="<?= Helper::e($title) ?>" />
<meta property="og:url" content="<?= Helper::url($_SERVER['REQUEST_URI']) ?>" />
```

---

## 📈 Tips SEO Tambahan

1.  **Canonical URL:** Selalu gunakan helper `Helper::url()` untuk memastikan link bersifat absolut.
2.  **WebP Images:** Gunakan `UploadHandler::handleUploadToWebP()` saat mengupload gambar agar ukuran file sangat kecil (disukai Google PageSpeed).
3.  **Slugification:** Gunakan `Helper::slugify()` untuk membuat URL artikel yang ramah SEO:
    `mysite.com/blog/belajar-php-dasar` lebih baik daripada `mysite.com/blog/1`.
4.  **HSTS:** Aktifkan HTTPS dan header HSTS (sudah aktif secara default di framework ini) untuk meningkatkan ranking keamanan di mata Google.
