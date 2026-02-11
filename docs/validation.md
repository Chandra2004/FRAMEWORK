# ✅ Validation (Ultimate Edition)

Framework menyediakan validator yang powerful dengan 50+ built-in rules untuk memvalidasi input pengguna sebelum memproses data.

---

## 📋 Daftar Isi

1. [Overview: 2 Cara Validasi](#overview-2-cara-validasi)
2. [Cara 1: Manual Validation (Controller)](#cara-1-manual-validation-controller)
3. [Cara 2: Form Request (Recommended)](#cara-2-form-request-recommended)
4. [Menampilkan Error di View](#menampilkan-error-di-view)
5. [Daftar Rules Lengkap](#daftar-rules-lengkap)
6. [Database Validation](#database-validation)
7. [File Validation](#file-validation)
8. [Custom Labels](#custom-labels)

---

## Overview: 2 Cara Validasi

Framework ini menyediakan **2 cara** untuk validasi input:

| Cara                  | Lokasi           | Use Case                       | Auto Redirect? |
| :-------------------- | :--------------- | :----------------------------- | :------------: |
| **Manual Validation** | Dalam Controller | Simple forms, quick validation |   ❌ Manual    |
| **Form Request**      | Dedicated class  | Complex forms, reusable rules  |  ✅ **Auto**   |

---

## Cara 1: Manual Validation (Controller)

**Best for:** Simple forms, quick prototyping, one-time validation.

### Step 1: Validate in Controller

```php
use TheFramework\App\Validator;
use TheFramework\Helpers\Helper;

public function store() {
    $validator = new Validator();

    $isValid = $validator->validate($_POST, [
        'username' => 'required|alpha_dash|min:4|max:20',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'age'      => 'nullable|numeric|between:17,99'
    ]);

    if (!$isValid) {
        // Manual flash errors & old input
        $_SESSION['errors'] = $validator->errors();
        $_SESSION['old'] = $_POST;

        return Helper::redirect('/register');
    }

    // Validation passed
    User::create([
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => Helper::hash_password($_POST['password'])
    ]);

    return Helper::redirect('/users', 'success', 'User created!');
}
```

### Step 2: Display Errors in View

```php
<!-- Display all errors -->
<?php if ($errors = $_SESSION['errors'] ?? null): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <li><?= Helper::e($error) ?></li>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Repopulate form -->
<input type="text" name="username" value="<?= Helper::old('username') ?>">

<?php
// Clean up session
unset($_SESSION['errors'], $_SESSION['old']);
?>
```

**Pros:**

- ✅ Simple, straightforward
- ✅ No extra files needed

**Cons:**

- ❌ Manual error flashing
- ❌ Manual redirect
- ❌ Code duplication jika form sama dipakai di banyak tempat

---

## Cara 2: Form Request (Recommended)

**Best for:** Complex forms, reusable validation, production apps.

### Step 1: Generate Form Request

```bash
php artisan make:request CreateUserRequest
```

**Output:**

```
★ SUCCESS  Request dibuat: CreateUserRequest (app/Http/Requests/CreateUserRequest.php)
```

### Step 2: Define Rules

File: `app/Http/Requests/CreateUserRequest.php`

```php
<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\FormRequest;

class CreateUserRequest extends FormRequest
{
    /**
     * Authorization check (optional)
     */
    public function authorize(): bool
    {
        return true; // or check user permission
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'username' => 'required|alpha_dash|min:4|max:20|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'age'      => 'nullable|numeric|between:13,120',
        ];
    }

    /**
     * Custom labels for error messages
     */
    public function labels(): array
    {
        return [
            'username' => 'Nama Pengguna',
            'email'    => 'Alamat Email',
            'password' => 'Kata Sandi',
            'age'      => 'Umur',
        ];
    }
}
```

### Step 3: Use in Controller

```php
use TheFramework\Http\Requests\CreateUserRequest;
use TheFramework\Models\User;
use TheFramework\Helpers\Helper;

class UserController
{
    /**
     * ✨ MAGIC: Validation happens AUTOMATICALLY!
     * If validation fails, auto redirects back with errors.
     * If we reach here, validation PASSED!
     */
    public function store(CreateUserRequest $request)
    {
        // No need to check validation!
        // Get only validated data (safe from mass assignment)
        $data = $request->validated();

        // Hash password
        $data['password'] = Helper::hash_password($data['password']);

        // Create user
        User::create($data);

        return Helper::redirect('/users', 'success', 'User created!');
    }
}
```

### Step 4: Display Errors in View (Same)

```php
<!-- Errors automatically flashed by FormRequest -->
<?php if ($errors = $_SESSION['errors'] ?? null): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <li><?= Helper::e($error) ?></li>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Old input automatically flashed -->
<input type="text" name="username" value="<?= Helper::old('username') ?>">
```

**Pros:**

- ✅ **Auto validation** on controller injection
- ✅ **Auto redirect back** jika error
- ✅ **Auto flash errors & old input**
- ✅ **Reusable** (bisa dipakai di update, store, dll)
- ✅ **Clean controller** - no validation clutter
- ✅ **Type safety** - IDE autocomplete
- ✅ **Authorization** built-in

**Cons:**

- ❌ Satu file ekstra (tapi worth it!)

---

## Menampilkan Error di View

Di View (`resources/views/register.php`), tampilkan pesan error:

```html
<!-- Input Username -->
<input type="text" name="username" value="<?= old('username') ?>" />

<?php if (isset($errors['username'])): ?>
<div class="text-danger"><?= e($errors['username']) ?></div>
<?php endif; ?>
```

**Helper untuk Old Input:**

```php
function old($key) {
    return $_SESSION['old'][$key] ?? '';
}
```

---

## Daftar Rules Lengkap

### Basic Rules

| Rule       | Deskripsi                                | Contoh                           |
| :--------- | :--------------------------------------- | :------------------------------- |
| `required` | Field tidak boleh kosong                 | `'name' => 'required'`           |
| `nullable` | Boleh null/kosong, skip validasi lainnya | `'phone' => 'nullable\|numeric'` |
| `accepted` | Checkbox harus di-check (yes/on/1/true)  | `'terms' => 'accepted'`          |

### Type Validation

| Rule         | Deskripsi                      | Contoh                    |
| :----------- | :----------------------------- | :------------------------ |
| `string`     | Harus berupa teks              | `'bio' => 'string'`       |
| `numeric`    | Harus angka (int atau float)   | `'price' => 'numeric'`    |
| `integer`    | Harus bilangan bulat           | `'quantity' => 'integer'` |
| `boolean`    | Harus boolean (true/false/1/0) | `'active' => 'boolean'`   |
| `alpha`      | Hanya huruf A-Z a-z            | `'name' => 'alpha'`       |
| `alpha_num`  | Huruf dan angka                | `'code' => 'alpha_num'`   |
| `alpha_dash` | Huruf, angka, dash, underscore | `'slug' => 'alpha_dash'`  |

### Format Validation

| Rule                 | Deskripsi               | Contoh                                  |
| :------------------- | :---------------------- | :-------------------------------------- |
| `email`              | Format email valid      | `'email' => 'email'`                    |
| `url`                | Format URL valid        | `'website' => 'url'`                    |
| `ip`                 | Alamat IP valid (v4/v6) | `'ip_address' => 'ip'`                  |
| `json`               | String JSON valid       | `'metadata' => 'json'`                  |
| `date`               | Tanggal valid           | `'birthdate' => 'date'`                 |
| `date_format:format` | Tanggal sesuai format   | `'published_at' => 'date_format:Y-m-d'` |

### Size Validation

| Rule          | Deskripsi                                          | Contoh                     |
| :------------ | :------------------------------------------------- | :------------------------- |
| `min:x`       | Minimal x karakter (string) atau x nilai (numeric) | `'password' => 'min:8'`    |
| `max:x`       | Maksimal x karakter                                | `'title' => 'max:255'`     |
| `between:x,y` | Panjang atau nilai antara x dan y                  | `'age' => 'between:17,99'` |
| `size:x`      | Tepat x karakter                                   | `'pin' => 'size:4'`        |

### Comparison

| Rule         | Deskripsi                            | Contoh                                  |
| :----------- | :----------------------------------- | :-------------------------------------- |
| `same:field` | Nilai harus sama dengan field lain   | `'password_confirm' => 'same:password'` |
| `confirmed`  | Auto cek field `{name}_confirmation` | `'password' => 'confirmed'`             |
| `in:a,b,c`   | Nilai harus salah satu dari list     | `'gender' => 'in:male,female,other'`    |
| `not_in:x,y` | Nilai TIDAK boleh dari list          | `'status' => 'not_in:banned,deleted'`   |

---

## Database Validation

Validator bisa query database untuk validasi unique dan exists.

### Rule: `unique`

Cek apakah nilai belum ada di database (cocok untuk email/username).

**Basic Usage:**

```php
'email' => 'required|email|unique:users,email'
// Format: unique:table,column
```

**Ignore ID (Untuk Update):**

```php
// Saat update profil user ID 5, ignore email user 5 sendiri
'email' => 'required|email|unique:users,email,5,id'
// Format: unique:table,column,except_id,id_column
```

**Contoh Real (Update Profile):**

```php
$userId = $_SESSION['user_id'];

$validator->validate($_POST, [
    'email' => "required|email|unique:users,email,{$userId},id",
    'username' => "required|unique:users,username,{$userId},id"
]);
```

### Rule: `exists`

Cek apakah nilai EXISTS di database (cocok untuk foreign key).

```php
'category_id' => 'required|exists:categories,id',
'user_id' => 'required|exists:users,id'
// Format: exists:table,column
```

**Contoh Real (Blog Post):**

```php
$validator->validate($_POST, [
    'title' => 'required|min:5',
    'category_id' => 'required|exists:categories,id', // Cek kategori ada
    'author_id' => 'required|exists:users,id'         // Cek user ada
]);
```

---

## File Validation

Validasi file upload (image, dokumen, dll).

### Rule: `mimes`

Validasi ekstensi file.

```php
'avatar' => 'required|mimes:jpg,jpeg,png,gif',
'document' => 'nullable|mimes:pdf,doc,docx'
// Format: mimes:ext1,ext2,ext3
```

### Rule: `image`

Shortcut untuk `mimes:jpg,jpeg,png,bmp,gif,svg,webp`.

```php
'profile_picture' => 'required|image|max:2048'
// max:2048 = maksimal 2MB
```

### Rule: `max` (Untuk File)

Ukuran file maksimal dalam KB.

```php
'video' => 'mimes:mp4,avi|max:10240' // Maksimal 10MB
```

**Catatan:** File upload di PHP berupa array:

```php
[
    'name' => 'photo.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/phpXXXX',
    'error' => 0,
    'size' => 15360  // in bytes
]
```

---

## Custom Labels

Ubah nama field di pesan error menjadi bahasa Indonesia atau lebih deskriptif.

```php
$rules = [
    'username' => 'required|min:4',
    'email' => 'required|email'
];

$labels = [
    'username' => 'Nama Pengguna',
    'email' => 'Alamat Email'
];

$validator->validate($_POST, $rules, $labels);

// Error message:
// "Nama Pengguna wajib diisi."
// "Alamat Email format email tidak valid."
```

---

## Advanced Examples

### Registration Form

```php
$validator->validate($_POST, [
    'username' => 'required|alpha_dash|min:4|max:20|unique:users,username',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'age'      => 'required|numeric|between:13,120',
    'gender'   => 'required|in:male,female,other',
    'terms'    => 'accepted'
]);
```

### Blog Post Form

```php
$validator->validate($_POST, [
    'title'       => 'required|min:5|max:255',
    'slug'        => 'required|alpha_dash|unique:posts,slug',
    'content'     => 'required|min:50',
    'category_id' => 'required|exists:categories,id',
    'image'       => 'nullable|image|max:2048',
    'published'   => 'boolean'
]);
```

### Profile Update

```php
$userId = $_SESSION['user_id'];

$validator->validate($_POST, [
    'email'    => "required|email|unique:users,email,{$userId},id",
    'bio'      => 'nullable|string|max:500',
    'avatar'   => 'nullable|image|max:1024',
    'website'  => 'nullable|url'
]);
```

---

## Error Handling Best Practices

### ✅ DO

```php
// Return early jika validasi gagal
if (!$validator->validate($_POST, $rules)) {
    return redirect()->back()->withErrors($validator->errors());
}

// Pisahkan validation logic ke Request class
class RegisterRequest {
    public function rules() {
        return ['email' => 'required|email|unique:users,email'];
    }
}
```

### ❌ DON'T

```php
// Jangan skip validasi
User::create($_POST); // Bahaya! Bisa mass assignment vulnerability

// Jangan validasi di View
// Validasi HARUS di Controller/Request
```

---

## Next Steps

- 📖 [Database](database.md)
- 📖 [ORM Guide](orm.md)
- 📖 [Security](security.md)
- 📖 [Tutorial: Auth System](tutorial-auth.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
