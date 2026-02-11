# ✅ Validation (Ultimate Edition)

Framework menyediakan validator yang powerful dengan 50+ built-in rules untuk memvalidasi input pengguna sebelum memproses data.

---

## 📋 Daftar Isi

1. [Validasi Dasar](#validasi-dasar)
2. [Menampilkan Error](#menampilkan-error)
3. [Daftar Rules Lengkap](#daftar-rules-lengkap)
4. [Database Validation](#database-validation)
5. [File Validation](#file-validation)
6. [Custom Labels](#custom-labels)

---

## Validasi Dasar

Validator dilakukan di Controller atau Request class sebelum menyimpan data.

```php
use TheFramework\App\Validator;

public function store() {
    $validator = new Validator();

    $isValid = $validator->validate($_POST, [
        'username' => 'required|alpha_dash|min:4|max:20',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'age'      => 'numeric|between:17,99'
    ]);

    if (!$isValid) {
        $errors = $validator->errors();
        // Redirect dengan error
        return redirect('/register')
            ->withErrors($errors)
            ->withInput();
    }

    // Sukses! Lanjut simpan database
    User::create($_POST);
}
```

---

## Menampilkan Error

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
