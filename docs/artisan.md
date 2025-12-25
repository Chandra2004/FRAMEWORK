# Artisan CLI Guide

Artisan adalah Command Line Interface (CLI) bawaan The Framework untuk mempercepat development.

## Daftar Perintah Utama

### 1. Model & Database

**Membuat Model Baru:**

```bash
php artisan make:model Product
```

**Membuat Model + Migrasi Sekaligus:**
Gunakan flag `-m` atau `--migration`.

```bash
php artisan make:model Product -m
```

Ini akan membuat:

- `app/Models/Product.php`
- `database/migrations/YYYY_MM_DD_xxxxxx_CreateProductsTable.php`

**Membuat Migrasi Manual:**

```bash
php artisan make:migration CreateCategoriesTable
```

**Menjalankan Migrasi:**

```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh
```

### 2. Controller & Routing

**Membuat Controller Kosong:**

```bash
php artisan make:controller HomeController
```

**Membuat Resource Controller (CRUD):**
Gunakan flag `-r`.

```bash
php artisan make:controller ProductController -r
```

**Membuat Resource Controller dengan Model:**
Gunakan flag `--model`.

```bash
php artisan make:controller ProductController -r --model=Product
```

Ini akan menyiapkan method `index`, `create`, `store`, dll dengan referensi ke model `Product`.

### 3. Queue & Jobs

**Membuat Job:**

```bash
php artisan make:job SendEmailJob
```

**Menjalankan Worker:**

```bash
php artisan queue:work
```

Atau spesifik queue: `php artisan queue:work queue=emails`

### 4. Lain-lain

**Membuat Request Validation:**

```bash
php artisan make:request StoreProductRequest
```

**Membuat Middleware:**

```bash
php artisan make:middleware CheckAdmin
```

**Testing:**

```bash
php artisan test
```

**Server:**

```bash
php artisan serve
```
