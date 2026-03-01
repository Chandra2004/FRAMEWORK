# 🏗️ Services Layer (v5.0.1 - Paten)

Service adalah layer yang menangani **Logika Bisnis** (_Business Logic_). Tujuan utamanya adalah menjaga agar Controller Anda tetap bersih (_Thin Controller_) dan memindahkan semua proses yang kompleks ke dalam class khusus yang bisa digunakan berulang kali (_Reusability_).

---

## 🌟 Mengapa Menggunakan Service?

Jika Anda menaruh semua logika (upload file, validasi unik, hitung pajak, kirim email) di dalam Controller, maka Controller Anda akan menjadi sangat panjang dan sulit dikelola (_Fat Controller_).

**Manfaat Service Layer:**

1.  **Separation of Concerns**: Controller hanya menangani request/response, Service menangani data.
2.  **Reusability**: Logika yang sama bisa dipanggil dari Controller Web, API, maupun Artisan Command.
3.  **Testability**: Mengetes satu fungsi di Service jauh lebih mudah daripada mengetes routing Controller.

---

## 📂 Lokasi File

Simpan file Service Anda di: `app/Services/`.
Contoh: `app/Services/UserService.php`.

---

## 🏗️ Struktur Dasar Service

Service biasanya berinteraksi dengan **Model** dan **Helper**.

```php
<?php

namespace TheFramework\Services;

use TheFramework\Models\User;
use TheFramework\Helpers\Helper;

class UserService
{
    protected User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function registerUser(array $data)
    {
        // 1. Bisnis Logic: Cek email unik
        if ($this->model->query()->where('email', $data['email'])->first()) {
            return 'email_exists';
        }

        // 2. Prepare data
        $data['uid'] = Helper::uuid();
        $data['password'] = Helper::hash_password($data['password']);

        // 3. Simpan ke Database
        return $this->model->create($data);
    }
}
```

---

## 🚀 Cara Penggunaan di Controller

Berkat sistem **Dependency Injection** pada v5.0.1, Anda bisa langsung memanggil Service di dalam Controller secara otomatis.

### 1. Injeksi Otomatis via Constructor

```php
namespace TheFramework\Http\Controllers;

use TheFramework\Services\UserService;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register()
    {
        $result = $this->userService->registerUser(request()->all());

        if ($result === 'email_exists') {
            return back('error', 'Email sudah terdaftar!');
        }

        return redirect('/login', 'success', 'Registrasi berhasil!');
    }
}
```

---

## 🛡️ Best Practices

1.  **Satu Service, Satu Tanggung Jawab**: Jangan buat `MasterService` yang isinya semua logika. Buatlah service yang spesifik seperti `OrderService`, `PaymentService`, `InventoryService`.
2.  **Kembalikan Data atau Exception**: Usahakan Service mengembalikan data murni (array/object) atau melempar Error jika ada masalah. Biarkan Controller yang memutuskan cara menampilkan error tersebut ke UX.
3.  **Gunakan Helper**: Jangan tulis ulang fungsi hashing atau genterate UUID di Service, gunakan `Helper` yang sudah disediakan framework.

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
