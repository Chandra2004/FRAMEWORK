# 🔐 Authentication Tutorial

**Coming Soon!**

This tutorial will cover:

1. User Registration System
2. Login \u0026 Logout Flow
3. Session Management
4. Password Reset
5. Email Verification
6. Remember Me functionality
7. Social Auth (OAuth)

---

## Quick Auth Setup

### 1. Create Users Table

```bash
php artisan migrate
```

### 2. User Model

```php
<?php

namespace TheFramework\Models;

use TheFramework\App\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
}
```

### 3. Registration Controller

```php
public function register(Request $request)
{
    $data = $request->input();
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

    User::create($data);

    return redirect('/login');
}
```

### 4. Login Controller

```php
public function login(Request $request)
{
    $email = $request->input('email');
    $password = $request->input('password');

    $user = User::where('email', $email)->first();

    if ($user && password_verify($password, $user->password)) {
        $_SESSION['user_id'] = $user->id;
        return redirect('/dashboard');
    }

    return redirect('/login')->with('error', 'Invalid credentials');
}
```

---

📖 **Full tutorial dengan complete authentication system coming in v5.1.0!**

📧 Request features: support@the-framework.ct.ws

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
