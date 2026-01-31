# 📂 Struktur Folder Aplikasi

The Framework dirancang dengan struktur yang bersih, modular, dan mengikuti standar industri (mirip Laravel). Memahami struktur ini adalah kunci penguasaan framework.

---

## 📋 Daftar Isi

1.  [Peta Direktori Utama](#peta-direktori-utama)
2.  [Bedah Folder `app/`](#bedah-folder-app)
3.  [Bedah Folder `config/` & `.env`](#bedah-folder-config--env)
4.  [Bedah Folder `public/`](#bedah-folder-public)
5.  [Bedah Folder `resources/`](#bedah-folder-resources)
6.  [Bedah Folder `routes/`](#bedah-folder-routes)
7.  [Bedah Folder `storage/`](#bedah-folder-storage)

---

## Peta Direktori Utama

```
Root/
├── app/            # Otak aplikasi (Controller, Model, Middleware)
├── database/       # Migrasi & Seeder Database
├── public/         # Pintu masuk (index.php) & Aset (CSS/JS)
├── resources/      # Tampilan (Views)
├── routes/         # Definisi URL routing
├── storage/        # File Server (Log, Cache, Upload)
├── tests/          # Unit Testing
├── vendor/         # Composer Packages (Jangan diubah!)
└── .env            # Konfigurasi Rahasia
```

---

## Bedah Folder `app/`

Ini adalah tempat kerja utama Anda.

- **`Console/`**: Tempat menyimpan custom command artisan.
- **`Controllers/`**: Logika yang menerima Request dan mengembalikan Response.
  - _Contoh: `UserController.php` menangani login/register._
- **`Models/`**: Representasi tabel database.
  - _Contoh: `User.php` merepresentasikan tabel `users`._
- **`Middleware/`**: "Satpam" yang mencegat request sebelum sampai controller.
  - _Contoh: `AuthMiddleware` menendang user yang belum login._
- **`Helpers/`**: Kumpulan fungsi statis global.
- **`Services/`**: (Opsional) Tempat logika bisnis yang rumit agar Controller tetap bersih.
- **`App/Internal/Views/`**: **Internal framework views** (tidak untuk developer):
  - **`errors/`**: Halaman error sistem (403, 404, 500, maintenance, payment, dll).
  - **`_system/`**: Dashboard dan UI untuk Web Command Center.
  - **`layout.blade.php`**: Layout master untuk internal views.

---

## Bedah Folder `config/` & `.env`

- **`.env`**: File ini berisi password database, API Key, dan setting sensitif. **JANGAN UPLOAD FILE INI KE GITHUB**.
- **`.env.example`**: Template konfigurasi. Developer lain meng-copy file ini menjadi `.env` mereka.

---

## Bedah Folder `public/`

Satu-satunya folder yang bisa diakses langsung oleh browser User.

- **`index.php`**: File keramat. Titik awal (Entry Point) framework. Semua request diarahkan ke sini.
- **`assets/`**: Simpan gambar, css, js, font di sini.
- **`storage/`**: Shortcut (Symlink) ke folder `storage/app/public` agar file upload bisa diakses.

---

## Bedah Folder `resources/`

Tempat kode Frontend (**user-facing views**).

- **`views/`**: File template HTML (Blade-like) untuk aplikasi Anda.
  - `layouts/`: Master template (Header/Footer).
  - `interface/`: Komponen UI dan halaman aplikasi.

---

## Bedah Folder `routes/`

- **`web.php`**: Rute untuk browser. Menggunakan Session & CSRF Protection.
- **`api.php`**: Rute untuk API (Mobile/React). Stateless (Token Based), rate limited.
- **`system.php`**: Rute spesial untuk **Web Command Center** (Maintenance Tools).

---

## Bedah Folder `storage/`

"Gudang" framework. Folder ini harus Writable (Permission 775/777).

- **`logs/`**: File `framework.log` berisi catatan error sistem. Cek ini jika aplikasi blank!
- **`framework/`**: Cache file system (Views, Sessions).
- **`app/`**: File yang diupload user.
  - `private/`: Dokumen rahasia (KTP/Invoice).
  - `public/`: Foto profil (Bisa diakses via `public/storage`).
