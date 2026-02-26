# 🎮 Controllers

Controllers berisi logika aplikasi untuk menangani HTTP requests dan mengembalikan responses.

---

## Basic Controller

### Create Controller

```bash
php artisan make:controller UserController
```

Generated file: `app/Http/Controllers/UserController.php`

```php
<?php

namespace TheFramework\Http\Controllers;

use TheFramework\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', ['users' => $users]);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return abort(404, "User not found");
        }

        return view('users.show', ['user' => $user]);
    }

    public function store()
    {
        $data = request()->all();
        $user = User::create($data);

        return redirect('/users', 'success', 'User berhasil ditambahkan');
    }
}
```

---

## 🏛️ Directory Structure

The Framework v5.0 memisahkan secara ketat antara area kerja developer dan inti sistem:

- **`app/Http/Controllers`**: Khusus untuk controller buatan Anda (Application Area).
- **`app/App/Internal/Controllers`**: Berisi controller inti (Debug, Error, File, Sitemap) yang menangani fitur framework. Area ini tidak boleh diubah untuk menjaga stabilitas sistem.

---

## Dependency Injection

Framework secara otomatis menyuntikkan (inject) dependensi via Constructor atau Method parameters.

### Constructor Injection

```php
<?php

namespace TheFramework\Http\Controllers;

use TheFramework\Services\UserService;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();
        return view('users.index', compact('users'));
    }
}
```

### Method Injection

```php
public function show($id, UserService $service)
{
    // $id dari route parameter
    // $service auto-injected
    $user = $service->findUser($id);
    return view('users.show', ['user' => $user]);
}
```

---

## Route Parameters

### Single Parameter

```php
// routes/web.php
Router::get('/users/{id}', [UserController::class, 'show']);

// Controller
public function show($id)
{
    $user = User::find($id);
    return view('users.show', ['user' => $user]);
}
```

### Multiple Parameters

```php
// routes/web.php
Router::get('/posts/{postId}/comments/{commentId}', [CommentController::class, 'show']);

// Controller
public function show($postId, $commentId)
{
    $comment = Comment::where('post_id', $postId)
                      ->where('id', $commentId)
                      ->first();
    return view('comments.show', ['comment' => $comment]);
}
```

---

## Request Handling

Anda dapat menggunakan global helper `request()` untuk mengakses input.

### Get All Input

```php
public function store()
{
    $allData = request()->all();  // Semua data POST/GET/JSON
    User::create($allData);
}
```

### Get Specific Input

```php
$name = request('name');
$email = request('email');

// Dengan nilai default
$country = request('country', 'Indonesia');
```

### Check if Input Exists

```php
if (request()->has('email')) {
    // Process email
}
```

---

## Responses (Paten v5.0)

Framework mendukung cara pengembalian response yang sangat bersih (fluent).

### Return View

```php
public function index()
{
    return view('users.index', [
        'users' => User::all(),
        'notification' => flash('notification') // Ambil flash message
    ]);
}
```

### Return JSON (API)

```php
public function apiIndex()
{
    return json([
        'success' => true,
        'data' => User::all()
    ]);
}
```

### Redirect

```php
public function store()
{
    User::create(request()->all());

    // Redirect sederhana
    return redirect('/users');

    // Redirect dengan Notifikasi (Premium)
    return redirect('/users', 'success', 'Data berhasil disimpan!');
    
    // Redirect Kembali (Back)
    return redirect()->back('warning', 'Aksi dibatalkan');
}
```

---

## Resource Controller (CRUD)

### Create Resource Controller

```bash
php artisan make:controller PostController --resource
```

Generates methods:

- `index()` - List all
- `create()` - Show create form
- `store()` - Save new record
- `show($id)` - Show single record
- `edit($id)` - Show edit form
- `update($id)` - Update record
- `destroy($id)` - Delete record

### Register Resource Route

```php
// routes/web.php
Router::resource('/posts', PostController::class);
```

Automatically creates routes:

| Method | URI                | Action  | Route Name    |
| ------ | ------------------ | ------- | ------------- |
| GET    | /posts             | index   | posts.index   |
| GET    | /posts/create      | create  | posts.create  |
| POST   | /posts             | store   | posts.store   |
| GET    | /posts/{id}        | show    | posts.show    |
| GET    | /posts/{id}/edit   | edit    | posts.edit    |
| POST   | /posts/{id}        | update  | posts.update  |
| POST   | /posts/{id}/delete | destroy | posts.destroy |

---

## Validation in Controller

```php
use TheFramework\App\Validator;

public function store(Request $request)
{
    $validator = new Validator($request->input(), [
        'name' => ['required', 'min:3'],
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8']
    ]);

    if ($validator->fails()) {
        $_SESSION['errors'] = $validator->errors();
        return redirect('/users/create');
    }

    User::create($request->input());
    return redirect('/users');
}
```

---

## File Upload in Controller

```php
use TheFramework\Config\UploadHandler;

public function uploadAvatar(Request $request)
{
    $upload = new UploadHandler('avatar');

    if ($upload->isUploaded()) {
        $filename = $upload->saveToPublic('avatars');

        auth()->user()->update(['avatar' => $filename]);

        return redirect('/profile');
    }

    $_SESSION['error'] = $upload->getError();
    return redirect('/profile');
}
```

---

## Controller Best Practices

### ✅ DO

```php
// Keep controllers thin
public function store(Request $request, UserService $service)
{
    $service->createUser($request->input());
    return redirect('/users');
}

// Use services for business logic
class UserService
{
    public function createUser(array $data)
    {
        // Validation
        // Email sending
        // Database transaction
        // etc.
    }
}
```

### ❌ DON'T

```php
// Fat controller (bad)
public function store(Request $request)
{
    // Validation logic
    // Email sending
    // Database transaction
    // File processing
    // External API calls
    // etc. (100+ lines)
}
```

---

## Middleware in Controller

### Apply Middleware

```php
// routes/web.php
Router::get('/admin/users', [AdminController::class, 'index'])
    ->middleware([AuthMiddleware::class, AdminMiddleware::class]);
```

---

## API Controllers

### Create API Controller

```php
<?php

namespace TheFramework\Http\Controllers\Api;

use TheFramework\App\Request;
use TheFramework\Models\User;

class UserApiController
{
    public function index()
    {
        $users = User::all();

        return $this->jsonResponse([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $user = User::create($request->input());

        return $this->jsonResponse([
            'success' => true,
            'data' => $user
        ], 201);
    }

    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

---

## Next Steps

- 📖 [Routing](routing.md)
- 📖 [Validation](validation.md)
- 📖 [Middleware](middleware.md)
- 📖 [Services](services.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
