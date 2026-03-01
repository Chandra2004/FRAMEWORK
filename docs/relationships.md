# 🔗 Model Relationships (v5.0.1) - Comprehensive Guide

Sistem ORM The Framework adalah "jembatan" yang sangat kuat untuk menghubungkan antar tabel. Dokumentasi ini merinci setiap aspek relasi, mulai dari definisi dasar hingga optimasi _High-Level_ untuk aplikasi skala enterprise.

---

## 📋 Daftar Isi

- [1. Definisi Relasi Dasar](#1-definisi-relasi-dasar)
- [2. Relasi Terhubung (Many-to-Many)](#2-relasi-terhubung-many-to-many)
- [3. Relasi Melalui (Through)](#3-relasi-melalui-through)
- [4. Relasi Polimordis (Polymorphic)](#4-relasi-polimorfis-polymorphic)
- [5. Querying &amp; Performance (Eager Loading)](#5-querying--performance-eager-loading)
- [6. Relationship Aggregates (Count, Sum, Avg)](#6-relationship-aggregates-count-sum-avg)
- [7. Relationship Existence (has, whereHas)](#7-relationship-existence-has-wherehas)
- [8. Operasi Database (Save, Create, Pivot)](#8-operasi-database-save-create-pivot)
- [9. Fitur Eksklusif Framework](#9-fitur-eksklusif-framework)

---

## 1. Definisi Relasi Dasar

Relasi didefinisikan sebagai method di dalam Model. Framework secara otomatis akan mengonversi method ini menjadi properti dinamis saat diakses.

### One-to-One (hasOne)

Hubungan 1-ke-1. Contoh: User memiliki 1 Profile.

```php
public function profile() {
    return $this->hasOne(Profile::class, 'user_id', 'id');
}
```

### One-to-Many (hasMany)

Hubungan 1-ke-banyak. Contoh: User memiliki banyak Post.

```php
public function posts() {
    return $this->hasMany(Post::class, 'user_id');
}
```

### Inverse (belongsTo)

Merujuk kembali ke orang tua (sisi yang memiliki Foreign Key).

```php
public function author() {
    return $this->belongsTo(User::class, 'user_id')->withDefault([
        'name' => 'Anonymous' // Objek default jika relasi NULL
    ]);
}
```

---

## 2. Relasi Terhubung (Many-to-Many)

Menggunakan tabel pivot (perantara).

### Definisi Dasar

```php
public function roles() {
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
}
```

### Kustomisasi Pivot

Anda dapat mengambil kolom tambahan dari tabel pivot atau mengganti nama "pivot" menjadi yang lain.

```php
return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('expired_at', 'status')
            ->withTimestamps()
            ->as('membership'); // Akses: $user->roles[0]->membership->status
```

---

## 3. Relasi Melalui (Through)

### Has-Many-Through

Mengakses data di tabel ketiga melalui tabel kedua.
**Country → User → Post** (Cari posts untuk suatu Negara).

```php
public function posts() {
    return $this->hasManyThrough(Post::class, User::class, 'country_id', 'user_id');
}
```

---

## 4. Relasi Polimorfis (Polymorphic)

Memungkinkan satu tabel terkait dengan banyak Model berbeda hanya dengan satu set kolom.

### Morph One-to-Many

Contoh: **Comment** bisa milik **Post** atau **Video**.

```php
// Model Comment
public function commentable() {
    return $this->morphTo(); // Otomatis baca commentable_id & commentable_type
}

// Model Post
public function comments() {
    return $this->morphMany(Comment::class, 'commentable');
}
```

### Morph Many-to-Many

Contoh: **Tag** yang bisa dipasang di **Post** dan **Product**.

```php
// Model Post
public function tags() {
    return $this->morphToMany(Tag::class, 'taggable');
}
```

---

## 5. Querying & Performance (Eager Loading)

Gunakan `with()` untuk mencegah masalah **N+1 Query** yang sering merusak performa database.

### Eager Loading Dasar

```php
$posts = Post::with(['author', 'comments.user'])->get();
```

### Constrained Eager Loading

Membatasi data relasi yang ditarik.

```php
$users = User::with(['posts' => function($query) {
    $query->where('is_published', true)->orderBy('views', 'DESC');
}])->get();
```

### Lazy Eager Loading

Tarik data relasi SETELAH model berhasil didapatkan.

```php
$users = User::all();
$users->loadRelations($users, ['profile', 'posts']); // Eksklusif The Framework
```

---

## 6. Relationship Aggregates (Count, Sum, Avg)

Tarik statistik relasi tanpa harus menarik seluruh datanya secara manual.

```php
$users = User::withCount('posts')
             ->withSum('orders', 'total_amount')
             ->withMax('posts', 'created_at')
             ->get();

echo $users[0]->posts_count;
echo $users[0]->orders_sum_total_amount;
```

---

## 7. Relationship Existence (has, whereHas)

Memfilter hasil berdasarkan keberadaan atau kondisi di tabel relasinya.

```php
// Cari user yang punya minimal 3 post
$users = User::has('posts', '>=', 3)->get();

// Cari user yang punya post mengandung kata 'AI'
$users = User::whereHas('posts', function($q) {
    $q->where('title', 'LIKE', '%AI%');
})->get();

// Kebalikannya: User yang TIDAK punya post
$users = User::doesntHave('posts')->get();
```

---

## 8. Operasi Database (Save, Create, Pivot)

### Memasukkan Data Relasi

```php
$user = User::find(1);

// Create (Langsung simpan ke DB)
$user->posts()->create(['title' => 'Halo World']);

// Save (Simpan instance model)
$post = new Post(['title' => 'Post Baru']);
$user->posts()->save($post);
```

### Pivot Management (attach, sync, toggle)

Hanya untuk relasi `belongsToMany`.

```php
// Tambah ID (Tanpa menghapus yang ada)
$user->roles()->attach(1, ['status' => 'active']);

// Sinkronisasi (Hanya ID ini yang tersisa di DB)
$user->roles()->sync([1, 2, 5]);

// Toggle (Jika ada hapus, jika tidak ada tambah)
$user->roles()->toggle([1, 3]);
```

---

## 9. Fitur Eksklusif Framework

### Touching Parent Timestamps

Otomatis mengupdate `updated_at` tabel orang tua saat tabel anak berubah.

```php
// Di Model Comment
protected $touches = ['post']; // Saat comment diedit, updated_at milik Post ikut berubah

public function post() {
    return $this->belongsTo(Post::class);
}
```

### ofEach() — Smart Partitioning (Premium)

Fitur unik untuk membatasi jumlah item per grup. Sangat berguna untuk landing page.

```php
// Ambil setiap User, dan UNTUK SETIAP USER, hanya ambil 3 post terbarunya.
$users = User::with(['posts' => fn($q) => $q->latest()->ofEach(3)])->get();
```

### Specialized hasOne of Many

```php
// Di Model User - Relasi hasOne yang mengambil data terbaru dari hasMany
public function latest_post() {
    return $this->latestOfMany('id');
}
```

---

## 💡 Best Practices

1. **Gunakan Eager Loading** (`with`) hampir selalu jika Anda akan melakukan looping hasil relasi di View.
2. **Gunakan `whereHas`** alih-alih menarik ribuan data lalu memfilternya via PHP (biarkan database yang bekerja).
3. **Hati-hati dengan `touches`** pada relasi yang sangat besar karena bisa memicu beban update berantai.

---

<div align="center">

**Mastering Relationships means Mastering Data Flow.**

[Kembali ke Dokumentasi Utama](README.md)

</div>
