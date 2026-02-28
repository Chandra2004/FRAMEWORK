# 🎨 Views & Templating

Framework ini menggunakan engine templating berbasis **Native PHP** yang telah ditingkatkan dengan helper function agar serasa seperti Blade di Laravel. Sederhana, tapi sangat cepat karena tidak butuh kompilasi berat.

---

## 📋 Daftar Isi

1.  [Membuat & Merender View](#membuat--merender-view)
2.  [Mengirim Data ke View](#mengirim-data-ke-view)
3.  [Syntax View (Cheatsheet)](#syntax-view-cheatsheet)
4.  [Layouts & Inheritance (Moderen)](#layouts--inheritance-moderen)
5.  [Partial Views (Include)](#partial-views-include)

---

## Membuat & Merender View

Simpan file view di folder `resources/views/`. Gunakan ekstensi `.php`.

**Contoh: `resources/views/welcome.php`**

Di Controller:

```php
use TheFramework\Core\View;

public function index() {
    return View::render('welcome');
}
```

---

## Mengirim Data ke View

Data array asosiatif akan diekstrak menjadi variabel PHP.

```php
// Controller
View::render('dashboard', [
    'username' => 'Chandra',
    'total_sales' => 5000000
]);
```

```html
<!-- dashboard.php -->
<h1>Selamat Datang, <?= $username ?>!</h1>
<p>Total Penjualan: <?= Helper::rupiah($total_sales) ?></p>
```

---

## Syntax View (Cheatsheet)

Framework v4.0 merekomendasikan penggunaan Native PHP Short Tag untuk performa terbaik.

| Fitur        | Blade (Laravel) | The Framework (PHP Native)                                |
| :----------- | :-------------- | :-------------------------------------------------------- |
| **Echo**     | `{{ $var }}`    | `<?= $var ?>` (Otomatis XSS Safe jika pakai helper `e()`) |
| **Raw**      | `{!! $var !!}`  | `<?= $var ?>`                                             |
| **If**       | `@if(...)`      | `<?php if(...): ?>`                                       |
| **Else**     | `@else`         | `<?php else: ?>`                                          |
| **End If**   | `@endif`        | `<?php endif; ?>`                                         |
| **Loop**     | `@foreach(...)` | `<?php foreach(...): ?>`                                  |
| **End Loop** | `@endforeach`   | `<?php endforeach; ?>`                                    |

> **Pro Tip:** Gunakan syntax `if ... : endif` (colon syntax) agar HTML Anda tetap terlihat rapi dan tidak penuh kurung kurawal.

---

## Layouts & Inheritance (Moderen)

Alih-alih menggunakan warisan kelas yang rumit, kita menggunakan pola **"Composition"**.

**1. Buat Master Layout (`resources/views/layouts/app.php`)**

```html
<!DOCTYPE html>
<html>
  <head>
    <title><?= $title ?? 'The Framework' ?></title>
  </head>
  <body>
    <nav>...</nav>

    <div class="container">
      <!-- Slot Konten Utama -->
      <?= $content ?>
    </div>

    <footer>...</footer>
  </body>
</html>
```

**2. Buat Halaman (`resources/views/home.php`)**
Tidak perlu `@extends`. Cukup render konten, lalu bungkus dengan layout.

Tapi tunggu, cara paling elegan di PHP Native adalah pola "Header-Footer Include".

**Pola Header-Footer (Disarankan):**

`resources/views/home.php`

```php
<?php include view_path('layouts/header'); ?>

    <h1>Halaman Home</h1>
    <p>Halo dunia!</p>

<?php include view_path('layouts/footer'); ?>
```

---

## Partial Views (Include)

Mencegah duplikasi kode (misal: Card Produk yang dipakai berulang).

```php
<!-- parent.php -->
<div class="grid">
    <?php foreach($products as $product): ?>
        <?php View::partial('components/product-card', ['item' => $product]); ?>
    <?php endforeach; ?>
</div>
```

```html
<!-- components/product-card.php -->
<div class="card">
  <h3><?= $item->name ?></h3>
</div>
```
