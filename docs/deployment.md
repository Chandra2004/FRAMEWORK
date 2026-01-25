# 🚀 Deployment & Maintenance Guide

Panduan lengkap untuk men-deploy, merawat, dan mengamankan aplikasi **The Framework** di berbagai lingkungan hosting.

---

## 📋 Daftar Isi

1.  [Skenario Hosting](#1-skenario-hosting)
2.  [Panduan GitHub Actions (CI/CD)](#2-panduan-github-actions-cicd)
    - [Setup Secrets (Wajib!)](#setup-repository-secrets)
3.  [Maintenance: Paket Gratis (No SSH)](#3-maintenance-paket-gratis-shared-hosting--infinityfree)
    - [Web Utilities](#web-utilities-pengganti-terminal)
    - [Keamanan (On/Off Switch)](#fitur-keamanan-onoff-switch)
4.  [Maintenance: Paket Premium (VPS/SSH)](#4-maintenance-paket-premium-vps--cloud-server)
5.  [Troubleshooting Umum](#5-troubleshooting)

---

## 1. Skenario Hosting

| Fitur               | Paket Premium (VPS/Cloud)     | Paket Hemat/Gratis (Shared Hosting)   |
| :------------------ | :---------------------------- | :------------------------------------ |
| **Contoh Provider** | AWS, DigitalOcean, Biznet Gio | InfinityFree, Niagahoster, 000Webhost |
| **Akses Terminal**  | ✅ Full SSH Access            | ❌ Tidak Ada (Hanya FTP/Web)          |
| **Metode Deploy**   | `git pull` manual via SSH     | Otomatis via **GitHub Actions** (FTP) |
| **Migrasi DB**      | `php artisan migrate`         | URL: `/_system/migrate`               |
| **Seeding DB**      | `php artisan db:seed`         | URL: `/_system/seed`                  |

---

## 2. Panduan GitHub Actions (CI/CD)

Framework ini menggunakan GitHub Actions (`.github/workflows/deploy.yml`) untuk mengirim kode secara otomatis ke hosting gratisan via FTP. Agar berjalan lancar, Anda **WAJIB** mengatur Secrets.

### Setup Repository Secrets

Masuk ke **GitHub Repo > Settings > Secrets and variables > Actions**. Tambahkan key berikut:

| Nama Secret                              | Deskripsi / Contoh Isi                                                                                                                                                                      |
| :--------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **FTP_SERVER**                           | Alamat server FTP (contoh: `ftpupload.net`)                                                                                                                                                 |
| **FTP_USERNAME**                         | Username hosting akun (contoh: `if0_382xxxxx`) - _Bukan login akun utama!_                                                                                                                  |
| **FTP_PASSWORD**                         | Password hosting akun - _Bukan password login akun utama!_                                                                                                                                  |
| **APP_URL**                              | URL website Anda (contoh: `http://myapp.rf.gd`)                                                                                                                                             |
| **APP_KEY**                              | **PENTING!** Copy dari `.env` lokal (`base64:xxx...`). Gunakan `php artisan setup` untuk generate.                                                                                          |
| **DB_HOST**                              | Host database (contoh: `sql311.infinityfree.com`)                                                                                                                                           |
| **DB_NAME**                              | Nama database (contoh: `if0_382_myapp`)                                                                                                                                                     |
| **DB_USER**                              | Username database (biasanya sama dengan FTP Username)                                                                                                                                       |
| **DB_PASS**                              | Password database (biasanya sama dengan FTP Password)                                                                                                                                       |
| **DB_PORT**                              | `3306`                                                                                                                                                                                      |
| **ALLOW_WEB_MIGRATION**                  | `true` (untuk menyalakan web tools) atau `false` (untuk mematikan)                                                                                                                          |
| **SYSTEM_ALLOWED_IPS** ⭐ **NEW v5.0.0** | **IP Whitelist** - Comma-separated list IP yang boleh akses `/_system/*`<br>Contoh: `182.8.66.200,103.x.x.x` atau `*` (semua IP - not recommended!)<br>Lihat IP Anda: https://api.ipify.org |
| **SYSTEM_AUTH_USER** ⭐ **NEW v5.0.0**   | **Basic Auth Username** (Optional tapi recommended)<br>Contoh: `admin` - Browser akan popup login                                                                                           |
| **SYSTEM_AUTH_PASS** ⭐ **NEW v5.0.0**   | **Basic Auth Password** (Optional tapi recommended)<br>Contoh: `MyStr0ng!P@ssw0rd#2026` - Minimal 12 karakter, kombinasi huruf/angka/simbol                                                 |

### 🛡️ Web Command Center Security (v5.0.0) - 3-Layer Protection

Framework v5.0.0 menggunakan **3-layer security** untuk melindungi Web Command Center (`/_system/*`):

```
REQUEST → Layer 1 → Layer 2 → Layer 3 → ✅ ALLOWED
          Toggle    IP Check  Auth
```

**Penjelasan Setiap Layer:**

| Layer                         | Config                                   | Fungsi                         | Contoh                     |
| ----------------------------- | ---------------------------------------- | ------------------------------ | -------------------------- |
| **1. Feature Toggle**         | `ALLOW_WEB_MIGRATION`                    | Enable/disable fitur           | `true` = ON, `false` = OFF |
| **2. IP Whitelist**           | `SYSTEM_ALLOWED_IPS`                     | Hanya IP terdaftar boleh akses | `182.8.66.200,103.x.x.x`   |
| **3. Basic Auth** (Required!) | `SYSTEM_AUTH_USER`<br>`SYSTEM_AUTH_PASS` | Username + Password login      | Browser popup kredensial   |

**Contoh Real-World:**

1. **Development (Lokal):**

   ```env
   ALLOW_WEB_MIGRATION=true
   SYSTEM_ALLOWED_IPS=127.0.0.1,*
   SYSTEM_AUTH_USER=admin
   SYSTEM_AUTH_PASS=$2y$12$abc...  # Use 'php artisan setup'
   ```

2. **Production (Aman):**

   ```env
   ALLOW_WEB_MIGRATION=false  # Disable when not needed!
   SYSTEM_ALLOWED_IPS=182.8.66.200  # Your IP only
   SYSTEM_AUTH_USER=chandra
   SYSTEM_AUTH_PASS=$2y$12$8Z0Snt...  # Bcrypt hash
   ```

3. **Maintenance Mode:**
   ```env
   ALLOW_WEB_MIGRATION=true  # Enable sementara
   SYSTEM_ALLOWED_IPS=182.8.66.200
   SYSTEM_AUTH_USER=chandra
   SYSTEM_AUTH_PASS=$2y$12$8Z0Snt...
   ```

**Penjelasan SYSTEM_ALLOWED_IPS:**

- Format: `IP1,IP2,IP3` (comma-separated)
- `182.8.66.200` = IP publik Anda (dari ISP), bukan IP server
- Cek IP Anda: https://api.ipify.org
- `*` = Wildcard (allow ALL IPs) - **DANGER!** Gunakan hanya development
- Contoh: `127.0.0.1,182.8.66.200,*` = localhost + IP Anda + semua IP lain

**Penjelasan Basic Auth:**

- `SYSTEM_AUTH_USER` = Username untuk login (contoh: `admin`)
- `SYSTEM_AUTH_PASS` = Bcrypt hashed password (**REQUIRED!**)
- **WAJIB setup via `php artisan setup`** untuk generate hash
- Saat akses `/_system/migrate`, browser popup login
- Masukkan username/password yang benar baru bisa akses

---

## 3. Maintenance: Paket Gratis (Shared Hosting / InfinityFree)

Karena tidak ada terminal hitam (SSH), kita gunakan **Web Utilities** yang sudah disiapkan di `routes/system.php`.

### Web Command Center (GUI Dashboard) ⭐ **NEW v5.1.0**

Kini Anda memiliki antarmuka grafis (GUI) berbasis terminal (CLI Style) untuk menjalankan semua perintah sistem tanpa perlu mengetik URL satu per satu.

- **URL Dashboard:** `/_system`
- **Lokasi View:** `app/App/Internal/Views/` (Terpisah dari `resources/views` agar folder utama tetap bersih).

### Web Utilities (Daftar Perintah)

Akses URL ini di browser. Browser akan meminta Basic Auth (Username & Password) sesuai yang diatur di `.env`.

| Tugas                | Perintah Artisan (Asli)    | URL Web Utility (Dashboard) | Deskripsi                                     |
| :------------------- | :------------------------- | :-------------------------- | :-------------------------------------------- |
| **Main Dashboard**   | -                          | `/_system`                  | **(NEW)** Panel kontrol utama sistem          |
| **Migrate Database** | `php artisan migrate`      | `/_system/migrate`          | Menjalankan file migrasi database             |
| **Isi Data Awal**    | `php artisan db:seed`      | `/_system/seed`             | Mengisi data awal/dummy ke database           |
| **Diagnosis Sistem** | -                          | `/_system/diagnose`         | Cek Session, CSRF, & DB details               |
| **System Check**     | -                          | `/_system/check-files`      | Verifikasi keberadaan file kritis & scan View |
| **Optimize System**  | `php artisan optimize`     | `/_system/optimize`         | Clear cache views + Opcache reset             |
| **Clear Cache**      | `php artisan cache:clear`  | `/_system/clear-cache`      | Hapus manual file cache & logs                |
| **Lihat Log Error**  | `tail -f storage/logs/..`  | `/_system/logs`             | Menampilkan 50 baris log terakhir             |
| **Cek Rute**         | `php artisan route:list`   | `/_system/routes`           | Melihat daftar semua rute terdaftar           |
| **Cek Kesehatan**    | -                          | `/_system/health`           | Cek status DB & Storage Writable (JSON)       |
| **Test Koneksi**     | -                          | `/_system/test-connection`  | Cek detail koneksi & versi MySQL              |
| **Info PHP**         | `php -i`                   | `/_system/phpinfo`          | Menampilkan fungsi `phpinfo()` standar        |
| **Symlink Storage**  | `php artisan storage:link` | `/_system/storage-link`     | Membuat symlink storage (jika disupport)      |
| **Cek Status**       | `php -v`                   | `/_system/status`           | Cek versi PHP & Ekstensi Wajib terinstall     |
| **Cek IP Saya**      | -                          | `/_system/my-ip`            | Mengetahui IP Anda untuk whitelist .env       |

### Fitur Keamanan (On/Off Switch)

Meninggalkan fitur ini dalam keadaan menyala selamanya adalah berbahaya!

1.  **Saat Maintenance:** Set Secret `ALLOW_WEB_MIGRATION` = `true`. Re-run Deploy.
2.  **Lakukan Tugas:** Buka URL migrate/seed di browser.
3.  **Selesai:** Set Secret `ALLOW_WEB_MIGRATION` = `false`. Re-run Deploy.
    - _Ini akan mematikan total akses ke `/_system/`._

---

## 4. Maintenance: Paket Premium (VPS / Cloud Server)

Jika Anda punya SSH, lupakan cara di atas. Gunakan cara profesional via terminal terminal:

1.  **Masuk Server:** `ssh user@ip-address`
2.  **Masuk Folder:** `cd /var/www/html`
3.  **Update Kode:** `git pull origin main`
4.  **Install Lib:** `composer install --no-dev`
5.  **Migrasi:** `php artisan migrate`
6.  **Seeding:** `php artisan db:seed` (Hanya awal)
7.  **Clear Cache:** `php artisan view:clear`

---

## 5. Troubleshooting

**Q: "Invalid Security Key" saat akses Web Utility?**
A: Fitur ini sudah dihapus di v5.0.0 final release. Anda sekarang hanya butuh Basic Auth (Username & Password) dan IP Whitelist. Pastikan `SYSTEM_AUTH_USER` dan `SYSTEM_AUTH_PASS` (hash) sudah diatur dengan benar di `.env`.

**Q: Seeder error "Class not found"?**
A: Pastikan nama file seeder Anda sudah standar: `Seeder_TIMESTAMP_Nama.php`. Jangan ubah-ubah nama class manual. Gunakan `php artisan make:seeder Nama` untuk membuat file yang valid.

**Q: Error "Timezone" saat seeding?**
A: Aplikasi ini butuh `DB_TIMEZONE` di `.env`. Pastikan sudah terisi (misal: ` Asia/Jakarta` atau `+07:00`).

**Q: Halaman putih / 500 Error di InfinityFree?**
A: Cek `display_errors` di hosting biasanya mati. Coba akses `/_system/status` untuk cek error log, atau nyalakan `APP_DEBUG=true` sementara di GitHub Secrets.
