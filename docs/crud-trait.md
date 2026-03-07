# 🔄 CRUD Trait (BaseCrudTrait)

**BaseCrudTrait** adalah fitur framework yang memungkinkan Anda mendapatkan **CRUD lengkap** (Create, Read, Update, Delete) di controller hanya dengan beberapa baris kode. Cukup `use` trait ini dan implementasikan 4 abstract method.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Abstract Methods](#abstract-methods)
4. [Generated Methods](#generated-methods)
5. [Kustomisasi](#kustomisasi)
6. [Contoh Lengkap](#contoh-lengkap)

---

## Overview

| Tanpa Trait                    | Dengan Trait                  |
| ------------------------------ | ----------------------------- |
| ~200 baris code per controller | ~20 baris code per controller |
| Copy-paste CRUD logic          | DRY (Don't Repeat Yourself)   |
| Inconsistent error handling    | Unified error handling        |
| Manual flash messages          | Auto flash messages           |

---

## Quick Start

### Step 1: Buat Controller

```php
<?php

namespace TheFramework\Controllers;

use TheFramework\App\Traits\BaseCrudTrait;
use TheFramework\Models\Product;
use TheFramework\Http\Requests\ProductRequest;

class ProductController
{
    use BaseCrudTrait;

    protected function getModel()        { return new Product(); }
    protected function getRequest()      { return new ProductRequest(); }
    protected function getRoutePath(): string { return '/products'; }
    protected function getViewPath(): string  { return 'products'; }
}
```

### Step 2: Buat Routes

```php
// routes/web.php
Router::add('GET',  '/products',              ProductController::class, 'index');
Router::add('GET',  '/products/create',       ProductController::class, 'create');
Router::add('POST', '/products/store',        ProductController::class, 'store');
Router::add('GET',  '/products/{id}',         ProductController::class, 'show');
Router::add('GET',  '/products/{id}/edit',    ProductController::class, 'edit');
Router::add('POST', '/products/{id}/update',  ProductController::class, 'update');
Router::add('POST', '/products/{id}/delete',  ProductController::class, 'destroy');
```

### Step 3: Buat Views

```
resources/views/products/
├── index.blade.php    ← List semua data
├── create.blade.php   ← Form buat baru
├── show.blade.php     ← Detail data
└── edit.blade.php     ← Form edit
```

**Selesai!** Anda sudah punya CRUD lengkap! 🎉

---

## Abstract Methods

Anda **wajib** mengimplementasikan 4 method ini:

| Method           | Return        | Deskripsi                                                  |
| ---------------- | ------------- | ---------------------------------------------------------- |
| `getModel()`     | `Model`       | Instance model yang digunakan                              |
| `getRequest()`   | `FormRequest` | Instance request untuk validasi                            |
| `getRoutePath()` | `string`      | Base path untuk redirect (misal `/products`)               |
| `getViewPath()`  | `string`      | Prefix path view (misal `products` untuk `products.index`) |

### Optional Override

| Method            | Default | Deskripsi                                          |
| ----------------- | ------- | -------------------------------------------------- |
| `getPrimaryKey()` | `'id'`  | Nama primary key (ubah ke `'uid'` jika pakai UUID) |

---

## Generated Methods

Trait otomatis menyediakan **7 method CRUD**:

### `index()` — List Semua Data

```php
// GET /products
// Mengambil semua data → render products.index
```

**View menerima:**

| Variable        | Tipe     | Deskripsi                     |
| --------------- | -------- | ----------------------------- |
| `$title`        | `string` | "List Products"               |
| `$items`        | `array`  | Semua data dari model         |
| `$notification` | `mixed`  | Flash notification (jika ada) |
| `$errors`       | `array`  | Validation errors (jika ada)  |
| `$old`          | `array`  | Old input (jika ada)          |

### `create()` — Form Create

```php
// GET /products/create
// Render products.create (form kosong)
```

### `store()` — Simpan Data Baru

```php
// POST /products/store
// 1. Validasi via getRequest()
// 2. Auto-generate UUID jika primary key = 'uid'
// 3. Insert ke database
// 4. Redirect ke index dengan flash message
```

### `show($id)` — Detail Data

```php
// GET /products/{id}
// Cari data by ID → render products.show
// Auto redirect jika tidak ditemukan
```

### `edit($id)` — Form Edit

```php
// GET /products/{id}/edit
// Cari data by ID → render products.edit
// Auto redirect jika tidak ditemukan
```

### `update($id)` — Update Data

```php
// POST /products/{id}/update
// 1. Validasi via getRequest()
// 2. Update data
// 3. Redirect ke show dengan flash message
```

### `destroy($id)` — Hapus Data

```php
// POST /products/{id}/delete
// 1. Cari data by ID
// 2. Hapus dari database
// 3. Redirect ke index dengan flash message
```

---

## Kustomisasi

### Override Method Tertentu

Anda bisa meng-override method tertentu jika butuh logika khusus:

```php
class ProductController
{
    use BaseCrudTrait;

    // Override store untuk menambahkan upload gambar
    public function store()
    {
        $request = $this->getRequest();
        $data = $request->validated();

        // Custom: Upload gambar
        if (isset($_FILES['image'])) {
            $data['image'] = UploadHandler::handleUploadToWebP(
                $_FILES['image'], '/products', 'prod_'
            );
        }

        $data['uid'] = Helper::uuid();
        $this->getModel()->insert($data);

        return redirect($this->getRoutePath(), 'success', 'Produk berhasil dibuat');
    }

    // Sisanya tetap menggunakan trait
    protected function getModel()        { return new Product(); }
    protected function getRequest()      { return new ProductRequest(); }
    protected function getRoutePath(): string { return '/products'; }
    protected function getViewPath(): string  { return 'products'; }
}
```

### UUID Primary Key

```php
class UserController
{
    use BaseCrudTrait;

    // Ubah primary key ke UUID
    protected function getPrimaryKey(): string
    {
        return 'uid';
    }

    // store() akan otomatis generate UUID
    // ...
}
```

---

## Contoh Lengkap

### Model

```php
// app/Models/Product.php
class Product extends Model
{
    protected $table = 'products';
    protected $fillable = ['uid', 'name', 'price', 'description', 'image'];
}
```

### Request (Validasi)

```php
// app/Http/Requests/ProductRequest.php
class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'required|min:3|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }
}
```

### View (Index)

```blade
{{-- resources/views/products/index.blade.php --}}
@extends('layouts.app')
@section('content')
<h1>{{ $title }}</h1>

@if($notification)
    <div class="alert">{{ $notification }}</div>
@endif

<a href="/products/create">+ Tambah Produk</a>

<table>
    <tr><th>Nama</th><th>Harga</th><th>Aksi</th></tr>
    @foreach($items as $item)
    <tr>
        <td>{{ $item->name }}</td>
        <td>@rupiah($item->price)</td>
        <td>
            <a href="/products/{{ $item->uid }}">Detail</a>
            <a href="/products/{{ $item->uid }}/edit">Edit</a>
        </td>
    </tr>
    @endforeach
</table>
@endsection
```

---

## 🔗 Related Documentation

- [Controllers](controllers.md) — Panduan controller
- [Validation](validation.md) — Form Request validation
- [ORM](orm.md) — Model & database operations
- [Routing](routing.md) — Route definitions

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
