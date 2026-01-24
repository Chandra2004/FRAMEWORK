# 🔀 Middleware

Middleware adalah layer yang memproses HTTP request sebelum mencapai controller (atau setelah response dikembalikan).

---

## How Middleware Works

```
Request → Middleware 1 → Middleware 2 → Controller → Response
          (before)       (before)        (logic)
```

---

## Built-in Middleware

The Framework comes with security middleware:

| Middleware            | Purpose                  | Auto-Applied |
| --------------------- | ------------------------ | ------------ |
| `CsrfMiddleware`      | CSRF Protection          | ✅ Yes       |
| `WAFMiddleware`       | Web Application Firewall | ✅ Yes       |
| `RateLimitMiddleware` | Rate limiting            | ⚠️ Optional  |
| `AuthMiddleware`      | Authentication           | ❌ Manual    |
| `AdminMiddleware`     | Admin check              | ❌ Manual    |

---

## Creating Middleware

### Generate Middleware

```bash
php artisan make:middleware LogRequestMiddleware
```

Generated: `app/Middleware/LogRequestMiddleware.php`

### Basic Structure

```php
<?php

namespace TheFramework\Middleware;

class LogRequestMiddleware implements Middleware
{
    public function before()
    {
        // Code here runs BEFORE controller

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        error_log("Request: $method $uri");
    }
}
```

---

## Applying Middleware

### Route-Level Middleware

```php
use TheFramework\Middleware\AuthMiddleware;

// Single middleware
Router::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware([AuthMiddleware::class]);

// Multiple middleware
Router::get('/admin/users', [AdminController::class, 'index'])
    ->middleware([AuthMiddleware::class, AdminMiddleware::class]);
```

### Group Middleware

```php
Router::group(['middleware' => [AuthMiddleware::class]], function() {
    Router::get('/profile', [ProfileController::class, 'index']);
    Router::post('/profile', [ProfileController::class, 'update']);
    Router::get('/settings', [SettingsController::class, 'index']);
});
```

---

## Common Middleware Examples

### 1. Authentication Middleware

```php
<?php

namespace TheFramework\Middleware;

class AuthMiddleware implements Middleware
{
    public function before()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

**Usage:**

```php
Router::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware([AuthMiddleware::class]);
```

---

### 2. Admin Check Middleware

```php
<?php

namespace TheFramework\Middleware;

use TheFramework\Models\User;

class AdminMiddleware implements Middleware
{
    public function before()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            abort(401, "Unauthorized");
        }

        $user = User::find($userId);

        if (!$user || $user->role !== 'admin') {
            abort(403, "Forbidden: Admin only");
        }
    }
}
```

---

### 3. CORS Middleware

```php
<?php

namespace TheFramework\Middleware;

class CorsMiddleware implements Middleware
{
    public function before()
    {
        header('Access-Control-Allow-Origin: * ');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
```

---

### 4. Rate Limiting Middleware

```php
<?php

namespace TheFramework\Middleware;

use TheFramework\App\RateLimiter;
use TheFramework\Helpers\Helper;

class RateLimitMiddleware implements Middleware
{
    private $maxAttempts;
    private $decaySeconds;

    public function __construct($maxAttempts = 60, $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    public function before()
    {
        $ip = Helper::get_client_ip();

        try {
            RateLimiter::check($ip, $this->maxAttempts, $this->decaySeconds);
        } catch (\Exception $e) {
            http_response_code(429);
            die(json_encode([
                'error' => 'Too many requests',
                'retry_after' => $this->decaySeconds
            ]));
        }
    }
}
```

**Usage:**

```php
Router::get('/api/data', [ApiController::class, 'index'])
    ->middleware([[RateLimitMiddleware::class, 30, 60]]);  // 30 requests per minute
```

---

### 5. JSON Response Middleware

```php
<?php

namespace TheFramework\Middleware;

class JsonResponseMiddleware implements Middleware
{
    public function before()
    {
        header('Content-Type: application/json');
    }
}
```

---

### 6. Logging Middleware

```php
<?php

namespace TheFramework\Middleware;

use TheFramework\App\Logging;

class RequestLoggerMiddleware implements Middleware
{
    public function before()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        Logging::getLogger()->info("Request: $method $uri from $ip");
    }
}
```

---

## Middleware with Parameters

### Define Middleware with Constructor

```php
<?php

namespace TheFramework\Middleware;

class RoleMiddleware implements Middleware
{
    private $requiredRole;

    public function __construct($role)
    {
        $this->requiredRole = $role;
    }

    public function before()
    {
        $user = auth()->user();

        if (!$user || $user->role !== $this->requiredRole) {
            abort(403, "Access denied");
        }
    }
}
```

### Use with Parameters

```php
Router::get('/admin/dashboard', [AdminController::class, 'index'])
    ->middleware([[RoleMiddleware::class, 'admin']]);

Router::get('/moderator/posts', [ModeratorController::class, 'index'])
    -> middleware([[RoleMiddleware::class, 'moderator']]);
```

---

## Middleware Execution Order

Middleware executes in the order they are defined:

```php
Router::get('/protected', [Controller::class, 'method'])
    ->middleware([
        Middleware1::class,  // Runs first
        Middleware2::class,  // Runs second
        Middleware3::class   // Runs third
    ]);
```

**Flow:**

```
Request → Middleware1 → Middleware2 → Middleware3 → Controller
```

---

## Global Middleware

Apply middleware to ALL routes:

### Option 1: In bootstrap/app.php

```php
// bootstrap/app.php

// Applied to every request
if (php_sapi_name() !== 'cli') {
    $rateLimiter = new RateLimitMiddleware(100, 120);
    $rateLimiter->before();
}
```

### Option 2: In Route File

```php
// routes/web.php

// Wrap all routes
Router::group(['middleware' => [LoggerMiddleware::class]], function() {
    // All routes here will have LoggerMiddleware
    require __DIR__ . '/api.php';
    require __DIR__ . '/admin.php';
});
```

---

## Terminating Middleware

Middleware yang menghentikan request:

```php
public function before()
{
    if ($condition) {
        http_response_code(403);
        echo "Access denied";
        exit;  // Stops execution
    }
}
```

---

## Middleware Best Practices

### ✅ DO

```php
// Keep middleware focused (Single Responsibility)
class AuthMiddleware  // Only check auth
class LogMiddleware   // Only log
class CorsMiddleware  // Only handle CORS

// Use descriptive names
class EnsureUserIsAuthenticated  // Clear
class Auth  // Less clear

// Check early, fail fast
public function before()
{
    if (!auth()->check()) {
        abort(401);  // Stop immediately
    }
}
```

### ❌ DON'T

```php
// Don't put business logic in middleware
public function before()
{
    // ❌ Bad: Business logic
    $user->updateLastLogin();
    sendWelcomeEmail($user);
    calculateUserStats();
}

// Don't do heavy processing
public function before()
{
    // ❌ Bad: Slow operations
    $allUsers = User::with('posts', 'comments')->get();
}
```

---

## Testing Middleware

```php
// tests/MiddlewareTest.php

use Tests\TestCase;
use TheFramework\Middleware\AuthMiddleware;

class AuthMiddlewareTest extends TestCase
{
    public function testRedirectsUnauthenticatedUsers()
    {
        $response = $this->get('/dashboard');

        $this->assertEquals(302, $response->statusCode);
        $this->assertEquals('/login', $response->redirectUrl);
    }

    public function testAllowsAuthenticatedUsers()
    {
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $this->assertEquals(200, $response->statusCode);
    }
}
```

---

## Next Steps

- 📖 [Routing](routing.md)
- 📖 [Controllers](controllers.md)
- 📖 [Security](security.md)
- 📖 [Authentication](tutorial-auth.md)

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
