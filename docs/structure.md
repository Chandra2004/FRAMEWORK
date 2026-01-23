# Struktur Direktori

Berikut adalah penjelasan fungsi dari setiap folder dalam TheFramework:

```bash
root/
├── app/                    # LOGIKA INTI APLIKASI
│   ├── App/                # Core System (Jantung Framework)
│   │   ├── Config.php      # Setup Environment
│   │   ├── Container.php   # Dependency Injection
│   │   ├── Database.php    # Koneksi DB
│   │   ├── Lang.php        # Localization Logic 🌍
│   │   └── Router.php      # Routing Engine
│   ├── Console/            # Command Line Interface (Artisan)
│   ├── Helpers/            # Helper Functions Global
│   ├── Http/               # Web Logic
│   │   └── Controllers/    # Pengendali Request
│   ├── Middleware/         # Filter HTTP (Auth, CSRF, dll)
│   └── Models/             # Representasi Data (ORM)
│
├── bootstrap/              # Script inisialisasi aplikasi (Start up)
├── config/                 # File Konfigurasi Tambahan
├── public/                 # Entry point web server (index.php, CSS, JS, Images)
├── resources/              # FRONTEND ASSETS
│   ├── lang/               # File Bahasa (kunci tranlasi)
│   └── views/              # Template HTML (Blade)
├── routes/                 # Definisi URL (web.php)
├── storage/                # Penyimpanan lokal (Cache, Logs, Uploads)
├── vendor/                 # Library pihak ketiga (Composer)
└── .env                    # Konfigurasi Environment (Database credentials, dll)
```
