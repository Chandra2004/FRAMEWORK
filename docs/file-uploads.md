# 📤 File Uploads

Framework ini menyertakan `UploadHandler`, sebuah class universal yang aman dan bertenaga untuk menangani segala jenis upload file.

---

## 🚀 Fitur Utama

- **Keamanan Ketat:** Validasi ekstensi dan real MIME-type.
- **Auto-Processing:** Resize gambar otomatis dan konversi ke format modern (**WebP**).
- **Struktur Rapi:** Pengorganisasian folder upload secara otomatis.
- **XSS Protection:** Pengamanan khusus terhadap file berbahaya (seperti SVG mentah).

---

## 📖 Cara Penggunaan Dasar

Gunakan method statis `UploadHandler::upload()` di Controller Anda.

```php
use TheFramework\Config\UploadHandler;
use TheFramework\Helpers\Helper;

public function saveProfile() {
    if (isset($_FILES['avatar'])) {
        $result = UploadHandler::upload($_FILES['avatar'], [
            'uploadDir'    => '/avatars',
            'allowedTypes' => ['jpg', 'png', 'webp'],
            'maxSize'      => 2 * 1024 * 1024, // 2MB
            'convertTo'    => 'webp'           // Otomatis ubah ke WebP
        ]);

        if ($result['success']) {
            $filename = $result['filename']; // Simpan nama file ini ke DB
            Helper::set_flash('success', 'Foto profil berhasil diupload!');
        } else {
            Helper::set_flash('error', $result['error']);
        }
    }
}
```

---

## ⚙️ Opsi Konfigurasi

| Opsi           | Tipe   | Default    | Deskripsi                                               |
| :------------- | :----- | :--------- | :------------------------------------------------------ |
| `uploadDir`    | string | `/default` | Sub-direktori tujuan di dalam `public/assets/uploads/`. |
| `prefix`       | string | `file_`    | Awalan nama file yang digenerate (agar unik).           |
| `maxSize`      | int    | 10MB       | Batas ukuran file dalam bytes.                          |
| `allowedTypes` | array  | [...]      | Array ekstensi yang diizinkan (tanpa titik).            |
| `convertTo`    | string | `null`     | Target format gambar (`webp`, `jpg`, `png`).            |
| `resize`       | array  | `null`     | `['width' => 800, 'height' => 600, 'quality' => 80]`    |

---

## 🔒 Security Best Practices

### 1. SVG XSS Vulnerability

Secara default, **SVG dilarang** karena kerentanan keamanan di mana script berbahaya bisa disisipkan ke dalam file XML SVG. Jika Anda sangat membutuhkan upload SVG, gunakan library sanitizer tambahan.

### 2. Permissions

Pastikan folder `public/assets/uploads/` memiliki izin tulis oleh web server:

```bash
chmod -R 775 public/assets/uploads/
```

### 3. MIME Validation

`UploadHandler` melakukan pengecekan ganda antara ekstensi file dan isi file (_magic bytes_). Jangan matikan fitur `validateMime` kecuali jika diperlukan.

---

## 🖼️ Helper Khusus WebP (Optimasi Gambar)

Jika Anda hanya butuh upload gambar dan ingin hasil yang super ringan untuk SEO, gunakan helper instan ini:

```php
$filename = UploadHandler::handleUploadToWebP($_FILES['image'], '/blog', 'post_');
// Hasil: 'post_827364.webp'
```
