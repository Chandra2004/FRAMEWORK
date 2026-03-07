# 💻 Tinker (Interactive Debugger)

**Tinker** adalah REPL (Read-Eval-Print Loop) yang powerful untuk berinteraksi dengan aplikasi framework Anda. Fitur ini memungkinkan Anda mengeksekusi kode PHP, memanggil Model, Job, Event, dan Class lainnya secara langsung.

The Framework menyediakan **dua versi** Tinker:

1. **CLI Tinker:** via Terminal (`php artisan tinker`)
2. **Web Tinker:** via Browser (`/_system/tinker`)

---

## 🖥️ CLI Tinker

Gunakan CLI Tinker saat Anda sedang developing di local environment.

### Cara Menjalankan

```bash
php artisan tinker
```

### Fitur Utama

- **Auto-Alias Model:** Tidak perlu mengetik namespace lengkap. Cukup ketik `User::all()` alih-alih `\TheFramework\Models\User::all()`.
- **Auto-Dump Result:** Hasil ekspresi otomatis ditampilkan.
- **History:** Tekan panah atas/bawah untuk melihat command sebelumnya.

### Contoh Penggunaan

```php
// CRUD User
>>> User::all();
>>> User::create(['name' => 'Chandra', 'email' => 'admin@example.com']);
>>> $u = User::find(1);
>>> $u->update(['name' => 'Admin Baru']);

// Global Helpers
>>> config('app.name');
>>> env('DB_HOST');
>>> str_slug('Hello World');

// Database Query
>>> \TheFramework\App\Database::getInstance()->query("SELECT VERSION()")->single();
```

---

## 🌐 Web Tinker

**Web Tinker** adalah solusi revolusioner untuk pengguna **Shared Hosting** yang tidak memiliki akses SSH. Anda bisa debugging aplikasi live langsung dari browser!

### Cara Mengakses

1. Buka URL: `https://yoursite.com/_system/tinker`
2. Atau via Dashboard: Buka `/_system` → Klik menu **11. tinker**

### Tampilan & Fitur

- **Terminal-like UI:** Tampilan hitam ala terminal yang nyaman di mata.
- **Auto-Resize Input:** Input area otomatis membesar sesuai panjang kode.
- **Formatted Output:** Hasil array/object ditampilkan dengan format yang rapi.
- **Safe Execution:** Dilindungi oleh System Key & IP Whitelist (sama seperti fitur \_system lainnya).

### Contoh Web Tinker

Ketik kode berikut lalu tekan **Ctrl + Enter**:

```php
// Cek user terakhir login
$lastUser = \TheFramework\Models\User::orderBy('id', 'desc')->first();
return $lastUser;
```

```php
// Cek koneksi & versi DB
$db = \TheFramework\App\Database::getInstance();
return $db->single("SELECT VERSION()");
```

---

## 🛡️ Keamanan (Security)

Web Tinker adalah fitur yang sangat powerful (bisa membaca/menulis database, file system, dll). Oleh karena itu, fitur ini dilindungi berlapis:

1. **Feature Toggle:** Bisa dimatikan total via `.env` (`ALLOW_WEB_MIGRATION=false`).
2. **IP Whitelist:** Hanya IP yang terdaftar di `.env` (`SYSTEM_ALLOWED_IPS`) yang bisa akses.
3. **Authentication:** Mendukung Basic Auth jika dikonfigurasi.

**⚠️ PENTING:**
Selalu matikan fitur ini di production jika tidak digunakan dengan mengubah:
`ALLOW_WEB_MIGRATION=false` di file `.env`.

---

## 💡 Tips & Trik

1. **Dump vs Return**
   - Gunakan `echo "text"` untuk output teks biasa.
   - Gunakan `return $var` untuk melihat struktur data (array/object) secara rapi.

2. **Error Handling**
   - Jika kode error, Tinker akan menangkap exception dan menampilkan pesan error tanpa merusak aplikasi.

3. **Session Persistence**
   - Web Tinker bersifat stateless (per request). Variabel `$a = 1` di request pertama tidak akan tersimpan di request kedua.
   - CLI Tinker bersifat stateful (selama sesi tidak ditutup).
