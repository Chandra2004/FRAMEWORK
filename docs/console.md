# 🖥️ Console Engine (BaseCommand & Commands)

Dokumentasi mendalam tentang **Console Engine** — arsitektur internal Artisan CLI, cara membuat custom command, dan referensi lengkap seluruh **41 built-in commands**.

> **📖 Catatan:** Untuk panduan penggunaan Artisan CLI, lihat [Artisan CLI](artisan.md). Halaman ini fokus pada **arsitektur internal engine** dan referensi lengkap commands.

---

## 📋 Daftar Isi

1. [BaseCommand Architecture](#basecommand-architecture)
2. [Membuat Custom Command](#membuat-custom-command)
3. [Styling Output](#styling-output)
4. [User Interaction](#user-interaction)
5. [Referensi 41 Commands](#referensi-41-commands)
6. [Stubs System](#stubs-system)

---

## BaseCommand Architecture

Semua command Artisan meng-extend `BaseCommand` yang menyediakan:

```
CommandInterface (getName, getDescription, run)
        │
   BaseCommand (abstract)
   ├── Styling: info(), success(), warn(), error(), line(), comment()
   ├── Tables: table(headers, rows) — premium Unicode table
   ├── Input: ask(), confirm()
   ├── Terminal: clear()
   └── ANSI Colors: 8 warna + bold style
        │
   ├── MakeControllerCommand
   ├── MigrateCommand
   ├── ServeCommand
   └── ... (41 commands total)
```

### CommandInterface

```php
interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function run(array $args): void;
}
```

---

## Membuat Custom Command

### Step 1: Buat Command Class

```php
<?php
// app/Console/Commands/SendNewsletterCommand.php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class SendNewsletterCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'newsletter:send';
    }

    public function getDescription(): string
    {
        return 'Kirim newsletter ke semua subscriber';
    }

    public function handle(array $args): void
    {
        $this->info('Memulai pengiriman newsletter...');

        // Parse arguments
        $limit = $args['--limit'] ?? 100;
        $dryRun = isset($args['--dry-run']);

        if ($dryRun) {
            $this->warn('DRY RUN MODE — tidak ada email yang dikirim');
        }

        // Simulasi proses
        $subscribers = ['user1@mail.com', 'user2@mail.com'];

        $this->table(
            ['No', 'Email', 'Status'],
            array_map(fn($email, $i) => [$i + 1, $email, '✅ Sent'], $subscribers, array_keys($subscribers))
        );

        $this->success("Newsletter dikirim ke " . count($subscribers) . " subscriber!");
    }
}
```

### Step 2: Register di Artisan

Command akan otomatis ter-register jika berada di folder `app/Console/Commands/`.

### Step 3: Jalankan

```bash
php artisan newsletter:send
php artisan newsletter:send --limit=50
php artisan newsletter:send --dry-run
```

---

## Styling Output

### Message Types

```php
$this->info('Informasi umum');        // 🔵 Label biru
$this->success('Operasi berhasil');   // 🟢 Label hijau
$this->warn('Peringatan');            // 🟡 Label kuning
$this->error('Terjadi error');        // 🔴 Label merah
$this->comment('Komentar');           // ⚫ Teks abu-abu
$this->line('Teks biasa');            // Dengan warna opsional
```

### Premium Tables

```php
$this->table(
    ['ID', 'Name', 'Email', 'Role'],
    [
        [1, 'Chandra', 'admin@mail.com', 'Admin'],
        [2, 'User', 'user@mail.com', 'Member'],
    ]
);

// Output:
// ┌────┬─────────┬─────────────────┬────────┐
// │ ID │ Name    │ Email           │ Role   │
// ├────┼─────────┼─────────────────┼────────┤
// │ 1  │ Chandra │ admin@mail.com  │ Admin  │
// │ 2  │ User    │ user@mail.com   │ Member │
// └────┴─────────┴─────────────────┴────────┘
```

### ANSI Colors

```php
$this->line('Blue text', self::COLOR_BLUE);
$this->line('Green text', self::COLOR_GREEN);
$this->line('Red text', self::COLOR_RED);
$this->line('Yellow text', self::COLOR_YELLOW);
$this->line('Magenta text', self::COLOR_MAGENTA);
$this->line('Cyan text', self::COLOR_CYAN);
$this->line('Gray text', self::COLOR_GRAY);
```

---

## User Interaction

### Ask (Input)

```php
// Input keyboard
$name = $this->ask('Nama proyek');               // Tanpa default
$port = $this->ask('Port server', '8080');        // Dengan default

// User cukup tekan Enter untuk pakai default
```

### Confirm (Y/N)

```php
if ($this->confirm('Lanjutkan operasi?', true)) {
    // User jawab Y (atau Enter karena default true)
    $this->info('Melanjutkan...');
} else {
    $this->warn('Dibatalkan');
}
```

### Clear Terminal

```php
$this->clear(); // Bersihkan terminal
```

---

## Referensi 41 Commands

### 🚀 Application Lifecycle

| Command                    | Deskripsi                                                              |
| -------------------------- | ---------------------------------------------------------------------- |
| `php artisan setup`        | **Setup wizard** — buat `.env`, generate APP_KEY, konfigurasi keamanan |
| `php artisan serve`        | Jalankan development server (default: `127.0.0.1:8080`)                |
| `php artisan down`         | Aktifkan maintenance mode                                              |
| `php artisan up`           | Nonaktifkan maintenance mode                                           |
| `php artisan key:generate` | Generate APP_KEY baru (Base64 32-byte random)                          |
| `php artisan env`          | Tampilkan/edit environment variables                                   |
| `php artisan test`         | Jalankan PHPUnit test suite                                            |
| `php artisan tinker`       | Jalankan interactive REPL                                              |

### 📦 Code Generators (`make:*`)

| Command                                         | File yang Dibuat                                                                                          |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `php artisan make:controller UserController`    | `app/Http/Controllers/UserController.php` (Gunakan opsi `-r` atau `--resource` untuk controller resource) |
| `php artisan make:model User`                   | `app/Models/User.php` (Gunakan opsi `-m` atau `--migration` untuk sekaligus membuat migrasi)              |
| `php artisan make:crud Product`                 | **7+ files** — Model, Controller, Request, Views, Routes, Repository, Service                             |
| `php artisan make:migration create_users_table` | `app/Database/Migrations/xxx_create_users_table.php`                                                      |
| `php artisan make:request StoreUserRequest`     | `app/Http/Requests/StoreUserRequest.php`                                                                  |
| `php artisan make:middleware AdminMiddleware`   | `app/Middleware/AdminMiddleware.php`                                                                      |
| `php artisan make:seeder UsersSeeder`           | `app/Database/Seeders/UsersSeeder.php`                                                                    |
| `php artisan make:service UserService`          | `app/Services/UserService.php`                                                                            |
| `php artisan make:repository UserRepository`    | `app/Repositories/UserRepository.php`                                                                     |
| `php artisan make:provider PaymentProvider`     | `app/Providers/PaymentProvider.php`                                                                       |
| `php artisan make:job ProcessOrderJob`          | `app/Jobs/ProcessOrderJob.php`                                                                            |
| `php artisan make:mail WelcomeMail`             | `app/Mail/WelcomeMail.php`                                                                                |
| `php artisan make:test UserTest`                | `tests/Feature/UserTest.php`                                                                              |
| `php artisan make:view users`                   | `resources/views/users/index.blade.php` (+ layout)                                                        |

### 🗄️ Database & Migrations

| Command                        | Deskripsi                        |
| ------------------------------ | -------------------------------- |
| `php artisan migrate`          | Jalankan semua migrasi pending   |
| `php artisan migrate:status`   | Tampilkan status migrasi         |
| `php artisan migrate:rollback` | Rollback batch terakhir          |
| `php artisan migrate:reset`    | Rollback semua migrasi           |
| `php artisan migrate:refresh`  | Rollback semua + migrate ulang   |
| `php artisan migrate:fresh`    | Drop semua tabel + migrate ulang |
| `php artisan db:seed`          | Jalankan database seeders        |

### ⚡ Cache & Performance

| Command                      | Deskripsi                                    |
| ---------------------------- | -------------------------------------------- |
| `php artisan cache:clear`    | Hapus semua cache (file, ratelimit, storage) |
| `php artisan config:cache`   | Cache konfigurasi (.env → PHP array)         |
| `php artisan config:clear`   | Hapus config cache                           |
| `php artisan route:cache`    | Cache route definitions                      |
| `php artisan route:clear`    | Hapus route cache                            |
| `php artisan route:list`     | Tampilkan semua registered routes            |
| `php artisan view:clear`     | Hapus compiled Blade cache                   |
| `php artisan optimize`       | Run semua cache commands sekaligus           |
| `php artisan optimize:clear` | Clear semua cache sekaligus                  |

### 📦 Assets & Storage

| Command                     | Deskripsi                                          |
| --------------------------- | -------------------------------------------------- |
| `php artisan asset:publish` | Publish asset ke public directory                  |
| `php artisan storage:link`  | Buat symlink `public/storage → storage/app/public` |

### 📋 Queue

| Command                  | Deskripsi             |
| ------------------------ | --------------------- |
| `php artisan queue:work` | Jalankan queue worker |

---

## Stubs System

Commands `make:*` menggunakan **Stubs** (template file) untuk generate code. Stubs berada di:

```text
app/Console/Stubs/
├── controller.crud.stub        (Template Controller untuk operasi CRUD otomatis)
├── controller.resource.stub    (Template Controller standard dengan method CRUD kosong / REST)
├── controller.stub             (Template Controller dasar kosong)
├── job.stub                    (Template Job / Background processing)
├── mail.stub                   (Template Email Mailable)
├── middleware.stub             (Template HTTP Middleware)
├── migration.stub              (Template Database Migration)
├── model.stub                  (Template Class Model)
├── provider.stub               (Template Service Provider)
├── repository.crud.stub        (Template Repository dengan Base CRUD logic)
├── repository.stub             (Template Repository pattern kosong)
├── request.form.stub           (Template Validasi Form Request)
├── seeder.stub                 (Template Database Seeder)
├── service.make.stub           (Template Service dengan business logic pattern)
├── service.stub                (Template Service class kosong)
├── test.stub                   (Template PHPUnit Test)
├── view.create.stub            (Template Form Create UI)
├── view.edit.stub              (Template Form Edit UI)
├── view.index.stub             (Template List / Data Table UI)
└── view.show.stub              (Template Detail / View Data UI)
```

### Cara Kerja Stubs

```
1. Command membaca stub file
2. Replace placeholder:
   - {{classname}} → UserController
   - {{model}} → User
   - {{table}} → users
   - {{namespace}} → TheFramework\Controllers
3. Tulis file hasil ke lokasi yang tepat
```

### Contoh Stub

```php
// controller.stub
<?php

namespace TheFramework\Controllers;

class {{classname}}
{
    public function index()
    {
        return view('{{viewpath}}.index');
    }
}
```

---

## `make:crud` — Generator CRUD Lengkap

Command paling powerful. **Satu perintah, 7+ files dibuatkan:**

```bash
php artisan make:crud Product
```

**Files yang di-generate:**

| #   | File         | Path                                         |
| --- | ------------ | -------------------------------------------- |
| 1   | Model        | `app/Models/Product.php`                     |
| 2   | Controller   | `app/Http/Controllers/ProductController.php` |
| 3   | Form Request | `app/Http/Requests/ProductRequest.php`       |
| 4   | Repository   | `app/Repositories/ProductRepository.php`     |
| 5   | Service      | `app/Services/ProductService.php`            |
| 6   | View: Index  | `resources/views/products/index.blade.php`   |
| 7   | View: Create | `resources/views/products/create.blade.php`  |
| 8   | View: Edit   | `resources/views/products/edit.blade.php`    |
| 9   | View: Show   | `resources/views/products/show.blade.php`    |
| 10  | Routes       | Auto-append ke `routes/web.php`              |

---

## 🔗 Related Documentation

- [Artisan CLI](artisan.md) — Panduan penggunaan Artisan
- [CRUD Trait](crud-trait.md) — Auto CRUD via Trait
- [Migrations](migrations.md) — Database version control
- [Testing](testing-guide.md) — PHPUnit integration

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
