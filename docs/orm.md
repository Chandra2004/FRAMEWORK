# 🔗 ORM & Models

The Framework menggunakan Active Record pattern yang mirip dengan Laravel Eloquent untuk berinteraksi dengan database.

---

## Creating Models

### Generate Model

```bash
php artisan make:model Post
```

Generated: `app/Models/Post.php`

```php
<?php

namespace TheFramework\Models;

use TheFramework\App\Model;

class Post extends Model
{
    protected $table = 'posts';  // Optional, auto-detected
    protected $primaryKey = 'id';  // Default: 'id'

    // Mass assignment protection
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'published_at'
    ];

    // Hidden from JSON
    protected $hidden = [
        'deleted_at'
    ];

    // Enable timestamps (created_at, updated_at)
    protected $timestamps = true;
}
```

---

## Basic Operations (CRUD)

### Create

```php
// Method 1: Using create()
$post = Post::create([
    'title' => 'My First Post',
    'content' => 'Hello World',
    'user_id' => 1
]);

// Method 2: Using insert()
$postId = Post::insert([
    'title' => 'Another Post',
    'content' => 'Content...'
]);
```

### Read

```php
// Get all
$posts = Post::all();

// Find by ID
$post = Post::find(1);

// Find or fail (throws 404)
$post = Post::findOrFail(1);

// Where clause
$posts = Post::where('user_id', '=', 1)->get();
$post = Post::where('title', 'like', '%framework%')->first();

// Multiple where
$posts = Post::query()
    ->where('published', '=', true)
    ->where('user_id', '=', 1)
    ->get();
```

### Update

```php
// Method 1: Active Record (Instance Update)
$post = Post::find(1);
$post->title = 'Updated Title';
$post->content = 'New Content';
$post->save(); // Detects update automatically

// Method 2: Batch Update
Post::where('published', false)
    ->update(['published' => true]);
```

### Delete

```php
// Method 1: Active Record (Instance Delete)
$post = Post::find(1);
$post->delete();

// Method 2: Delete by Query
Post::where('user_id', 1)->delete();
```

---

## Query Builder Methods

### Where Clauses

```php
// Basic where
Post::where('id', '=', 1)->first();
Post::where('title', 'like', '%framework%')->get();

// Where In
Post::whereIn('id', [1, 2, 3])->get();

// Where Not In
Post::whereNotIn('user_id', [5, 6])->get();

// Raw where
Post::whereRaw('YEAR(created_at) = ?', [2026])->get();
```

### Ordering

```php
// Order by single column
Post::orderBy('created_at', 'DESC')->get();

// Order by multiple
Post::query()
    ->orderBy('published', 'DESC')
    ->orderBy('created_at', 'DESC')
    ->get();
```

### Limit & Offset

```php
// Limit
Post::limit(10)->get();

// Limit with offset
Post::limit(10, 20)->get();  // Skip 20, take 10
```

### Pagination

```php
$posts = Post::paginate(15, 1);  // 15 per page, page 1

// Access pagination data
echo "Total: " . $posts['total'];
echo "Current Page: " . $posts['current_page'];
echo "Last Page: " . $posts['last_page'];

// Loop results
foreach ($posts['data'] as $post) {
    echo $post->title;
}
```

### Counting

```php
$count = Post::where('published', '=', true)->count();
```

### Pluck (Get Column Values)

```php
$titles = Post::query()->pluck('title');
// Returns: ['Title 1', 'Title 2', 'Title 3']

$ids = Post::where('published', true)->pluck('id');
// Returns: [1, 2, 3, 4, 5]
```

---

## Advanced Active Record

// Create via Relation (Auto sets foreign key)
$user = User::find(1);
$user->posts()->create([
'title' => 'New Post',
'content' => 'Content via relation...'
]); // Sets user_id = 1 automatically

// Mass Fill (Protected by $fillable)
$post = new Post();
$post->fill([
    'title' => 'Title',
    'user_id' => 1,
    'sensitive_data' => 'secret' // Ignored if not in $fillable
]);
$post->save();

---

## Relationships

### One-to-Many (hasMany)

```php
// User model
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts;  // Magic property
```

### Belongs To (belongsTo)

```php
// Post model
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// Usage
$post = Post::find(1);
$author = $post->user;
```

### One-to-One (hasOne)

```php
// User model
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$profile = $user->profile;
```

### Many-to-Many (belongsToMany)

```php
// User model
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,        // Related model
            'user_roles',       // Pivot table
            'user_id',          // Foreign key
            'role_id'           // Related key
        );
    }
}

// Usage
$user = User::find(1);
$roles = $user->roles;
```

📖 [Full Relationships Guide](relationships.md)

---

## Eager Loading (Prevent N+1)

### Problem: N+1 Query

```php
// ❌ BAD: N+1 query problem
$posts = Post::all();  // 1 query

foreach ($posts as $post) {
    echo $post->user->name;  // N queries (1 per post)
}
// Total: 1 + N queries
```

### Solution: Eager Loading

```php
// ✅ GOOD: Only 2 queries
$posts = Post::with('user')->get();  // 2 queries: posts + users

foreach ($posts as $post) {
    echo $post->user->name;  // No additional query
}
// Total: 2 queries
```

### Nested Eager Loading

```php
// Load posts with comments and comment authors
$posts = Post::with(['comments', 'comments.user'])->get();

// Or using dot notation
$posts = Post::with('comments.user')->get();
```

### Eager Loading with Constraints

```php
$users = User::with([
    'posts' => function($query) {
        $query->where('published', true)
              ->orderBy('created_at', 'DESC')
              ->limit(5);
    }
])->get();
```

---

## JSON Serialization

### Hide Attributes

```php
class User extends Model
{
    protected $hidden = ['password', 'remember_token'];
}

$user = User::find(1);
echo json_encode($user);  // Password not included
```

### To Array

```php
$user = User::find(1);
$array = $user->toArray();
```

---

## Scopes (Reusable Queries)

### Local Scopes

```php
class Post extends Model
{
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', date('Y-m-d', strtotime("-{$days} days")));
    }
}

// Usage
$posts = Post::published()->recent(30)->get();
```

---

## Transactions

```php
use TheFramework\App\Database;

$db = Database::getInstance();

try {
    $db->beginTransaction();

    // Multiple operations
    User::create(['name' => 'John']);
    Profile::create(['user_id' => $userId]);

    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

---

## Advanced Queries

### Joins

Framework mendukung berbagai tipe JOIN untuk menggabungkan data dari multiple tables.

#### INNER JOIN (Default)

Mengambil data yang cocok di kedua tabel:

```php
$posts = Post::query()
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author_name')
    ->get();

// Atau menggunakan helper method
$posts = Post::query()
    ->innerJoin('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author_name')
    ->get();
```

#### LEFT JOIN

Mengambil semua data dari tabel kiri, dan data yang cocok dari tabel kanan:

```php
// Semua posts, dengan atau tanpa user
$posts = Post::query()
    ->leftJoin('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author_name')
    ->get();
```

#### RIGHT JOIN

Mengambil semua data dari tabel kanan, dan data yang cocok dari tabel kiri:

```php
// Semua users, dengan atau tanpa posts
$users = User::query()
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
    ->select('users.*', 'posts.title')
    ->get();
```

#### OUTER JOIN

Left Outer Join dan Right Outer Join (alias dari LEFT/RIGHT JOIN):

```php
// LEFT OUTER JOIN
$posts = Post::query()
    ->leftOuterJoin('users', 'posts.user_id', '=', 'users.id')
    ->get();

// RIGHT OUTER JOIN
$posts = Post::query()
    ->rightOuterJoin('users', 'posts.user_id', '=', 'users.id')
    ->get();
```

#### FULL OUTER JOIN

Mengambil semua data dari kedua tabel (kombinasi LEFT dan RIGHT):

```php
// Note: MySQL tidak support FULL OUTER JOIN secara native
// Framework akan menggunakan UNION dari LEFT dan RIGHT JOIN
$results = Post::query()
    ->fullOuterJoin('users', 'posts.user_id', '=', 'users.id')
    ->get();
```

#### CROSS JOIN

Cartesian product - setiap row dari tabel pertama dikombinasikan dengan setiap row dari tabel kedua:

```php
// Kombinasi semua products dengan semua categories
$combinations = Product::query()
    ->crossJoin('categories')
    ->select('products.name as product', 'categories.name as category')
    ->get();
```

#### Multiple Joins

```php
$posts = Post::query()
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
    ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
    ->select('posts.*', 'users.name as author', 'categories.name as category')
    ->groupBy('posts.id')
    ->get();
```

#### Join dengan WHERE Conditions

```php
$posts = Post::query()
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('users.active', '=', true)
    ->where('posts.published', '=', true)
    ->get();
```

### Group By

```php
$stats = Post::query()
    ->groupBy('user_id')
    ->select('user_id', 'COUNT(*) as total')
    ->get();
```

### Search

```php
$posts = Post::query()
    ->search(['title', 'content'], 'framework')
    ->get();
```

---

## Caching Queries

```php
// Cache results for 3600 seconds
$posts = Post::query()
    ->where('published', true)
    ->remember(3600)
    ->get();
```

---

## Best Practices

### ✅ DO

```php
// Use fillable for mass assignment protection
protected $fillable = ['name', 'email'];

// Hide sensitive data
protected $hidden = ['password'];

// Use relationships
$user->posts;  // Not: Post::where('user_id', $user->id)->get()

// Eager load to prevent N+1
Post::with('user')->get();
```

### ❌ DON'T

```php
// Don't bypass mass assignment protection
protected $fillable = [];  // Dangerous!

// Don't expose sensitive data
// Missing $hidden = ['password']

// Don't create N+1 queries
foreach ($posts as $post) {
    $post->user;  // N additional queries
}
```

---

## Next Steps

- 📖 [Relationships](relationships.md)
- 📖 [Database](database.md)
- 📖 [Migrations](migrations.md)
- 📖 [Query Builder](query-builder.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
