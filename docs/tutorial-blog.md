# Tutorial: Membangun Blog Sederhana

Panduan ini akan memandu Anda mengenal **The Framework** dengan cara mempraktikkan langsung pembuatan aplikasi Blog sederhana (CRUD Postingan).

**Estimasi Waktu:** 10-15 Menit.

---

## 1. Persiapan Database

Pastikan Anda sudah mengonfigurasi `.env` dengan database Anda dan menjalankannya.

```bash
php artisan migrate
```

Jika Anda belum punya file `.env`, jalankan `php artisan setup`.

---

## 2. Membuat Model & Migrasi

Kita akan membuat entitas `Post` (Artikel). Gunakan Artisan untuk membuat Model sekaligus file Migrasinya (flag `-m`).

```bash
php artisan make:model Post -m
```

### Edit File Migrasi

Buka file migrasi baru di `database/migrations/YYYY_MM_DD_xxxxxx_CreatePostsTable.php`. Tambahkan kolom `title` dan `content`.

```php
public function up()
{
    Schema::create('posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('content'); // Isi artikel
        $table->timestamps();
    });
}
```

Jalankan migrasi agar tabel terbuat di database:

```bash
php artisan migrate
```

### Edit Model

Buka `app/Models/Post.php` dan izinkan kolom `title` dan `content` untuk diisi massal (`$fillable`).

```php
class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'title',
        'content'
    ];
}
```

---

## 3. Membuat Controller

Kita akan membuat Controller untuk menangani logika Blog. Gunakan flag `-r` untuk membuat Resource Controller (otomatis method index, create, store, dll).

```bash
php artisan make:controller PostController -r --model=Post
```

File `app/Http/Controllers/PostController.php` telah dibuat.

### Edit Method `index()`

Mari tampilkan daftar postingan.

```php
public function index()
{
    // Ambil semua data postingan dari database (urut terbaru)
    // Note: Jika belum ada method latest(), pakai Post::all() dulu atau query builder
    $posts = Post::all();

    return View::render('posts.index', [
        'posts' => $posts,
        'title' => 'Blog Saya'
    ]);
}
```

---

## 4. Membuat View

Buat folder baru `resources/views/posts` dan file `index.blade.php`.

```html
<!-- resources/views/posts/index.blade.php -->
@extends('template.layout') @section('main-content')
<div class="max-w-4xl mx-auto py-12 px-4">
  <div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-100">Blog Saya</h1>
    <a
      href="{{ url('posts/create') }}"
      class="bg-blue-600 px-4 py-2 rounded text-white"
      >Buat Postingan</a
    >
  </div>

  <div class="grid gap-6">
    @foreach($posts as $post)
    <article
      class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700"
    >
      <h2 class="text-xl font-bold text-cyan-400 mb-2">{{ $post['title'] }}</h2>
      <p class="text-gray-300 mb-4">
        {{ substr($post['content'], 0, 100) }}...
      </p>
      <div class="text-sm text-gray-500">
        Diposting pada: {{ $post['created_at'] }}
      </div>
    </article>
    @endforeach @if(empty($posts))
    <p class="text-gray-400 text-center">Belum ada postingan.</p>
    @endif
  </div>
</div>
@endsection
```

---

## 5. Mendaftarkan Route

Buka `routes/web.php` dan daftarkan controller kita.

```php
use TheFramework\Http\Controllers\PostController;

// Route Resource otomatis mendaftarkan:
// GET /posts (index), POST /posts (store), etc.
Router::resource('posts', PostController::class);
```

---

## 6. Uji Coba

Jalankan server:

```bash
php artisan serve
```

Buka browser di `http://localhost:8080/posts`.
Selamat! Anda telah memiliki halaman Blog dinamis yang mengambil data dari database.

---

### Langkah Selanjutnya?

Coba lengkapi fitur **Create** (Buat Postingan):

1. Isi method `create` di Controller untuk render form.
2. Buat view `posts/create.blade.php`.
3. Isi method `store` di Controller untuk simpan data (`Post::create($_POST)`).
4. Redirect kembali ke index.

Happy Coding! 🚀
