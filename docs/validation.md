# ✅ Validation

Memvalidasi input pengguna adalah langkah wajib sebelum memproses data. Framework ini menyediakan validator yang simple namun robust.

---

## 📋 Daftar Isi

1.  [Validasi Dasar](#validasi-dasar)
2.  [Menampilkan Error](#menampilkan-error)
3.  [Daftar Rules Lengkap](#daftar-rules-lengkap)
4.  [Custom Validation Message](#custom-validation-message)

---

## Validasi Dasar

Biasanya dilakukan di Controller sebelum menyimpan data.

```php
use TheFramework\Helpers\Validator;
use TheFramework\Helpers\Helper;

public function store() {
    $input = Helper::request()->all();

    $rules = [
        'username' => 'required|min:5|max:20|alpha_dash',
        'email'    => 'required|email',
        'password' => 'required|min:8',
        'age'      => 'numeric|min_value:17'
    ];

    $errors = Validator::validate($input, $rules);

    if (!empty($errors)) {
        // Gagal! Kembalikan ke form dengan error
        Helper::set_flash('errors', $errors);
        Helper::set_flash('old', $input); // Agar input tidak hilang
        Helper::redirect('/register');
        return;
    }

    // Sukses! Lanjut simpan database...
}
```

---

## Menampilkan Error

Di View (`resources/views/register.php`), tampilkan pesan error di bawah input.

```html
<!-- Input Username -->
<input type="text" name="username" value="<?= Helper::old('username') ?>" />

<?php if ($err = Helper::validation_errors('username')): ?>
<div class="text-danger"><?= $err ?></div>
<?php endif; ?>
```

---

## Daftar Rules Lengkap

| Rule               | Deskripsi                                                          |
| :----------------- | :----------------------------------------------------------------- |
| `required`         | Field tidak boleh kosong.                                          |
| `email`            | Harus format email valid.                                          |
| `numeric`          | Harus berupa angka.                                                |
| `string`           | Harus berupa string text.                                          |
| `alpha`            | Hanya huruf (a-z).                                                 |
| `alpha_num`        | Huruf dan angka.                                                   |
| `alpha_dash`       | Huruf, angka, dash, underscore.                                    |
| `min:x`            | Minimal x karakter (string) atau nilai x (jika numeric).           |
| `max:x`            | Maksimal x karakter.                                               |
| `same:field`       | Nilai harus sama dengan field lain (cocok untuk confirm password). |
| `unique:table,col` | Cek database apakah nilai sudah ada (misal email unik).            |

---

## Custom Validation Message

Anda bisa mengubah pesan error default (Bahasa Inggris) menjadi bahasa Anda sendiri.

```php
$messages = [
    'username.required' => 'Nama pengguna wajib diisi, Bos!',
    'email.email' => 'Format email salah tuh.'
];

$errors = Validator::validate($input, $rules, $messages);
```
