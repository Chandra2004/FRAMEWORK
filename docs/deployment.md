# 🚀 Deployment & Maintenance Guide

Panduan ini mencakup cara men-deploy aplikasi **The Framework**, mengelola database, dan menggunakan fitur keamanan pada berbagai jenis hosting.

---

## 📋 Daftar Isi

1.  [Skenario Hosting](#1-skenario-hosting)
2.  [Paket Premium (VPS / Cloud Server)](#2-paket-premium-vps--cloud-server-ssh-access)
3.  [Paket Gratis / Shared Hosting (InfinityFree, cPanel)](#3-paket-gratis--shared-hosting-tanpa-ssh)
4.  [Web Utilities & Migration Tools](#4-web-utilities--migration-tools)
    - [Cara Menggunakan](#cara-menggunakan)
    - [Fitur Keamanan (ON/OFF Switch)](#fitur-keamanan-onoff-switch-penting)
5.  [Database Seeding](#5-database-seeding)

---

## 1. Skenario Hosting

Pilih panduan berdasarkan jenis server yang Anda gunakan:

| Fitur               | Paket Premium (VPS/Cloud)        | Paket Hemat/Gratis (Shared Hosting)                |
| :------------------ | :------------------------------- | :------------------------------------------------- |
| **Contoh Provider** | AWS, DigitalOcean, Biznet Gio    | InfinityFree, Niagahoster (Paket Bayi), 000Webhost |
| **Akses SSH**       | ✅ Ada                           | ❌ Tidak Ada                                       |
| **Instalasi**       | via Terminal (`git clone`)       | via FTP / CI/CD                                    |
| **Migration/Seed**  | via Command Line (`php artisan`) | via **Web Utilities** atau Import SQL Manual       |

---

## 2. Paket Premium (VPS / Cloud Server) - SSH Access

Jika Anda memiliki akses terminal (SSH), ini adalah cara standar dan paling disarankan.

1.  **Connect ke Server**:
    ```bash
    ssh user@ip-address
    ```
2.  **Clone Repository**:
    ```bash
    git clone https://github.com/username/repo-name.git .
    ```
3.  **Install Dependencies**:
    ```bash
    composer install --optimize-autoloader --no-dev
    ```
4.  **Setup Environment**:
    ```bash
    cp .env.example .env
    nano .env # Edit data database dan APP_URL
    ```
5.  **Migrate & Seed Database**:
    ```bash
    # Jalankan migrasi dan seeder langsung dari terminal
    php artisan migrate
    php artisan db:seed
    ```

---

## 3. Paket Gratis / Shared Hosting (Tanpa SSH)

Pada hosting seperti **InfinityFree**, Anda tidak bisa menjalankan perintah `php artisan`. Kita mengatasinya dengan **CI/CD Pipeline** dan **Web Utilities**.

### A. Setup CI/CD (Otomatisasi Deployment)

Aplikasi ini sudah dilengkapi `.github/workflows/deploy.yml` yang akan otomatis mengupload file via FTP setiap kali Anda push ke GitHub.

1.  Buka Repository GitHub Anda > **Settings** > **Secrets and variables** > **Actions**.
2.  Tambahkan Repository Secrets berikut:
    - `FTP_SERVER`: (Contoh: `ftpupload.net`)
    - `FTP_USERNAME`: (Username FTP/Cpanel Anda)
    - `FTP_PASSWORD`: (Password FTP Anda)
    - `APP_URL`: (Contoh: `http://namasitus.infinityfreeapp.com`)
    - `DB_HOST`: (Contoh: `sql123.infinityfree.com`)
    - `DB_NAME`, `DB_USER`, `DB_PASS`: (Sesuai detail database di panel hosting)
    - `ALLOW_WEB_MIGRATION`: `true` (Lihat bagian Keamanan di bawah)

### B. Deployment Pertama

Saat Anda melakukan `git push`, GitHub Actions akan:

1.  Menginstall library PHP (Composer).
2.  Membuat file `.env` otomatis di server menggunakan Secrets di atas.
3.  Mengupload semua file ke folder `htdocs`.

---

## 4. Web Utilities & Migration Tools

Karena tidak ada SSH di Shared Hosting, kami menyediakan **Web-Based Tools** untuk melakukan maintenance dasar seperti migrasi database dan membersihkan cache.

### Prasyarat

Untuk keamanan, akses ke tools ini dilindungi dua lapis:

1.  **APP_KEY**: URL harus menyertakan `key` yang sesuai dengan `APP_KEY` di `.env`.
2.  **Feature Toggle**: Fitur harus diaktifkan via variabel `ALLOW_WEB_MIGRATION`.

### Cara Menggunakan

Format URL:
`https://website-anda.com/_system/{command}?key={APP_KEY_ANDA}`

| Perintah         | URL Endpoint            | Fungsi                                                                            |
| :--------------- | :---------------------- | :-------------------------------------------------------------------------------- |
| **Migrate DB**   | `/_system/migrate`      | Menjalankan file migrasi database (`database/migrations/*.php`).                  |
| **Clear Cache**  | `/_system/clear-cache`  | Menghapus view cache dan log (solusi jika tampilan tidak berubah setelah update). |
| **Storage Link** | `/_system/storage-link` | Membuat symlink agar file upload bisa diakses publik (server tertentu).           |
| **Status**       | `/_system/status`       | Cek versi PHP dan ekstensi yang aktif.                                            |

**Contoh Penggunaan:**
Jika `APP_KEY` Anda adalah `base64:XYZ123...`, maka buka di browser:
`https://website-anda.com/_system/migrate?key=base64:XYZ123...`

---

### 🛡️ Fitur Keamanan (ON/OFF Switch) [PENTING]

Meninggalkan akses database via web dalam keadaan terbuka adalah berbahaya. Gunakan fitur **ON/OFF Switch** via GitHub Secrets untuk mengamankannya.

#### Langkah 1: MODE ON (Maintenance Mode)

Saat Anda baru saja deploy fitur baru dan butuh migrasi database:

1.  Buka **GitHub Settings > Secrets > Actions**.
2.  Ubah/Buat secret `ALLOW_WEB_MIGRATION` dengan nilai `true`.
3.  Jalankan ulang deployment (Re-run jobs di tab Actions atau push perubahan kecil).
    - _Efek: Tool bisa diakses via browser._

#### Langkah 2: Lakukan Tugas

Buka browser, akses URL `/_system/migrate` untuk mengupdate struktur database Anda.

#### Langkah 3: MODE OFF (Production/Safe Mode)

Setelah selesai maintenance:

1.  Buka **GitHub Settings > Secrets > Actions**.
2.  Ubah `ALLOW_WEB_MIGRATION` menjadi `false` (atau hapus secret-nya).
3.  Jalankan ulang deployment.
    - _Efek: Endpoint `/\_system/_` akan mati total. Hacker tidak bisa menyentuhnya meskipun tahu kuncinya.\*

---

## 5. Database Seeding

**Seeding** (mengisi data awal/dummy) sedikit berbeda tergantung paket hosting Anda.

### A. Di Paket Premium (VPS)

Cukup jalankan perintah:

```bash
php artisan db:seed
```

### B. Di Paket Gratis (InfinityFree/Shared)

Karena alasan keamanan dan batas waktu eksekusi (timeout) pada hosting gratis, **Web Seeder tidak disediakan secara default**. Seeding data dalam jumlah besar via browser seringkali gagal di tengah jalan (Time Limit).

**Cara Alternatif Terbaik:**

1.  **Seed Lokal**:
    Jalankan seeding di komputer lokal Anda:
    ```bash
    php artisan migrate:fresh --seed
    ```
2.  **Export Data**:
    Buka Database Manager lokal (phpMyAdmin / DBeaver / HeidSQL). Export tabel yang datanya ingin Anda upload (misal: tabel `users`, `roles`, atau data referensi lainnya) ke format **SQL** (Insert statement).
3.  **Import ke Hosting**:
    Buka **phpMyAdmin** di InfinityFree (atau cPanel hosting Anda). Pilih database, lalu menu **Import**, dan upload file SQL yang tadi diexport.

> **Tips:** Cara ini jauh lebih aman dan akurat daripada mencoba menjalankan script seeder berat via browser di hosting gratisan.
