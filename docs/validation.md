# VALIDATION SYSTEM

Validator di The-Framework didesain agar mirip dengan Laravel, sehingga mudah dipelajari.

## Fitur Utama

- **Auto Redirect**: Jika validasi `Request` gagal, otomatis redirect kembali ke form sebelumnya dengan error message & old input.
- **Rule Lengkap**: Tersedia berbagai rule validasi penting.
- **Security Check**: Termasuk validasi file upload yang aman.

## Cara Menggunakan

### 1. Via Controller (Recommended)

```php
public function store(Request $request)
{
    // Validate akan mengembalikan array data yang sudah divalidasi
    $validated = $request->validate([
        'username' => 'required|min:5|unique:users,username',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed', // cek password_confirmation
        'age'      => 'required|numeric|min:17',
        'terms'    => 'required|accepted'
    ]);

    // Jika sampai di baris ini, berarti validasi berhasil (Lanjut simpan ke DB)
    User::create($validated);
}
```

### 2. Manual (Tanpa Request)

```php
use TheFramework\App\Validator;

$validator = new Validator();
$isValid = $validator->validate($data, $rules);

if (!$isValid) {
    $errors = $validator->errors();
}
```

---

## Daftar Rule Tersedia

| Rule               | Deskripsi                                        | Contoh                            |
| :----------------- | :----------------------------------------------- | :-------------------------------- |
| `required`         | Field wajib diisi.                               | `'name' => 'required'`            |
| `numeric`          | Harus berupa angka.                              | `'age' => 'numeric'`              |
| `min:value`        | Minimal karakter (string) atau nilai (angka).    | `'password' => 'min:8'`           |
| `max:value`        | Maksimal karakter/nilai.                         | `'bio' => 'max:255'`              |
| `email`            | Format email valid.                              | `'email' => 'email'`              |
| `unique:table,col` | Cek ke database apakah data unik.                | `'email' => 'unique:users,email'` |
| `confirmed`        | Field harus cocok dengan `{field}_confirmation`. | `'password' => 'confirmed'`       |
| `date`             | Harus format tanggal yang valid.                 | `'dob' => 'date'`                 |
| `boolean`          | Harus true, false, 1, 0.                         | `'is_active' => 'boolean'`        |
| `alpha`            | Hanya huruf.                                     | `'name' => 'alpha'`               |
| `alpha_num`        | Huruf dan Angka.                                 | `'username' => 'alpha_num'`       |
| `file`             | Input harus berupa upload file.                  | `'avatar' => 'file'`              |
| `mimes:jpg,png`    | Validasi ekstensi file.                          | `'foto' => 'mimes:jpg,png,webp'`  |

---

## Menampilkan Error di View

Validation error otomatis tersimpan di Session flash data `validation_errors`.
Gunakan helper global `error()` atau cek session manual.

```html
<!-- Contoh Form Blade -->
<form method="POST" action="/register">
  @csrf

  <label>Username</label>
  <input type="text" name="username" value="{{ old('username') }}" />
  @if(isset($errors['username']))
  <span class="text-red-500">{{ $errors['username'] }}</span>
  @endif

  <label>Password</label>
  <input type="password" name="password" />

  <label>Confirm Password</label>
  <input type="password" name="password_confirmation" />

  <button type="submit">Register</button>
</form>
```
