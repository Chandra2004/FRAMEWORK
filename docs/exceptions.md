# 📘 Exceptions & Error Handling Documentation

> TheFramework v5.0.1 — Exception handling setara Laravel + fitur bonus

---

## Daftar Isi

1. [Exception Handler (Registration)](#exception-handler)
2. [HTTP Exceptions](#http-exceptions)
3. [Abort Helper](#abort-helper)
4. [Model Exceptions](#model-exceptions)
5. [Validation Exception](#validation-exception)
6. [Auth Exceptions](#auth-exceptions)
7. [Reporting & Logging](#reporting--logging)
8. [Custom Renderers & Reporters](#custom-renderers--reporters)
9. [JSON/API Error Responses](#jsonapi-error-responses)
10. [Beyond Laravel Features](#beyond-laravel)

---

## Exception Handler

### Registration

```php
// Sudah dipanggil otomatis di bootstrap
TheFramework\App\Exceptions\Handler::register();
```

Register 3 handler:

- **Error Handler** — Warnings, Notices, Deprecated
- **Exception Handler** — Semua uncaught exceptions
- **Shutdown Handler** — Fatal errors (E_ERROR, E_PARSE, E_COMPILE_ERROR)

---

## HTTP Exceptions

### Hierarchy

```
HttpException (base)
├── NotFoundHttpException          (404)
├── AccessDeniedHttpException      (403)
├── UnauthorizedHttpException      (401) + WWW-Authenticate header
├── MethodNotAllowedHttpException  (405) + Allow header
├── TooManyRequestsHttpException   (429) + Retry-After header
├── ServiceUnavailableHttpException(503) + Retry-After header
├── ConflictHttpException          (409)
└── GoneHttpException              (410)
```

### Usage

```php
use TheFramework\App\Exceptions\NotFoundHttpException;
use TheFramework\App\Exceptions\HttpException;

throw new NotFoundHttpException('Page not found');
throw new HttpException(418, "I'm a teapot");

// With custom headers
throw new TooManyRequestsHttpException(60, 'Too many requests');
// Automatically adds header: Retry-After: 60

throw new MethodNotAllowedHttpException(['GET', 'POST']);
// Automatically adds header: Allow: GET, POST

throw new UnauthorizedHttpException('Bearer');
// Automatically adds header: WWW-Authenticate: Bearer
```

---

## Abort Helper

```php
use TheFramework\App\Exceptions\Handler;

// Basic abort
Handler::abort(404);
Handler::abort(403, 'Forbidden');
Handler::abort(500, 'Something went wrong', ['X-Custom' => 'value']);

// Conditional abort
Handler::abortIf($user === null, 404, 'User not found');
Handler::abortUnless($user->isAdmin(), 403, 'Not authorized');
```

---

## Model Exceptions

```php
use TheFramework\App\Exceptions\ModelNotFoundException;

// Thrown automatically by findOrFail()
$user = User::findOrFail(123);

// Manual throw
throw new ModelNotFoundException('App\\Models\\User', [123]);

// Get info
$e->getModel();  // 'App\Models\User'
$e->getIds();    // [123]
// Auto HTTP 404
```

---

## Validation Exception

```php
use TheFramework\App\Exceptions\ValidationException;

// From array
throw ValidationException::withMessages([
    'email' => ['Email is required.'],
    'name' => ['Name must be at least 3 characters.'],
]);

// With error bag
throw ValidationException::withMessages([...])->errorBag('login');

// With custom redirect
throw ValidationException::withMessages([...])
    ->redirectTo('/register');

// Get errors
$e->errors();        // ['email' => [...], ...]
$e->getErrorBag();   // 'default'
$e->toArray();       // for JSON response
// Auto HTTP 422
```

---

## Auth Exceptions

### AuthenticationException (401)

```php
use TheFramework\App\Exceptions\AuthenticationException;

throw new AuthenticationException('Please login first.', ['web'], '/login');

$e->guards();       // ['web']
$e->redirectTo();   // '/login'
```

### AuthorizationException (403)

```php
use TheFramework\App\Exceptions\AuthorizationException;

throw new AuthorizationException('You cannot edit this post.');

// Masquerade as 404
throw (new AuthorizationException('Not found.'))->asNotFound();

// Custom status
throw (new AuthorizationException('Rate limited.'))->withStatus(429);
```

---

## Reporting & Logging

### Automatic Logging

Semua exception otomatis di-log ke `storage/logs/framework-YYYY-MM-DD.log`:

```
[2026-02-26 12:30:45] [ERROR] [App\Models\UserException] User not found in /app/Controllers/UserController.php:42
[2026-02-26 12:30:46] [WARNING] [E_NOTICE] Undefined variable $foo in /resources/views/home.blade.php:15
[2026-02-26 12:30:47] [CRITICAL] [PDOException] SQLSTATE[42S02] in /app/Database/Database.php:150
```

### Log Levels (Auto-classified)

| Exception Type                  | Level      |
| ------------------------------- | ---------- |
| HTTP 5xx                        | `CRITICAL` |
| HTTP 4xx                        | `WARNING`  |
| PDOException, DatabaseException | `CRITICAL` |
| ModelNotFoundException          | `WARNING`  |
| AuthenticationException         | `WARNING`  |
| AuthorizationException          | `WARNING`  |
| ValidationException             | `NOTICE`   |
| Everything else                 | `ERROR`    |

### Ignore List ($dontReport)

```php
Handler::ignore(
    ModelNotFoundException::class,
    ValidationException::class,
    AuthenticationException::class,
);
```

### Exception report() Method

```php
class PaymentException extends \Exception
{
    public function report(): bool
    {
        // Custom reporting logic
        PaymentLogger::log($this->getMessage());
        return false; // false = stop default reporting
    }
}
```

### Exception context() Method

```php
class OrderException extends \Exception
{
    public function context(): array
    {
        return [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
        ];
    }
}
```

---

## Custom Renderers & Reporters

### Custom Renderer

```php
Handler::renderable(ModelNotFoundException::class, function ($e) {
    View::render('errors.model_not_found', [
        'model' => $e->getModel(),
        'ids' => $e->getIds(),
    ]);
});

Handler::renderable(PaymentException::class, function ($e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Payment failed',
        'code' => $e->getCode(),
    ]);
});
```

### Custom Reporter

```php
Handler::reportable(DatabaseException::class, function ($e, $context) {
    SlackAlert::send("DB Error: {$e->getMessage()}");
});
```

### External Hooks (Sentry, Bugsnag, etc.)

```php
// Sentry integration
Handler::addReportHook(function (\Throwable $e, array $context) {
    \Sentry\captureException($e);
});

// Bugsnag integration
Handler::addReportHook(function (\Throwable $e, array $context) {
    \Bugsnag::notifyException($e);
});
```

---

## JSON/API Error Responses

Otomatis mendeteksi API request dan mengirim JSON:

**Deteksi otomatis jika:**

- `Accept: application/json`
- `Content-Type: application/json`
- `X-Requested-With: XMLHttpRequest`
- URL dimulai dengan `/api/`

### Production Response

```json
{
  "message": "Not Found"
}
```

### Debug Response

```json
{
  "message": "No query results for model [User] 123.",
  "exception": "TheFramework\\App\\Exceptions\\ModelNotFoundException",
  "file": "/app/Controllers/UserController.php",
  "line": 42,
  "trace": [{ "file": "...", "line": 42, "function": "..." }]
}
```

### Validation Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Email is required."],
    "password": ["Password must be at least 8 characters."]
  }
}
```

---

## Beyond Laravel

| #   | Fitur                             | Deskripsi                                               |
| --- | --------------------------------- | ------------------------------------------------------- |
| 1   | **Exception Fingerprinting**      | Deduplication — exception identik tidak di-log berulang |
| 2   | **Rate-Limited Logging**          | Max 3 identical errors per request                      |
| 3   | **Daily Log Rotation**            | Auto `framework-YYYY-MM-DD.log`                         |
| 4   | **Log Level Auto-Classification** | CRITICAL/ERROR/WARNING/NOTICE berdasarkan tipe          |
| 5   | **External Report Hooks**         | Plug in Sentry/Bugsnag/custom dengan 1 line             |
| 6   | **Previous Chain Display**        | Tampil chain of previous exceptions                     |
| 7   | **Exception context()**           | Extra data dari exception                               |
| 8   | **$dontFlash**                    | Proteksi otomatis password/token/credit_card/cvv        |
| 9   | **Real IP Detection**             | Via X-Forwarded-For, X-Real-IP                          |
| 10  | **Expanded Production Views**     | Support 400/401/403/404/419/422/429/500/503             |
| 11  | **throttleReports()**             | Configurable rate limit                                 |
| 12  | **flush()**                       | Reset all config (untuk testing)                        |
