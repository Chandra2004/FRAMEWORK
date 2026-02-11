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
namespace TheFramework\Models;
use TheFramework\App\Model;

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
namespace TheFramework\Controllers;
use TheFramework\Helpers\Helper;
use TheFramework\Models\Post;

class BlogController {
    // Tampilkan semua postingan
    public function index() {
        $posts = Post::orderBy('created_at', 'DESC')->get();
        return view('blog/index', ['posts' => $posts]);
    }

    // Tampilkan form buat baru
    public function create() {
        return view('blog/create');
    }
}
```

Daftarkan rute di `routes/web.php`:

```php
use TheFramework\Http\Controllers\BlogController;

Router::add('GET', '/blog', BlogController::class, 'index');
Router::add('GET', '/blog/create', BlogController::class, 'create');
Router::add('POST', '/blog/store', BlogController::class, 'store');
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
use TheFramework\App\Validator;

public function store() {
    // 1. Validasi
    $validator = new Validator();
    $isValid = $validator->validate($_POST, [
        'title'   => 'required|min:5',
        'content' => 'required',
        'author'  => 'required'
    ]);

    if (!$isValid) {
        $errors = $validator->errors();
        // Flash errors dan redirect
        Helper::set_flash('errors', $errors);
        Helper::set_flash('old', $_POST);
        return Helper::redirect('/blog/create');
    }

    // 2. Simpan ke Database (Active Record)
    Post::create([
        'title'   => $_POST['title'],
        'content' => $_POST['content'],
        'author'  => $_POST['author']
    ]);

    // 3. Redirect
    Helper::set_flash('success', 'Artikel berhasil diterbitkan!');
    Helper::redirect('/blog');
}
```

---

## Mengedit Data (Update)

Untuk mengupdate data, kita butuh rute baru dan method di controller.

1. **Rute** (`routes/web.php`):

   ```php
   Router::add('GET', '/blog/{id}/edit', BlogController::class, 'edit');
   Router::add('POST', '/blog/{id}/update', BlogController::class, 'update');
   ```

2. **Controller Method** (`BlogController.php`):

   ```php
   public function edit($id) {
       $post = Post::findOrFail($id);
       return View::render('blog/edit', ['post' => $post]);
   }

   public function update($id) {
       $post = Post::find($id);
       if (!$post) abort(404);

       // Active Record Update
       $post->title = $_POST['title'];
       $post->content = $_POST['content'];
       $post->save();

       Helper::set_flash('success', 'Artikel berhasil diupdate!');
       Helper::redirect('/blog');
   }
   ```

---

## Menghapus Data (Delete)

Gunakan _Method Spoofing_ untuk penghapusan yang aman.

1. **Rute** (`routes/web.php`):

   ```php
   Router::add('POST', '/blog/{id}/delete', BlogController::class, 'destroy');
   ```

2. **Controller Method** (`BlogController.php`):

   ```php
   public function destroy($id) {
       $post = Post::findOrFail($id);
       $post->delete();

       Helper::redirect('/blog', 'danger', 'Artikel telah dihapus.');
   }
   ```

3. **Tombol Hapus di View** (`blog/index.php`):
   ```html
   <form
     action="/blog/<?= $post->id ?>/delete"
     method="POST"
     style="display:inline;"
   >
     <input
       type="hidden"
       name="_token"
       value="<?= Helper::generateCsrfToken() ?>"
     />
     <button type="submit" onclick="return confirm('Yakin?')">Hapus</button>
   </form>
   ```

---

## Kesimpulan

Selamat! Anda baru saja membangun aplikasi Blog fungsional dengan fitur CRUD lengkap. Framework ini menangani urusan berat seperti routing, database, dan security, sehingga Anda bisa fokus pada logika aplikasi.

Langkah selanjutnya:

- Tambahkan autentikasi (Login Admin).
- Implementasikan **File Upload** untuk gambar thumbnail artikel.
- Percantik tampilan dengan CSS atau framework seperti Tailwind.
