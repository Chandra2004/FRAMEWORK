# 🐳 Dockerization

The Framework mendukung penuh Docker untuk mempermudah development lokal maupun deployment ke Cloud (seperti Railway, Render, atau VPS).

---

## 🛠️ File Konfigurasi

Framework telah menyertakan:

- **`Dockerfile`**: Menggunakan image `php:8.3-cli-alpine` yang sangat ringan.
- **`docker-compose.yml`**: (Jika tersedia) Untuk menjalankan PHP dan MySQL sekaligus.

---

## 🚀 Menjalankan dengan Docker

### 1. Build Image

Jalankan perintah ini di root direktori proyek Anda:

```bash
docker build -t my-framework-app .
```

### 2. Jalankan Kontainer

```bash
docker run -p 8080:8080 -e PORT=8080 my-framework-app
```

Aplikasi akan tersedia di `http://localhost:8080`.

---

## ☁️ Deployment ke Railway

`Dockerfile` ini sudah dioptimalkan untuk **Railway.app**:

1. Hubungkan repository GitHub Anda ke Railway.
2. Railway akan otomatis mendeteksi `Dockerfile`.
3. Tambahkan **Environment Variables** (seperti `DB_HOST`, `DB_NAME`, dll) di dashboard Railway.
4. Framework akan memproses `$PORT` secara dinamis sesuai yang diberikan Railway.

---

## ⚙️ Detail Teknis

### Ekstensi PHP

Image Docker ini sudah menyertakan ekstensi wajib:

- `pdo_mysql`, `mbstring`, `exif`, `gd` (dengan support WebP/JPEG), `intl`, `zip`, dan `opcache`.

### Permissions

Docker secara otomatis membuat folder `storage/` dan `private-uploads/` dengan izin akses `777` untuk memastikan framework bisa menulis log dan session di dalam kontainer.

### Composer

Dependensi diinstal otomatis saat build dengan flag `--no-dev` untuk menjaga ukuran image tetap kecil dan performa maksimal di produksi.
