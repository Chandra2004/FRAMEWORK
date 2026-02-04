# Changelog

All notable changes to **The Framework** will be documented in this file.

---

## [5.1.0] - 2026-02-04

### 🎉 New Features

#### Foreign Key Constraints

- ✅ **`foreignId($column)`** - Helper method untuk membuat BIGINT UNSIGNED foreign key column
- ✅ **`constrained($table, $column)`** - Auto-detect foreign key constraints dengan konvensi penamaan
- ✅ **`cascadeOnDelete()`** - Shorthand untuk ON DELETE CASCADE
- ✅ **`restrictOnDelete()`** - Shorthand untuk ON DELETE RESTRICT
- ✅ **`nullOnDelete()`** - Shorthand untuk ON DELETE SET NULL
- ✅ **`cascadeOnUpdate()`** - Shorthand untuk ON UPDATE CASCADE
- ✅ **`dropForeign($columns)`** - Method untuk menghapus foreign key constraints

#### Complete JOIN Support

- ✅ **`innerJoin()`** - INNER JOIN helper method
- ✅ **`leftJoin()`** - LEFT JOIN helper method
- ✅ **`rightJoin()`** - RIGHT JOIN helper method (NEW)
- ✅ **`leftOuterJoin()`** - LEFT OUTER JOIN helper method (NEW)
- ✅ **`rightOuterJoin()`** - RIGHT OUTER JOIN helper method (NEW)
- ✅ **`fullOuterJoin()`** - FULL OUTER JOIN helper method (NEW)
- ✅ **`crossJoin($table)`** - CROSS JOIN untuk cartesian product (NEW)

#### Interactive Debugger (Tinker)

- ✅ **CLI Tinker** - Interactive shell via `php artisan tinker` (REPL)
- ✅ **Web Tinker** - Web-based interactive shell di `/_system/tinker`
- ✅ **Auto-Alias** - Otomatis load Model tanpa namespace full
- ✅ **Safety Features** - Protected via System Key & IP Whitelist

### 📝 Enhanced Documentation

- **`docs/migrations.md`** - Added comprehensive Foreign Keys section (125+ lines)
- **`docs/orm.md`** - Expanded JOIN section with all types (98+ lines)
- **`docs/query-builder.md`** - Updated with all JOIN types (40+ lines)
- **`docs/foreign-keys-joins-guide.md`** - Complete visual guide untuk Foreign Keys & JOINs
- **`docs/foreign-keys-joins-reference.md`** - Quick reference cheat sheet

### 🔧 Core Changes

#### Blueprint.php

```php
// New methods:
- foreignId($column)
- constrained($table = null, $column = 'id')
- cascadeOnDelete()
- restrictOnDelete()
- nullOnDelete()
- cascadeOnUpdate()
- dropForeign($columns)
```

#### QueryBuilder.php

```php
// Enhanced join() method + new helpers:
- innerJoin($table, $first, $operator, $second)
- leftJoin($table, $first, $operator, $second)
- rightJoin($table, $first, $operator, $second)
- leftOuterJoin($table, $first, $operator, $second)
- rightOuterJoin($table, $first, $operator, $second)
- fullOuterJoin($table, $first, $operator, $second)
- crossJoin($table)
```

### 💡 Usage Examples

**Foreign Key - Shorthand:**

```php
Schema::create('posts', function($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->timestamps();
});
```

**JOIN Examples:**

```php
// LEFT JOIN
$posts = Post::query()
    ->leftJoin('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author')
    ->get();

// RIGHT JOIN (NEW!)
$users = User::query()
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### 🎯 Benefits

1. **Laravel-like Syntax** - Familiar untuk developer Laravel
2. **Type Safety** - Mendukung berbagai tipe JOIN sesuai SQL standard
3. **Developer Experience** - Sintaks yang lebih ringkas dan ekspresif
4. **Database Integrity** - Foreign keys menjaga konsistensi data
5. **Flexibility** - Developer bisa pilih sintaks sesuai preferensi

**Upgrade Notice:** These changes are 100% backward compatible. Existing code will continue to work without any modifications.

---

## [5.0.0] - 2026-01-29

### Added

- New Web Command Center for easier management.
- Improved ORM with Laravel-like syntax.
- Enhanced security middleware (CSRF, WAF, Auth).
- Built-in Rate Limiting.
- Support for PHP 8.5+.

### Fixed

- Critical Command Injection in `ServeCommand`.
- SVG XSS vulnerability in `UploadHandler`.
- Windows compatibility for URL validation.
- Missing `.gitignore` files in storage.
- `Model::create()` visibility bug.
