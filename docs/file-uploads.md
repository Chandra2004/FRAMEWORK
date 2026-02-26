# 📤 Upload Handler (Advanced Engine)

`UploadHandler` bukan sekadar pemindah file, melainkan sebuah mesin pemroses file (File Engine) yang mendukung optimasi gambar modern secara otomatis.

---

## 🚀 Fitur "Paten"
- **Auto-WebP**: Otomatis mengonversi gambar (JPG/PNG) menjadi WebP saat upload untuk performa SEO maksimal.
- **Smart Resize**: Mengecilkan ukuran gambar secara proporsional.
- **Security Vault**: Menyimpan file di folder `/storage/app` (lebih aman daripada folder publik) dan memblokir file berbahaya (PHP/SVG-XSS).

---

## ⚙️ Konfigurasi
Anda bisa mengatur batas ukuran dan jenis file yang diizinkan di `config/upload.php`.

```php
// config/upload.php
return [
    'max_size'   => 10 * 1024 * 1024, // 10MB
    'webp_quality' => 80,
    // ...
];
```

---

## 📖 Cara Penggunaan

### 1. Upload Dasar & Kontrol Gambar
Gunakan method `save()` untuk menangani file dari `$_FILES`.

```php
use TheFramework\Handlers\UploadHandler;

$uploader = new UploadHandler();

try {
    $result = $uploader->save($_FILES['avatar'], '/users/avatars', [
        'width'      => 400,    // Resize lebar ke 400px
        'convert_to' => 'webp', // Paksa hasil jadi WebP
        'quality'    => 90      // Kualitas WebP
    ]);

    if ($result['success']) {
        echo "Nama File: " . $result['name']; // Simpan ini ke database
    }
} catch (\Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
```

### 2. Deskripsi Hasil (Return Array)
Setiap upload yang berhasil akan mengembalikan informasi lengkap:
- `name`: Nama file yang digenerate secara unik.
- `path`: Path relatif untuk disimpan di database (misal: `/users/avatars/up_65abc.webp`).
- `type`: Tipe file akhir (misal: `image/webp`).

---

## 🔒 Keamanan & Praktik Terbaik

### Mengapa SVG Diblokir?
Secara default, `UploadHandler` memblokir SVG karena file tersebut berbasis XML yang rentan disisipi tag `<script>` (Stored XSS). Gunakan PNG jika memungkinkan.

### Lokasi Storage
File disimpan di dalam `storage/app/uploads`. Untuk menampilkannya di browser, disarankan menggunakan rute khusus atau melakukan *symlink* dari `storage/app` ke `public` jika hosting Anda mendukungnya.

```bash
# Contoh struktur folder
/storage/app/uploads/users/avatars/up_unique_id.webp
```

---

## 🖼️ Performa vs Laravel
Berbeda dengan Laravel `Storage` yang hanya "memindahkan" file, `UploadHandler` milik kita melakukan **manipulasi pixel** secara langsung untuk memastikan website Anda memiliki beban *load* gambar yang seringan mungkin (WebP Optimizer).
