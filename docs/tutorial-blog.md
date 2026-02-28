# 📝 Tutorial: Membangun Aplikasi Bertenaga (Clean Architecture)

Tutorial ini akan memandu Anda membuat aplikasi dengan operasi CRUD menggunakan **The Framework v5.0**. Anda akan diajak menyelami praktik **Clean Architecture** yang digunakan oleh framework ini, yang terdiri dari 5 lapisan utama: Router, Form Request, Controller, Service, dan Repository.

---

## 📋 Daftar Isi

1. [Pemahaman Arsitektur (Wajib Dibaca)](#pemahaman-arsitektur-wajib-dibaca)
2. [Persiapan Struktur (Tabel & Model)](#persiapan-struktur-tabel--model)
3. [Membuat Lapisan Validasi (Form Request)](#membuat-lapisan-validasi-form-request)
4. [Membuat Lapisan Data (Repository)](#membuat-lapisan-data-repository)
5. [Membuat Lapisan Bisnis (Service)](#membuat-lapisan-bisnis-service)
6. [Membuat Lapisan HTTP (Controller)](#membuat-lapisan-http-controller)
7. [Mendaftarkan Rute (Router)](#mendaftarkan-rute-router)

---

## Pemahaman Arsitektur (Wajib Dibaca)

Banyak framework mengajarkan untuk menuliskan seluruh logika aplikasi (Validasi + Upload File + Simpan Database) di dalam Controller. Di **The Framework v5.0**, kita menggunakan arsitektur kelas enterprise untuk kemudahan _scaling_:

1. **Route (`routes/web.php`)**: Menerima request masuk dari URL dan meneruskannya ke Controller.
2. **Form Request (`app/Http/Requests`)**: Mencegat request sebelum masuk ke Controller untuk divalidasi.
3. **Controller (`app/Http/Controllers`)**: Menghandle response HTTP (Redirect, Render HTML, JSON).
4. **Service (`app/Services`)**: Tempat logika "kotor" berada (Cek Email ganda, Simpan/Hapus Gambar, Algoritma).
5. **Repository (`app/Repositories`)**: Pekerja keras database (Menjalankan Query Insert/Update/Delete khusus menggunakan Transaksi PDO).

Mari kita mulai membuat Management User Sederhana sebagai contohnya!

---

## Persiapan Struktur (Tabel & Model)

### 1. Buat Tabel

Pertama, kita harus membuat migrasi untuk tabel ke database:

```bash
php artisan make:migration create_users_table
```

File: `database/migrations/xxxx_create_users_table.php`

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

Lalu eksekusi migrasinya: `php artisan migrate`

### 2. Definisikan Model

Buat model menggunakan Artisan:

```bash
php artisan make:model User
```

File: `app/Models/User.php`

```php
namespace TheFramework\Models;
use TheFramework\App\Database\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'uid'; // Custom UID

    protected $fillable = ['uid', 'name', 'email', 'profile_picture'];
}
```

---

## Membuat Lapisan Validasi (Form Request)

Buat request khusus untuk mengelola aturan "Name" dan "Email":

```bash
php artisan make:request UserRequest
```

File: `app/Http/Requests/UserRequest.php`

```php
namespace TheFramework\Http\Requests;
use TheFramework\App\Http\Request;

class UserRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'profile_picture' => 'nullable|file|images|max:2048',
        ];
    }

    public function updateRule(): array
    {
        // Gabungkan rules utama dengan perintah delete gambar saat UPDATE
        return array_merge($this->rules(), [
            'delete_profile_picture' => 'nullable'
        ]);
    }

    public function validated(): array
    {
        return $this->validate($this->rules());
    }

    public function updateValidated(): array
    {
        return $this->validate($this->updateRule());
    }
}
```

---

## Membuat Lapisan Data (Repository)

Repo bertanggung jawab murni berkomunikasi dengan sintaks database, termasuk Transaksi Rollback-nya.

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
        return $this->model->query()->orderBy('updated_at', 'DESC')->get();
    }

    public function getInformation(string $uid) {
        return $this->model->where('uid', '=', $uid)->first();
    }

    // Transaksi Database yang Aman untuk Insert
    public function createRepo(array $data) {
        try {
            $this->db->beginTransaction();
            $this->model->create($data);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e; // Lempar error ke layer atasnya (Service)
        }
    }

    // Transaksi Update
    public function updateRepo(array $data, string $uid) {
        // Logika update...
    }
}
```

---

## Membuat Lapisan Bisnis (Service)

Disinilah Anda harus menempatkan logika (contohnya memastikan tidak ada email ganda) dan mendelegasikan perintah upload pada `UploadHandler`.

File: `app/Services/UserService.php`

```php
namespace TheFramework\Services;

use Exception;
use TheFramework\Repositories\UserRepository;
use TheFramework\Handlers\UploadHandler;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;

class UserService
{
    protected UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    public function createUserService(UserRequest $request)
    {
        $photoName = null;

        // 1. Eksekusi file upload
        if ($request->hasFile('profile_picture')) {
            $photoName = UploadHandler::handleUploadToWebP(
                $request->file('profile_picture'), '/user-pictures', 'foto_'
            );
            if (UploadHandler::isError($photoName)) {
                throw new Exception(UploadHandler::getErrorMessage($photoName));
            }
        }

        // 2. Siapkan Data yang Di-*validate* Form Request
        $data = $request->validated();
        $data['profile_picture'] = $photoName;
        $data['uid'] = Helper::uuid();

        // 3. Delegate insert ke Repository
        try {
            return $this->repo->createRepo($data);
        } catch (Exception $e) {
            // Rollback manual! Jika gagal insert DB, hapus file foto yang terlanjur ter-upload.
            if ($photoName) UploadHandler::delete($photoName, '/user-pictures');
            throw new Exception('Gagal Data: ' . $e->getMessage());
        }
    }
}
```

---

## Membuat Lapisan HTTP (Controller)

Perhatikan betapa "bersihnya" Controller kita karena semua logika telah ditaruh di tempat yang semestinya:

File: `app/Http/Controllers/HomeController.php`

```php
namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\Http\Requests\UserRequest;
use TheFramework\Services\UserService;

class HomeController extends Controller
{
    private UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
    }

    // Tampilkan View (Memanggil Service)
    public function users() {
        return view('interface.users', [
            'users' => $this->userService->getAll(),
        ]);
    }

    // Eksekusi Pembuatan Data (Dependency Injection UserRequest!)
    public function createUser(UserRequest $request) {
        try {
            $this->userService->createUserService($request);

            return redirect('/users', 'success', 'Berhasil Ditambahkan');
        } catch (Exception $e) {
            return redirect('/users', 'error', $e->getMessage());
        }
    }
}
```

---

## Mendaftarkan Rute (Router)

The Framework v5.0 mendukung router berbasis Prefix, Group, dan Middleware mutakhir di `routes/web.php`:

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
        'middleware' => [CsrfMiddleware::class] // Lindungi API
    ],
    function () {
        Router::post('/create', HomeController::class, 'createUser');
        Router::post('/update/{uid}', HomeController::class, 'updateUser');
        Router::post('/delete/{uid}', HomeController::class, 'deleteUser');
    }
);
```

### Kesimpulan

Aplikasi Anda kini bukan lagi sekadar aplikasi _Blog Procedural_, Anda baru saja menyusun arsitektur perangkat lunak dengan kontrol versi database tersendiri dan pola yang kuat untuk keamanan, file processing _atomic_ (file batal simpan jika server query DB Error), dan HTTP Middleware tingkat lanjut.

**Good Job! Terapkan Arsitektur ini di setiap aplikasi the framework Anda.**
