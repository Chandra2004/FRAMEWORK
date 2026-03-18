# 🌐 Web Command Center (WCC)

**Web Command Center** adalah fitur _flagship_ dari The Framework yang memungkinkan pengelolaan server, database, dan maintenance aplikasi **langsung dari browser**. Dirancang khusus untuk lingkungan yang tidak memiliki akses SSH (seperti Shared Hosting gratisan).

---

## 🏗️ Arsitektur & Keamanan

WCC beroperasi melalui file `routes/system.php` dan dilindungi oleh **3-Layer Premium Security** untuk memastikan akses hanya diberikan kepada pihak berwenang.

### 1. Layer 1: Master Switch

Fitur ini mati secara default dan harus diaktifkan secara eksplisit di file `.env`.

```bash
ALLOW_WEB_MIGRATION=true
```

### 2. Layer 2: IP Whitelisting (Dynamic)

Hanya IP yang terdaftar yang bisa melihat dashboard WCC.

```bash
SYSTEM_ALLOWED_IPS=127.0.0.1,182.1.2.3
# Gunakan '*' untuk mengizinkan semua (SANGAT TIDAK DISARANKAN di Production)
```

> [!TIP]
> Alamat IP saat ini dapat diperiksa melalui endpoint `/_system/my-ip`.

### 3. Layer 3: Basic Authentication (Hashed)

Sistem akan meminta username dan password sebelum dashboard ditampilkan.

```bash
SYSTEM_AUTH_USER=admin
SYSTEM_AUTH_PASS=$2y$12$.... # Gunakan bcrypt hashed password
```

---

## 🚀 Dashboard & Endpoints

Akses dashboard utama melalui: `https://domain.com/_system`

### 🗄️ Database Management

| Endpoint                    | Perintah    | Deskripsi                                               |
| --------------------------- | ----------- | ------------------------------------------------------- |
| `/_system/migrate`          | `migrate`   | Jalankan migrasi database yang pending.                 |
| `/_system/migrate/rollback` | `rollback`  | **WARNING**: Batalkan batch migrasi terakhir (with alert). |
| `/_system/migrate/fresh`    | `fresh`     | **DANGER**: Drop semua tabel dan migrasi ulang.         |
| `/_system/backup`           | `backup`    | **NEW**: Backup Database (SQL) & Aplikasi (ZIP).        |
| `/_system/seed`             | `db:seed`   | Isi database dengan data dummy dari seeder.             |
| `/_system/schema`           | `db:schema` | **Premium Inspector**: Lihat daftar tabel & baris data. |
| `/_system/test-connection`  | `db:test`   | Uji latensi dan status koneksi database.                |

### 🧹 Maintenance & Optimization

| Endpoint                 | Deskripsi                                                   |
| ------------------------ | ----------------------------------------------------------- |
| `/_system/optimize`      | Bersihkan kompilasi view Blade dan reset OpCache.           |
| `/_system/clear-cache`   | Hapus file cache manual dan file log aplikasi.              |
| `/_system/storage-link`  | Buat _symbolic link_ untuk folder storage di folder public. |
| `/_system/asset-publish` | Salin ulang assets dari `resources` ke `public/assets`.     |

### 📊 Monitoring & Diagnostics

| Endpoint            | Deskripsi                                                         |
| ------------------- | ----------------------------------------------------------------- |
| `/_system/status`   | Cek versi PHP dan status ekstensi yang dibutuhkan.                |
| `/_system/diagnose` | Diagnosa mendalam Session, CSRF, dan izin tulis folder.           |
| `/_system/logs`     | Lihat 100 baris terakhir dari log aplikasi secara real-time.      |
| `/_system/routes`   | Daftar seluruh rute yang terdaftar di aplikasi.                   |
| `/_system/health`   | Status kesehatan sistem dalam format JSON (untuk uptime monitor). |

---

## 📦 Backup & Recovery Management (v5.0.2)

Fitur **Backup Management** memungkinkan Anda mengamankan seluruh aset aplikasi dalam hitungan detik.

**URL:** `/_system/backup`

### 🛡️ Apa yang Masuk dalam Backup?
Sistem akan memaketkan aplikasi ke dalam file `.zip` dengan cerdas:
- **Core Files:** `index.php`, `artisan`, `composer.json/lock`.
- **Configs:** `.env`, `.htaccess`, `.gitignore`.
- **Environment:** `/.idx/`, `/.vscode/` (Untuk konsistensi IDE).
- **Data User:** `/private-uploads/` dan `storage/app/`.
- **Database:** Pilihan `.sql` dump murni atau digabung ke dalam ZIP (Full Backup).

> [!IMPORTANT]
> **Excluded Files**: Folder `vendor/`, `node_modules/`, serta file temporer di `storage/logs/`, `storage/framework/cache/`, dan `storage/session/` otomatis diabaikan untuk mengoptimalkan ukuran file.

### 🔄 Database Export (SQL)
Sistem akan mencoba menggunakan `mysqldump` jika tersedia di server untuk kecepatan maksimal. Jika tidak, framework memiliki **Native SQL Generator** sebagai fallback yang akan menghasilkan file SQL lengkap melalui PDO.

---

## ⚡ Feature Spotlight: Web Tinker (REPL)

**Web Tinker** adalah salah satu fitur paling powerful di WCC. Kode PHP dapat dijalankan secara interaktif langsung di server tanpa perlu melakukan deployment berulang kali.

**URL:** `/_system/tinker`

**Kemampuan:**

- Test query database menggunakan Model (Auto-aliased).
- Jalankan business logic secara on-the-fly.
- Cek hasil kalkulasi fungsi helper.
- Formatted output yang rapi menggunakan `print_r` logic.

---

## 🛠️ Panduan Implementasi Production

Saat melakukan deployment ke Shared Hosting (seperti Hostinger, Niagahoster, atau InfinityFree):

1.  **Upload** aplikasi seperti biasa.
2.  Akses `/_system/my-ip` (endpoint ini tidak memerlukan whitelist) untuk mendapatkan IP publik Anda.
3.  Konfigurasi `.env` dengan IP tersebut dan aktifkan `ALLOW_WEB_MIGRATION`.
4.  Buka `/_system/migrate` untuk inisialisasi database.
5.  **Penting**: Setelah selesai, kembalikan `ALLOW_WEB_MIGRATION=false` untuk keamanan maksimal.

---

## ⚠️ Keamanan Lanjutan (Webserver Level)

Untuk perlindungan ekstra, akses ke folder `/_system` dapat diblokir di level web server menggunakan file `.htaccess`:

```apache
<LocationMatch "^/_system">
    Require all denied
</LocationMatch>
```

---

## 🔗 Dokumentasi Terkait

- [Security Guide](security.md)
- [Database Migrations](migrations.md)
- [Deployment Guide](deployment.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
