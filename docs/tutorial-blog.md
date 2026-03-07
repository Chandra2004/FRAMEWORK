# 📝 Tutorial: Membangun Aplikasi Enterprise (Clean Architecture)

Tutorial ini akan memandu Anda dalam membangun aplikasi CRUD (Create, Read, Update, Delete) menggunakan **The Framework v5.0.1 Premium**. Kita akan menerapkan pola **Clean Architecture** yang memisahkan tanggung jawab ke dalam 5 lapisan utama untuk memastikan aplikasi Anda mudah dipelihara (_maintainable_) dan dikembangkan (_scalable_).

---

## 📋 Daftar Isi

1. [Pemahaman Arsitektur (Layered Pattern)](#pemahaman-arsitektur-layered-pattern)
2. [Persiapan Struktur (Tabel & Model)](#persiapan-struktur-tabel--model)
3. [Lapisan Validasi (Form Request)](#lapisan-validasi-form-request)
4. [Lapisan Data (Repository)](#lapisan-data-repository)
5. [Lapisan Bisnis (Service)](#lapisan-bisnis-service)
6. [Lapisan HTTP (Controller)](#lapisan-http-controller)
7. [Mendaftarkan Rute (Router)](#mendaftarkan-rute-router)

---

## Pemahaman Arsitektur (Layered Pattern)

Berbeda dengan framework monolitik tradisional, **The Framework v5.0** mendorong pemisahan logika agar Controller tetap ramping (_Skinny Controller_):

1. **Route (`routes/web.php`)**: Gerbang utama yang menerima request URL dan mengarahkannya ke Controller.
2. **Form Request (`app/Http/Requests`)**: Mencegat request sebelum masuk ke Controller untuk divalidasi secara otomatis.
3. **Controller (`app/Http/Controllers`)**: Fokus pada penanganan alur HTTP (mengirim Response JSON, Redirect, atau Render View).
4. **Service (`app/Services`)**: Menangani logika bisnis inti (algoritma, pengolahan file, integrasi API pihak ketiga).
5. **Repository (`app/Repositories`)**: Fokus murni pada akses data ke database, memastikan integritas query dan transaksi.

Mari kita implementasikan sistem Manajemen User sebagai studi kasus.

---

## Persiapan Struktur (Tabel & Model)

### 1. Migrasi Database

Gunakan perintah Artisan untuk membuat file migrasi:

```bash
php artisan make:migration create_users_table
```

Edit file di `database/migrations/`:

```php
public function up(): void {
    Schema::create('users', function(Blueprint $table) {
        $table->string('uid', 36)->primary();
        $table->string('name', 100);
        $table->string('email', 100)->unique();
        $table->string('profile_picture')->nullable();
        $table->timestamps();
    });
}
```

Jalankan perintah: `php artisan migrate`

### 2. Definisi Model

Buat model `User` di `app/Models/User.php`:

```php
namespace TheFramework\Models;

use TheFramework\App\Database\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'uid'; // Menggunakan UID sebagai Primary Key
    public $incrementing = false;  // Karena UID bukan integer auto-increment

    protected $fillable = ['uid', 'name', 'email', 'profile_picture'];
}
```

---

## Lapisan Validasi (Form Request)

Buat request khusus untuk memisahkan aturan validasi dari logika bisnis:

```bash
php artisan make:request UserRequest
```

File: `app/App/Http/Requests/UserRequest.php`

```php
namespace TheFramework\App\Http\Requests;

use TheFramework\App\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'            => 'required|min:3|max:100',
            'email'           => 'required|email|unique:users,email',
            'profile_picture' => 'nullable|image|max:2MB',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.email'   => 'Format email tidak valid.',
        ];
    }
}
```

---

## Lapisan Data (Repository)

Repository menangani interaksi langsung dengan database. Gunakan Transaksi PDO untuk menjamin atomisitas data.

File: `app/Repositories/UserRepository.php`

```php
namespace TheFramework\Repositories;

use Exception;
use TheFramework\App\Database\Database;
use TheFramework\Models\User;

class UserRepository
{
    protected User $model;
    protected Database $db;

    public function __construct() {
        $this->model = new User();
        $this->db = Database::getInstance();
    }

    public function getAll() {
        return $this->model->latest()->get();
    }

    public function getInformation(string $uid) {
        return $this->model->where('uid', '=', $uid)->first();
    }

    // Transaksi Database yang Aman untuk Insert
    public function create(array $data) {
        try {
            $this->db->beginTransaction();
            $user = $this->model->create($data);
            $this->db->commit();
            return $user;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Transaksi Update
    public function updateRepo(array $data, string $uid) {
        // Logika update...
    }
}
```

---

## Lapisan Bisnis (Service)

Service menengahi Controller dan Repository. Di sini kita menaruh logika kompleks seperti pengolahan file gambar.

File: `app/Services/UserService.php`

```php
namespace TheFramework\Services;

use Exception;
use TheFramework\Repositories\UserRepository;
use TheFramework\Handlers\UploadHandler;
use TheFramework\Helpers\Helper;
use TheFramework\App\Http\Requests\UserRequest;

class UserService
{
    protected UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    public function registerUser(UserRequest $request)
    {
        $photoName = null;

        // 1. Proses Upload Gambar ke WebP (Private Storage)
        if ($request->hasFile('profile_picture')) {
            $photoName = UploadHandler::handleUploadToWebP(
                $request->file('profile_picture'),
                '/user-pictures',
                'user_'
            );

            if (UploadHandler::isError($photoName)) {
                throw new Exception(UploadHandler::getErrorMessage($photoName));
            }
        }

        // 2. Persiapan Data (Sudah tervalidasi oleh FormRequest)
        $data = $request->validated();
        $data['uid'] = Helper::uuid();
        $data['profile_picture'] = $photoName;

        // 3. Simpan via Repository
        try {
            return $this->repo->create($data);
        } catch (Exception $e) {
            // Cleanup: Hapus file jika database gagal diupdate (Atomic Operation)
            if ($photoName) {
                UploadHandler::delete($photoName, '/user-pictures');
            }
            throw new Exception("Gagal menyimpan data ke database.");
        }
    }
}
```

---

## Lapisan HTTP (Controller)

Controller sekarang menjadi sangat bersih karena hanya bertugas menjembatani Request ke Service.

File: `app/Http/Controllers/HomeController.php`

```php
namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\App\Http\Requests\UserRequest;
use TheFramework\Services\UserService;

class HomeController extends Controller
{
    protected UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
    }

    // Tampilkan View (Memanggil Service)
    public function users() {
        return view('interface.users', [
            'users' => $this->userService->getAll(),
        ]);
    }

    public function createUser(UserRequest $request)
    {
        try {
            $this->userService->registerUser($request);

            return redirect('/users', 'success', 'User berhasil didaftarkan ke sistem.');
        } catch (Exception $e) {
            return redirect('/users', 'error', $e->getMessage());
        }
    }
}
```

---

## Mendaftarkan Rute (Router)

Daftarkan rute di `routes/web.php` dengan perlindungan Middleware keamanan.

```php
<?php
use TheFramework\App\Http\Router;
use TheFramework\Http\Controllers\HomeController;
use TheFramework\Middleware\CsrfMiddleware;

// Rute Tampilan
Router::get('/users', HomeController::class, 'users');

// Aksi User Berbahaya (Dikelompokkan menggunakan pengaman Middleware & CSRF)
Router::group(
    [
        'prefix'     => '/users',
        'middleware' => [CsrfMiddleware::class] // Lindungi dari serangan Cross-Site Request Forgery
    ],
    function () {
        Router::post('/create', HomeController::class, 'createUser');
        Router::post('/update/{uid}', HomeController::class, 'updateUser');
        Router::post('/delete/{uid}', HomeController::class, 'deleteUser');
    }
);
```

### Kesimpulan

Dengan mengikuti pola ini, aplikasi Anda memiliki struktur yang sangat kuat. Jika di masa depan Anda ingin mengubah database atau mengganti sistem penyimpanan file, Anda hanya perlu mengedit `Repository` atau `Service` saja tanpa menyentuh `Controller`.

**Selamat! Anda telah menerapkan standar Enterprise pada aplikasi The Framework.**
