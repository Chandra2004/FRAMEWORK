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

| Nama Secret             | Deskripsi / Contoh Isi                                                                             |
| :---------------------- | :------------------------------------------------------------------------------------------------- |
| **FTP_SERVER**          | Alamat server FTP (contoh: `ftpupload.net`)                                                        |
| **FTP_USERNAME**        | Username hosting akun (contoh: `if0_382xxxxx`) - _Bukan login akun utama!_                         |
| **FTP_PASSWORD**        | Password hosting akun - _Bukan password login akun utama!_                                         |
| **APP_URL**             | URL website Anda (contoh: `http://myapp.rf.gd`)                                                    |
| **APP_KEY**             | **PENTING!** Copy dari `.env` lokal (`base64:xxx...`). Gunakan `php artisan setup` untuk generate. |
| **DB_HOST**             | Host database (contoh: `sql311.infinityfree.com`)                                                  |
| **DB_NAME**             | Nama database (contoh: `if0_382_myapp`)                                                            |
| **DB_USER**             | Username database (biasanya sama dengan FTP Username)                                              |
| **DB_PASS**             | Password database (biasanya sama dengan FTP Password)                                              |
| **DB_PORT**             | `3306`                                                                                             |
| **ALLOW_WEB_MIGRATION** | `true` (untuk menyalakan web tools) atau `false` (untuk mematikan)                                 |

> **⚠️ Catatan:** Jika `APP_KEY` tidak diset di Secrets, aplikasi akan error "Invalid Security Key" karena `.env` di server akan kosong kuncinya.

---

## 3. Maintenance: Paket Gratis (Shared Hosting / InfinityFree)

Karena tidak ada terminal hitam (SSH), kita gunakan **Web Utilities** yang sudah disiapkan di `routes/system.php`.

### Web Utilities (Pengganti Terminal)

Akses URL ini di browser. Pastikan `key=` cocok dengan `APP_KEY` Anda.

Format: `https://domain-anda.com/_system/{perintah}?key={APP_KEY_ANDA}`

| Tugas                | Perintah Artisan (Asli)    | URL Web Utility (Pengganti)                  |
| :------------------- | :------------------------- | :------------------------------------------- |
| **Migrate Database** | `php artisan migrate`      | `/_system/migrate`                           |
| **Isi Data Awal**    | `php artisan db:seed`      | `/_system/seed`                              |
| **Optimize System**  | `php artisan optimize`     | `/_system/optimize` (Clear cache + OpCache)  |
| **Lihat Log Error**  | `tail -f storage/logs/..`  | `/_system/logs` (50 error terakhir)          |
| **Cek Rute**         | `php artisan route:list`   | `/_system/routes`                            |
| **Cek Kesehatan**    | -                          | `/_system/health` (Permission & Disk Space)  |
| **Test Koneksi**     | -                          | `/_system/test-connection` (DB & Mail Check) |
| **Info Server**      | `php -i`                   | `/_system/phpinfo`                           |
| **Symlink Storage**  | `php artisan storage:link` | `/_system/storage-link`                      |
| **Cek Status**       | `php -v`                   | `/_system/status`                            |

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
A: Pastikan Secret `APP_KEY` di GitHub sudah diisi (copy dari `.env` lokal). Dan pastikan `key=base64:....` di URL browser sudah benar (harus di-URL Encode jika ada simbol aneh, tapi biasanya copy-paste browser aman).

**Q: Seeder error "Class not found"?**
A: Pastikan nama file seeder Anda sudah standar: `Seeder_TIMESTAMP_Nama.php`. Jangan ubah-ubah nama class manual. Gunakan `php artisan make:seeder Nama` untuk membuat file yang valid.

**Q: Error "Timezone" saat seeding?**
A: Aplikasi ini butuh `DB_TIMEZONE` di `.env`. Pastikan sudah terisi (misal: ` Asia/Jakarta` atau `+07:00`).

**Q: Halaman putih / 500 Error di InfinityFree?**
A: Cek `display_errors` di hosting biasanya mati. Coba akses `/_system/status` untuk cek error log, atau nyalakan `APP_DEBUG=true` sementara di GitHub Secrets.
