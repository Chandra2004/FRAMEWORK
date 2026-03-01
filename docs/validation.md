# ✅ Validation — v5.0.1 Premium

The Framework v5 menyediakan sistem validasi yang sangat kuat (Ultimate Edition) dengan **50+ built-in rules**. Anda dapat memvalidasi input pengguna secara otomatis menggunakan **Form Request** atau secara manual di dalam Controller.

---

## 📋 Daftar Isi

1. [Overview Validasi](#overview-validasi)
2. [Cara 1: Form Request (Direkomendasikan)](#cara-1-form-request-direkomendasikan)
3. [Cara 2: Manual Validation (Controller)](#cara-2-manual-validation-controller)
4. [Daftar Rules Lengkap (Tabel)](#daftar-rules-lengkap-tabel)
5. [Validasi Database](#validasi-database)
6. [Validasi File & Gambar](#validasi-file--gambar)
7. [Kustomisasi Pesan & Label](#kustomisasi-pesan--label)

---

## Overview Validasi

Ada dua pendekatan utama untuk melakukan validasi di Framework ini:

| Fitur             | Form Request                           | Manual Validation          |
| :---------------- | :------------------------------------- | :------------------------- |
| **Lokasi**        | Dedicated Class                        | Controller                 |
| **Auto-Redirect** | ✅ Ya (Otomatis ke Halaman Sebelumnya) | ❌ Tidak (Manual)          |
| **Flash Data**    | ✅ Ya (Otomatis `errors` & `old`)      | ❌ Tidak (Manual)          |
| **Keamanan**      | ✅ Sangat Tinggi (Mendukung Authorize) | ⚠️ Tergantung Implementasi |

---

## Cara 1: Form Request (Direkomendasikan)

Ini adalah cara paling bersih dan modern. Validasi terjadi **sebelum** method controller dieksekusi.

### Step 1: Buat Form Request
Gunakan Artisan untuk membuat class request baru:
```bash
php artisan make:request StoreUserRequest
```

### Step 2: Tentukan Aturan (Rules)
Buka file `app/Http/Requests/StoreUserRequest.php`:

```php
namespace TheFramework\App\Http\Requests;

use TheFramework\App\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => 'required|alpha_dash|min:4|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'avatar'   => 'nullable|image|max:2MB',
        ];
    }
}
```

### Step 3: Inject ke Controller

Cukup tambahkan type-hint pada method controller. Framework akan mengurus sisanya.

```php
public function store(StoreUserRequest $request)
{
    // Jika sampai di baris ini, data DIJAMIN VALID!
    $validated = $request->validated();

    User::create($validated);

    return redirect('/dashboard')->success('User berhasil didaftarkan!');
}
```

---

## Cara 2: Manual Validation (Controller)

Gunakan method `$request->validate()` jika Anda ingin melakukan validasi cepat di tempat.

```php
public function update(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|max:255',
        'content' => 'required',
    ]);

    // Lanjutkan proses...
}
```

> **Note:** Method ini akan melempar `ValidationException` yang otomatis ditangkap oleh **Global Exception Handler** untuk melakukan redirect back dengan pesan error.

---

## Daftar Rules Lengkap (Tabel)

### 1. Basic & Presence Rules
| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `required` | Data wajib ada dan tidak boleh kosong. | `'name' => 'required'` |
| `nullable` | Boleh kosong, skip validasi lain jika kosong. | `'bio' => 'nullable\|max:500'` |
| `accepted` | Harus bernilai "yes", "on", 1, atau true. | `'terms' => 'accepted'` |
| `present` | Key harus ada di input, walaupun nilainya kosong. | `'comment' => 'present'` |

### 2. Type Checking Rules
| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `string` | Harus berupa string teks. | `'note' => 'string'` |
| `numeric` | Harus berupa angka (integer atau float). | `'price' => 'numeric'` |
| `integer` | Harus berupa angka bulat. | `'age' => 'integer'` |
| `boolean` | Harus berupa nilai boolean (true, false, 0, 1). | `'status' => 'boolean'` |
| `array` | Harus berupa tipe data array. | `'items' => 'array'` |
| `json` | Harus berupa string JSON yang valid. | `'data' => 'json'` |

### 3. String & Format Rules
| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `alpha` | Hanya boleh berisi huruf. | `'code' => 'alpha'` |
| `alpha_num` | Hanya boleh berisi huruf dan angka. | `'id' => 'alpha_num'` |
| `alpha_dash` | Huruf, angka, dash (-), dan underscore (_). | `'slug' => 'alpha_dash'` |
| `email` | Harus format alamat email yang valid. | `'email' => 'email'` |
| `url` | Harus format URL yang valid. | `'site' => 'url'` |
| `active_url` | Alamat URL yang valid dan host-nya aktif. | `'site' => 'active_url'` |
| `ip` | Alamat IP yang valid (v4 atau v6). | `'ip' => 'ip'` |
| `uuid` | Format UUID yang valid. | `'uuid' => 'uuid'` |
| `regex:pattern`| Nilai harus cocok dengan regex pattern. | `'pin' => 'regex:/^[0-9]{6}$/'` |

### 4. Size & Comparison Rules
| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `min:val` | Minimal panjang (string) atau nilai (numeric). | `'pass' => 'min:8'` |
| `max:val` | Maksimal panjang atau nilai (Mendukung unit file). | `'foto' => 'max:2MB'` |
| `between:x,y` | Nilai harus di antara rentang X dan Y. | `'age' => 'between:17,99'` |
| `size:val` | Ukuran harus tepat senilai VAL. | `'pin' => 'size:6'` |
| `digits:n` | Harus angka dengan panjang tepat N digit. | `'otp' => 'digits:4'` |
| `same:field` | Nilai harus sama dengan field lain. | `'p_conf' => 'same:password'` |
| `confirmed` | Harus sama dengan field `{name}_confirmation`. | `'password' => 'confirmed'` |
| `different:f` | Nilai harus berbeda dengan field F. | `'new' => 'different:old'` |

### 5. Date Rules
| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `date` | Format tanggal yang valid (strtotime). | `'born' => 'date'` |
| `date_format:F`| Tanggal harus sesuai format F (misal: Y-m-d).| `'at' => 'date_format:Y-m-d'` |
| `after:date` | Tanggal harus setelah DATE tertentu. | `'end' => 'after:today'` |
| `before:date` | Tanggal harus sebelum DATE tertentu. | `'start' => 'before:tomorrow'` |

---

## Validasi Database

| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `unique:table,col` | Data belum ada di tabel database. | `'email' => 'unique:users,email'` |
| `exists:table,col` | Data harus ada di tabel database. | `'cat_id' => 'exists:categories,id'`|

**Tips Unique (Ignore ID):**
`'email' => "unique:users,email,{$id},id"` — Gunakan ini saat update agar tidak bentrok dengan email user itu sendiri.

---

## Validasi File & Gambar

| Rule | Deskripsi | Contoh |
| :--- | :--- | :--- |
| `image` | Harus file gambar (jpg, png, webp, svg, dll). | `'foto' => 'image'` |
| `mimes:e1,e2` | Harus file dengan ekstensi tertentu. | `'doc' => 'mimes:pdf,zip'` |
| `max:2MB` | Maksimal ukuran file (cerdas dengan unit). | `'file' => 'max:2MB'` |

---

## Kustomisasi Pesan & Label

### Menampilkan Error di View
Gunakan global helper `error()` atau `@error` (di Blade) untuk mengecek kesalahan.

```php
<input name="email" value="<?= old('email') ?>">
<?php if (has_error('email')): ?>
    <span class="text-red"><?= error('email') ?></span>
<?php endif; ?>
```

Di Blade:
```blade
@error('email')
    <div class="alert text-red">{{ $message }}</div>
@enderror
```

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
