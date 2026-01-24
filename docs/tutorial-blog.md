# 📝 Tutorial: Membangun Blog Sederhana

Tutorial ini akan memandu Anda membuat aplikasi Blog fungsional (CRUD) menggunakan The Framework v4.0. Anda akan belajar cara menggunakan Migrasi, Model, Controller, View, dan Validasi.

---

## 📋 Daftar Isi

1.  [Persiapan Proyek](#persiapan-proyek)
2.  [Membuat Database & Migrasi](#membuat-database--migrasi)
3.  [Membuat Model](#membuat-model)
4.  [Membuat Controller & Rute](#membuat-controller--rute)
5.  [Membuat View (Tampilan)](#membuat-view-tampilan)
6.  [Menyimpan Data (Create)](#menyimpan-data-create)
7.  [Kesimpulan](#kesimpulan)

---

## Persiapan Proyek

Pastikan Anda sudah menginstal framework dan menjalankan server lokal.

```bash
composer install
php artisan setup
php artisan serve
```

Buka `http://localhost:8080`.

---

## Membuat Database & Migrasi

Kita butuh tabel `posts` untuk menyimpan artikel.

1.  Buat file migrasi:

    ```bash
    php artisan make:migration CreatePostsTable
    ```

2.  Buka file migrasi baru di `database/migrations/`. Edit method `up()`:

    ```php
    public function up() {
        Schema::create('posts', function($table) {
            $table->id();
            $table->string('title', 255);
            $table->text('content');
            $table->string('author', 100);
            $table->timestamps();
        });
    }
    ```

3.  Jalankan migrasi:
    ```bash
    php artisan migrate
    ```

---

## Membuat Model

Buat Model untuk berinteraksi dengan tabel `posts`.

```bash
php artisan make:model Post
```

File: `app/Models/Post.php`

```php
<?php
namespace App\Models;
use TheFramework\Database\Model;

class Post extends Model {
    protected $table = 'posts';
    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = ['title', 'content', 'author'];
}
```

---

## Membuat Controller & Rute

Kita butuh controller untuk logika.

```bash
php artisan make:controller BlogController
```

File: `app/Controllers/BlogController.php`

```php
<?php
namespace App\Controllers;
use TheFramework\Core\View;
use App\Models\Post;

class BlogController {
    // Tampilkan semua postingan
    public function index() {
        $posts = Post::orderBy('created_at', 'DESC')->get();
        return View::render('blog/index', ['posts' => $posts]);
    }

    // Tampilkan form buat baru
    public function create() {
        return View::render('blog/create');
    }
}
```

Daftarkan rute di `routes/web.php`:

```php
use App\Controllers\BlogController;

Router::add('GET', '/blog', 'BlogController@index');
Router::add('GET', '/blog/create', 'BlogController@create');
Router::add('POST', '/blog/store', 'BlogController@store');
```

---

## Membuat View (Tampilan)

Buat folder `resources/views/blog/`.

### 1. Halaman Index (`blog/index.php`)

```html
<h1>Daftar Artikel</h1>
<a href="/blog/create">Tulis Artikel Baru</a>

<hr />

<?php foreach ($posts as $post): ?>
<article>
  <h2><?= Helper::e($post->title) ?></h2>
  <small>Oleh: <?= Helper::e($post->author) ?></small>
  <p><?= substr(Helper::e($post->content), 0, 100) ?>...</p>
</article>
<?php endforeach; ?>
```

### 2. Halaman Create (`blog/create.php`)

```html
<h1>Tulis Artikel</h1>

<!-- Tampilkan Pesan Error (Validasi) -->
<?php if ($errors = Helper::session_get('errors')): ?>
<div style="color: red;">Ada input yang salah!</div>
<?php endif; ?>

<form action="/blog/store" method="POST">
  <!-- Token Wajib -->
  <input
    type="hidden"
    name="_token"
    value="<?= Helper::generateCsrfToken() ?>"
  />

  <div>
    <label>Judul</label><br />
    <input type="text" name="title" required />
  </div>

  <div>
    <label>Konten</label><br />
    <textarea name="content" rows="5" required></textarea>
  </div>

  <div>
    <label>Penulis</label><br />
    <input type="text" name="author" required />
  </div>

  <button type="submit">Terbitkan</button>
</form>
```

---

## Menyimpan Data (Create)

Tambahkan method `store` di `BlogController.php`.

```php
use TheFramework\Helpers\Helper;
use TheFramework\Helpers\Validator;

public function store() {
    $input = Helper::request()->all();

    // 1. Validasi
    $errors = Validator::validate($input, [
        'title'   => 'required|min:5',
        'content' => 'required',
        'author'  => 'required'
    ]);

    if (!empty($errors)) {
        Helper::set_flash('errors', $errors);
        Helper::redirect('/blog/create');
        return;
    }

    // 2. Simpan ke Database
    Post::create([
        'title'   => $input['title'],
        'content' => $input['content'],
        'author'  => $input['author']
    ]);

    // 3. Redirect
    Helper::set_flash('success', 'Artikel berhasil diterbitkan!');
    Helper::redirect('/blog');
}
```

---

## Kesimpulan

Selamat! Anda baru saja membangun fitur blog lengkap dengan database, validasi, dan keamanan (CSRF) menggunakan The Framework.

Langkah selanjutnya:

- Tambahkan fitur **Edit** dan **Delete**.
- Tambahkan autentikasi (Login Admin).
- Percantik tampilan dengan CSS framework (Bootstrap/Tailwind).
