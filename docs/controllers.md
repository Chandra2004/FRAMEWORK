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

use TheFramework\App\Request;
use TheFramework\Models\User;

class UserController
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
            abort(404, "User not found");
        }

        return view('users.show', ['user' => $user]);
    }

    public function store(Request $request)
    {
        $data = $request->input();
        $user = User::create($data);

        return redirect('/users');
    }
}
```

---

## Dependency Injection

Framework automatically injects dependencies via Constructor atau Method parameters.

### Constructor Injection

```php
<?php

namespace TheFramework\Http\Controllers;

use TheFramework\App\Request;
use TheFramework\Services\UserService;

class UserController
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
public function show($id, Request $request, UserService $service)
{
    // $id from route parameter
    // $request auto-injected
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

### Get All Input

```php
public function store(Request $request)
{
    $allData = $request->input();  // All POST/GET data

    User::create($allData);
}
```

### Get Specific Input

```php
$name = $request->input('name');
$email = $request->input('email');

// With default value
$country = $request->input('country', 'Indonesia');
```

### Check if Input Exists

```php
if ($request->has('email')) {
    // Process email
}
```

---

## Responses

### Return View

```php
public function index()
{
    return view('users.index', ['users' => User::all()]);
}
```

### Return JSON (API)

```php
public function apiIndex()
{
    $users = User::all();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
}
```

### Redirect

```php
public function store(Request $request)
{
    User::create($request->input());

    // Redirect to route
    return redirect('/users');

    // Or with message
    $_SESSION['success'] = "User created!";
    return redirect('/users');
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
