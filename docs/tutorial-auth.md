# 🔐 Authentication System — v5.0.1 Premium

Tutorial ini akan memandu Anda membangun sistem autentikasi (Login & Registrasi) yang aman menggunakan fitur bawaan **The Framework v5.0.1**.

---

## 🏗️ 1. Persiapan Model User

Pastikan model `User` sudah dikonfigurasi untuk menangani hashing password secara otomatis atau melalui Service.

File: `app/Models/User.php`

```php
namespace TheFramework\Models;

use TheFramework\App\Database\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['uid', 'name', 'email', 'password', 'role'];
    protected $hidden = ['password']; // Jangan sertakan password saat dikonversi ke JSON/Array
}
```

---

## 🛠️ 2. Registrasi (Service Layer)

Gunakan `Helper::hash_password()` yang menggunakan algoritma BCRYPT (Cost 12) untuk keamanan maksimal.

File: `app/Services/AuthService.php`

```php
namespace TheFramework\Services;

use TheFramework\Models\User;
use TheFramework\Helpers\Helper;

class AuthService
{
    public function register(array $data)
    {
        $data['uid'] = Helper::uuid();
        $data['password'] = Helper::hash_password($data['password']);

        return User::create($data);
    }
}
```

---

## 🔑 3. Login & Session Management

Proses pengecekan kredensial dilakukan di Controller atau Service dengan membandingkan hash.

File: `app/Http/Controllers/AuthController.php`

```php
namespace TheFramework\Http\Controllers;

use TheFramework\App\Http\Request;
use TheFramework\Models\User;
use TheFramework\Helpers\Helper;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', '=', $email)->first();

        if ($user && password_verify($password, $user->password)) {
            // Set session aman
            Helper::session_write('user_id', $user->uid);
            Helper::session_write('user_name', $user->name);

            return redirect('/dashboard', 'success', 'Selamat datang kembali, ' . $user->name);
        }

        return redirect('/login', 'error', 'Email atau password salah.');
    }

    public function logout()
    {
        Helper::session_destroy_all();
        return redirect('/login', 'success', 'Anda telah berhasil keluar.');
    }
}
```

---

## 🔒 4. Melindungi Rute (Middleware)

Gunakan middleware untuk memastikan hanya user yang sudah login yang dapat mengakses halaman tertentu.

```php
Router::group(
    [
        'prefix' => '/admin',
        'middleware' => [AuthMiddleware::class]
    ],
    function() {
        Router::get('/dashboard', AdminController::class, 'index');
    }
);
```

---

## ✅ Keamanan Tambahan

1. **Session Fixation**: Framework secara otomatis meregenerasi ID session setiap kali login untuk mencegah pembajakan session.
2. **CSRF Protection**: Selalu gunakan `@csrf` di setiap form login/registrasi Anda di Blade.
3. **Password Hashing**: Jangan pernah menyimpan password dalam bentuk teks biasa (_plain-text_). Gunakan `Helper::hash_password()`.

---

<div align="center">

**The Framework Auth Engine — Secure by Design** 🛡️

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
