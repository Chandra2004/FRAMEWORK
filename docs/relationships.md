# 🔗 Model Relationships

Understanding how to define and use relationships between models.

---

## One-to-Many (hasMany)

**A user has many posts**

### Define Relationship

```php
// app/Models/User.php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
        // Parameters: Related Model, Foreign Key, Local Key
    }
}
```

### Usage

```php
$user = User::find(1);

// Access posts
$posts = $user->posts;  // Returns array of Post models

// Loop posts
foreach ($user->posts as $post) {
    echo $post->title;
}

// Count posts
$postCount = count($user->posts);
```

### Eager Loading

```php
// ❌ N+1 Problem (Bad)
$users = User::all();
foreach ($users as $user) {
    echo $user->posts[0]->title;  // 1 query per user
}

// ✅ Solution (Good)
$users = User::with('posts')->get();  // Only 2 queries total
foreach ($users as $user) {
    echo $user->posts[0]->title;
}
```

---

## Inverse: Belongs To (belongsTo)

**A post belongs to a user**

### Define Relationship

```php
// app/Models/Post.php
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
        // Parameters: Related Model, Foreign Key, Owner Key
    }
}
```

### Usage

```php
$post = Post::find(1);

// Access user
$author = $post->user;

echo $post->user->name;  // Direct property access
```

---

## One-to-One (hasOne)

**A user has one profile**

### Define Relationship

```php
// app/Models/User.php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}

// app/Models/Profile.php
class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

### Usage

```php
$user = User::find(1);

// Access profile
$profile = $user->profile;

echo $user->profile->bio;
echo $user->profile->avatar;
```

---

## Many-to-Many (belongsToMany)

**Users have many roles, roles belong to many users**

### Database Structure

```sql
-- users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255)
);

-- roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255)
);

-- Pivot table
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    role_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### Define Relationship

```php
// app/Models/User.php
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,      // Related model
            'user_roles',     // Pivot table
            'user_id',        // Foreign key on pivot
            'role_id'         // Related key on pivot
        );
    }
}

// app/Models/Role.php
class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        );
    }
}
```

### Usage

```php
$user = User::find(1);

// Get all roles
$roles = $user->roles;

foreach ($user->roles as $role) {
    echo $role->name;
}

// Check if user has role
$hasAdmin = in_array('admin', array_column($user->roles, 'name'));
```

---

## Has-Many-Through

**A country has many posts through users**

```
Country → User → Post
```

### Define Relationship

```php
// app/Models/Country.php
class Country extends Model
{
    public function posts()
    {
        return $this->hasManyThrough(
            Post::class,      // Final model
            User::class,      // Intermediate model
            'country_id',     // Foreign key on users table
            'user_id',        // Foreign key on posts table
            'id',            // Local key on countries table
            'id'             // Local key on users table
        );
    }
}
```

### Usage

```php
$country = Country::find(1);

// Get all posts from users in this country
$posts = $country->posts;
```

---

## Polymorphic Relations

**Comments can belong to both Post and Video**

### Database Structure

```sql
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content TEXT,
    commentable_id INT,      -- ID of post or video
    commentable_type VARCHAR(255)  -- 'App\\Models\\Post' or 'App\\Models\\Video'
);
```

### Define Relationship

```php
// app/Models/Comment.php
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}

// app/Models/Post.php
class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// app/Models/Video.php
class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

### Usage

```php
// Get comments for a post
$post = Post::find(1);
$comments = $post->comments;

// Get parent of a comment
$comment = Comment::find(1);
$parent = $comment->commentable;  // Could be Post or Video
```

---

## Nested Eager Loading

### Load Multiple Levels

```php
// Load posts with their comments and comment authors
$posts = Post::with(['comments', 'comments.user'])->get();

// Dot notation (same as above)
$posts = Post::with('comments.user')->get();

// Multiple relations
$posts = Post::with(['user', 'comments.user', 'tags'])->get();
```

### With Constraints

```php
$users = User::with([
    'posts' => function($query) {
        $query->where('published', true)
              ->orderBy('created_at', 'DESC')
              ->limit(5);
    },
    'posts.comments' => function($query) {
        $query->where('approved', true);
    }
])->get();
```

---

## Lazy Eager Loading

Load relationships after model is retrieved:

```php
$posts = Post::all();

// Later, load relationships
$posts = Post::loadRelations($posts, ['user', 'comments']);
```

---

## Relationship Methods vs Properties

### As Method (Query Builder)

```php
$user->posts()  // Returns QueryBuilder, can chain methods
    ->where('published', true)
    ->orderBy('created_at', 'DESC')
    ->get();
```

### As Property (Direct Access)

```php
$user->posts  // Returns collection of posts (executes query)

foreach ($user->posts as $post) {
    // ...
}
```

---

## Counting Related Models

```php
// Count posts for each user
$users = User::query()
    ->select('users.*')
    ->selectRaw('COUNT(posts.id) as posts_count')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->groupBy('users.id')
    ->get();

foreach ($users as $user) {
    echo "{$user->name} has {$user->posts_count} posts";
}
```

---

## Best Practices

### ✅ DO

```php
// Use eager loading to prevent N+1
$posts = Post::with('user')->get();

// Name relationships clearly
public function author() { ... }  // Clear
public function user() { ... }    // OK
public function u() { ... }       // ❌ Bad

// Use relationship methods for queries
$user->posts()->where('published', true)->get();
```

### ❌ DON'T

```php
// Don't create N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name;  // N queries
}

// Don't load unnecessary data
Post::with('user', 'comments', 'tags', 'categories')->get();
// Only load what you need!
```

---

## Real-World Example

### Blog System

```php
// Models with relationships

// User.php
class User extends Model
{
    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'user_id');
    }
}

// Post.php
class Post extends Model
{
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id');
    }
}

// Comment.php
class Comment extends Model
{
    public function post() {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// Usage in controller
public function showPost($id)
{
    $post = Post::with(['author', 'comments.user', 'tags'])->findOrFail($id);

    return view('post.show', [
        'post' => $post,
        'author' => $post->author,
        'comments' => $post->comments,
        'tags' => $post->tags
    ]);
}
```

---

## Next Steps

- 📖 [ORM Guide](orm.md)
- 📖 [Database](database.md)
- 📖 [Query Builder](query-builder.md)
- 📖 [Migrations](migrations.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
