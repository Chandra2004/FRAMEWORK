# 🛠️ Service Providers (v5.0.1 - Paten)

Service Provider adalah "pusat komando" untuk melakukan inisialisasi aplikasi Anda. Di sinilah tempat terbaik untuk mendaftarkan service ke dalam Container atau menjalankan konfigurasi awal sebelum router dijalankan.

---

## 📂 Lokasi File

File utama berada di: `app/Providers/AppServiceProvider.php`.

---

## 🏗️ Struktur Dasar

Service Provider memiliki dua method utama:

### 1. `register()`

Digunakan **hanya** untuk mengikat (_bind_) sesuatu ke dalam **Service Container**. Anda tidak boleh menjalankan logika lain atau menggunakan service lain di sini karena service tersebut mungkin belum terdaftar.

```php
public function register(): void
{
    $container = \TheFramework\App\Core\Container::getInstance();

    // Mendaftarkan Singleton Service
    $container->singleton(MyCustomService::class, function() {
        return new MyCustomService(config('api.key'));
    });
}
```

### 2. `boot()`

Method ini dipanggil **setelah** semua service terdaftar (setelah `register()` semua provider selesai). Di sini Anda bebas menggunakan service yang sudah terdaftar atau menjalankan logika inisialisasi awal.

```php
public function boot(): void
{
    // Contoh: Set default timezone atau locale secara global
    date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

    // Contoh: Inisialisasi library pihak ketiga
    // ThirdParty::init(config('thirdparty.id'));
}
```

---

## 🚀 Cara Penggunaan

### Mendaftarkan Service (Dependency Injection)

Jika Anda memiliki class `PaymentService` yang ingin Anda gunakan di Controller, Anda bisa mendaftarkannya di `register()`:

```php
public function register(): void
{
    $container = \TheFramework\App\Core\Container::getInstance();

    $container->singleton(\TheFramework\Services\PaymentService::class, function () {
        return new \TheFramework\Services\PaymentService();
    });
}
```

Sekarang, Anda bisa memanggilnya di Controller via Container atau Type-hinting:

```php
// Di Controller
public function checkout(\TheFramework\Services\PaymentService $payment)
{
    $payment->process();
}
```

---

## 🛡️ Best Practices

1.  **Keep it Lean**: Jangan masukkan logika bisnis yang berat di Provider. Gunakan ia hanya sebagai "perekat" (_glue_) antar komponen.
2.  **Singleton vs Instance**: Gunakan `$container->singleton()` jika Anda hanya ingin satu instance saja selama siklus request. Gunakan `$container->bind()` (jika didukung) untuk instance baru setiap kali dipanggil.
3.  **Group logically**: Jika aplikasi Anda makin besar, Anda bisa membuat provider baru (misal: `AuthServiceProvider`, `EventServiceProvider`) dan mendaftarkannya di `bootstrap/app.php`.

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
