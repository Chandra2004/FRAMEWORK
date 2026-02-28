# 🌍 HTTP Module (Request, Router, Session & Validator)

> TheFramework v5.0.1 — HTTP Module yang Ditingkatkan (100% Setara Laravel)

---

## 1. Request (`TheFramework\App\Http\Request`)

Object `Request` sekarang mendukung 30+ metode pembantu (helpers) untuk berinteraksi dengan HTTP Request. Anda tidak perlu lagi menggunakan `$_GET`, `$_POST` atau mengambil JSON secara manual.

### Retrieves Input
```php
$request->all();                        // Array of all inputs (GET, POST, JSON, Files)
$request->input('name', 'default');     // Ambil 1 nilai. Support JSON.
$request->query('id');                  // Mengambil dari $_GET
$request->post('name');                 // Mengambil dari $_POST

$request->only(['username', 'password']);   // Hanya key tertentu
$request->except(['_token', 'password']);   // Semua kecuali key ini
```

### Type Casting & Presence
```php
$request->has('email');             // true/false
$request->hasAny(['email', 'tel']); 
$request->filled('username');       // Cek ada & tidak empty string
$request->boolean('is_active');     // auto type-cast boolean
$request->date('created_at');       // Return DateTime instance
$request->string('name')->trim();   // Cast to string
$request->integer('age');           // Cast to INT
```

### URL & Environment
```php
$request->path();        // 'users/edit'
$request->url();         // 'https://site.com/users/edit'
$request->fullUrl();     // Termasuk query string
$request->ip();          // Cerdas cek X-Forwarded-For atau Remote_Addr
$request->userAgent();
$request->method();      // 'GET', 'POST', dll
$request->isMethod('POST');
```

### JSON & Headers
```php
$request->expectsJson();        // True jika 'Accept: application/json' atau Ajax
$request->bearerToken();        // Otomatis tarik dari header 'Authorization'
$request->header('X-API-KEY');  // Safe header reader
```

---

## 2. Router (`TheFramework\App\Http\Router`)

Sistem routing kini menyuguhkan antarmuka yang _fluent_ dengan chaining.

### Membuat Routes Baru
```php
use TheFramework\App\Http\Router;

Router::get('/home', [HomeController::class, 'index'])->name('home');
Router::post('/submit', [FormController::class, 'store'])->name('submit')->middleware('auth');
Router::put('/users/{id}', [UserController::class, 'update']);
Router::delete('/users/{id}', [UserController::class, 'destroy']);
```

### Shortcut Routers
```php
// Render view langsung, cukup 1 baris
Router::view('/about', 'pages.about', ['title' => 'About Us']);

// Redirect URL instan
Router::redirect('/old-link', '/new-link', 301);

// Custom 404 (Fallback)
Router::fallback(function() {
    return 'Halaman Custom 404 Ini!';
});
```

---

## 3. Validator (`TheFramework\App\Http\Validator`)

Kini disokong oleh 28 rule andalan (termasuk validasi database) dan mendukung **array wildcard**.

### Contoh Penggunaan
```php
$rules = [
    'email' => 'required|email|unique:users,email',
    'age' => 'required|numeric|min:18',
    'website' => 'active_url',
    'password' => 'required|min:8|confirmed', // Harus cocok dengan password_confirmation

    // 🚀 NEW: Validasi Dinamis untuk Array!
    'users.*.email' => 'required|email|unique:users,email',
    'phones.*' => 'numeric|digits_between:10,13'
];

// $request->validate akan otomatis melempar ValidationException jika gagal
$validated = $request->validate($rules);
// $validated berisi data terfilter saja (only validated)
```

### Daftar Rules yang Tersedia
- `required`, `accepted`, `confirmed`, `same`, `different`
- `string`, `numeric`, `integer`, `boolean`, `array`, `json`
- `alpha`, `alpha_num`, `alpha_dash`
- `email`, `url`, `active_url`, `ip`, `mac_address`, `uuid`, `regex`
- `min`, `max`, `between`, `size`, `digits`, `digits_between`
- `in`, `not_in`
- `date`, `date_format`, `after`, `before`
- `unique:{table},{column}`, `exists:{table},{column}`

---

## 4. Form Request Validation (`TheFramework\App\Http\FormRequest`)

FormRequest membuat Controller Anda dijamin super clean dan tidak pusing validasi.

### Buat Class Request
```php
namespace App\Http\Requests;

use TheFramework\App\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pengecekan akses/middleware
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email'
        ];
    }
}
```

### Cukup Type-hint di Controller
```php
public function store(StoreUserRequest $request)
{
    // 🔥 Jika eksekusi mencapai baris ini, data DIJAMIN 100% valid!
    // Auto-lempar ValidationException jika gagal.
    
    User::create($request->validated()); 
}
```

---

## 5. SessionManager & Flash Data

SessionManager tidak hanya untuk memulai session `session_start()`, melainkan class utuh pembungkus operasi array Session yang terstruktur:

```php
use TheFramework\App\Http\SessionManager;

// Manipulasi Dasar
SessionManager::put('cart', ['id' => 1]);
$cart = SessionManager::get('cart');
$item = SessionManager::pull('cart'); // get sekaligus di-delete

// Flash Data (Hidup hanyak untuk 1 page load ke depan)
SessionManager::flash('success', 'Data berhasil diperbarui!');

// Memperpanjang napas flash data 1 page load lagi
SessionManager::reflash(); 
SessionManager::keep(['success']); 
```

---

## 6. Global View Variables (View::share)

Untuk mengirim variabel ke semua file `.blade.php` Anda tanpa mempassingnya manual tiap method Controller. Sangat berguna di Provider.

```php
use TheFramework\App\Http\View;

// Biasa dilakukan di Provider / Middleware / construct Controller
View::share('auth_user', $currentUser);
View::share('site_title', 'My Web Framework');

// Alhasil di view app.blade.php Anda bisa selalu menulis:
// <h1>Selamat datang, {{ $auth_user->name }}</h1>
```
