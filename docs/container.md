# 📦 Dependency Injection Container

Framework menyediakan **IoC Container** (Inversion of Control) yang powerful untuk mengelola dependency antar class. Mendukung auto-wiring, singleton, contextual binding, method injection, tagged services, dan resolving events.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Binding](#binding)
4. [Resolving](#resolving)
5. [Auto-Wiring](#auto-wiring)
6. [Singleton](#singleton)
7. [Contextual Binding](#contextual-binding)
8. [Method Injection](#method-injection)
9. [Tagged Services](#tagged-services)
10. [Extending & Decorating](#extending--decorating)
11. [Resolving Events](#resolving-events)
12. [Debugging](#debugging)
13. [Method Reference](#method-reference)

---

## Overview

| Fitur                  | Deskripsi                                         |
| ---------------------- | ------------------------------------------------- |
| **Auto-Wiring**        | Resolusi dependency otomatis via Reflection API   |
| **Singleton**          | Satu instance untuk seluruh application lifecycle |
| **Contextual Binding** | Interface yang berbeda untuk class yang berbeda   |
| **Method Injection**   | DI pada level method (bukan hanya constructor)    |
| **Tagged Services**    | Kelompokkan bindings dengan label                 |
| **Extend/Decorate**    | Bungkus resolved instance dengan decorator        |
| **Resolving Events**   | Callback sebelum/sesudah resolving                |
| **Circular Detection** | Deteksi otomatis circular dependency              |
| **Alias Support**      | Nama alternatif untuk binding                     |

---

## Quick Start

```php
use TheFramework\App\Core\Container;

$container = Container::getInstance();

// 1. Bind interface ke implementasi
$container->bind(PaymentGateway::class, MidtransGateway::class);

// 2. Resolve otomatis (auto-wiring)
$gateway = $container->make(PaymentGateway::class);

// 3. Singleton (dibuat sekali, dipakai berulang)
$container->singleton(CacheManager::class);

// 4. Method injection
$result = $container->call([OrderController::class, 'store'], ['id' => 5]);
```

---

## Binding

### Basic Binding

```php
// Bind interface ke implementasi konkret
$container->bind(LoggerInterface::class, FileLogger::class);

// Bind dengan closure (lazy instantiation)
$container->bind(Mailer::class, function ($container) {
    $config = $container->make(Config::class);
    return new SmtpMailer($config->get('mail.host'));
});

// Bind hanya jika belum ada
$container->bindIf(CacheInterface::class, FileCache::class);
```

### Instance Binding

```php
// Register instance yang sudah ada
$logger = new FileLogger('/var/log/app.log');
$container->instance(LoggerInterface::class, $logger);
```

### Alias

```php
// Buat nama alternatif
$container->alias(CacheManager::class, 'cache');
$container->alias(DatabaseManager::class, 'db');

// Bisa di-resolve pakai alias
$cache = $container->make('cache');
$db = $container->make('db');
```

---

## Resolving

### Basic Resolution

```php
// Resolve class (auto-wiring jika ada constructor dependencies)
$userService = $container->make(UserService::class);

// Resolve dengan parameter tambahan
$controller = $container->make(UserController::class, [
    'defaultRole' => 'admin'
]);
```

### Safe Resolution

```php
// tryMake — return null jika gagal (tidak throw exception)
$service = $container->tryMake(OptionalService::class);

if ($service) {
    $service->doSomething();
}
```

### Check Binding

```php
// Cek apakah sudah di-bind
$container->bound(CacheManager::class);    // true/false
$container->has(CacheManager::class);       // alias untuk bound()

// Cek apakah sudah pernah di-resolve
$container->resolved(UserService::class);   // true/false

// Cek apakah binding adalah singleton
$container->isShared(UserService::class);   // true/false
```

---

## Auto-Wiring

Container secara otomatis membaca constructor parameters menggunakan **PHP Reflection** dan me-resolve dependency-nya.

### Cara Kerja

```php
class OrderService {
    public function __construct(
        private UserRepository $userRepo,      // Auto-resolved
        private PaymentGateway $payment,        // Auto-resolved
        private string $currency = 'IDR'       // Scalar → pakai default value
    ) {}
}

// Container otomatis mendeteksi:
// 1. UserRepository → make(UserRepository::class)
// 2. PaymentGateway → make(PaymentGateway::class)
// 3. $currency → pakai default 'IDR'

$service = $container->make(OrderService::class);
```

### Resolution Order

```
1. Cek apakah ada contextual binding untuk class ini?
2. Cek apakah ada explicit binding?
3. Cek apakah parameter sudah diberikan manual?
4. Cek apakah type-hint adalah class/interface? → Auto-resolve
5. Cek apakah ada default value? → Gunakan default
6. Throw CannotResolveException
```

### Circular Dependency Detection

```php
class A {
    public function __construct(B $b) {} // A butuh B
}

class B {
    public function __construct(A $a) {} // B butuh A → CIRCULAR!
}

$container->make(A::class);
// Error: "Circular dependency detected: A → B → A"
```

---

## Singleton

Singleton memastikan **hanya satu instance** yang dibuat selama lifecycle aplikasi.

```php
// Register as singleton
$container->singleton(DatabaseManager::class, function () {
    return new DatabaseManager(Config::get('database'));
});

// Setiap kali make() dipanggil, return instance yang sama
$db1 = $container->make(DatabaseManager::class);
$db2 = $container->make(DatabaseManager::class);
// $db1 === $db2 → true (objek yang sama)

// Singleton jika belum ada
$container->singletonIf(CacheManager::class);
```

### Forget Singleton

```php
// Hapus instance singleton (dipaksa re-create saat make berikutnya)
$container->forgetInstance(DatabaseManager::class);

// Hapus semua singleton instances
$container->forgetInstances();
```

---

## Contextual Binding

Satu interface, implementasi **berbeda** tergantung siapa yang meminta.

```php
// PhotoController mendapat LocalStorage
$container->when(PhotoController::class)
          ->needs(StorageInterface::class)
          ->give(LocalStorage::class);

// VideoController mendapat S3Storage
$container->when(VideoController::class)
          ->needs(StorageInterface::class)
          ->give(S3Storage::class);

// Atau dengan closure
$container->when(ReportController::class)
          ->needs(ExportInterface::class)
          ->give(function ($container) {
              return new PdfExporter(
                  $container->make(Config::class)->get('export.paper_size')
              );
          });
```

### Cara Kerja

```
Container::make(PhotoController::class)
    │
    ├── Constructor: __construct(StorageInterface $storage)
    │
    ├── Cek contextual: PhotoController needs StorageInterface?
    │   → YES → resolve LocalStorage::class
    │
    └── Return PhotoController with LocalStorage injected
```

---

## Method Injection

Resolve dependency langsung saat memanggil method:

### Closure

```php
$result = $container->call(function (UserService $service, CacheManager $cache) {
    $users = $cache->remember('users', 3600, fn() => $service->getAll());
    return $users;
});
```

### Class Method

```php
// [Class, Method] syntax
$result = $container->call([UserController::class, 'show'], ['id' => 5]);

// String syntax: "Class@Method"
$result = $container->call('App\Controllers\UserController@show', ['id' => 5]);
```

### Make and Call (Shortcut)

```php
// Make instance + panggil method sekaligus
$result = $container->makeAndCall(
    OrderController::class,
    'store',
    ['orderId' => 123]
);

// Equivalent to:
// $controller = $container->make(OrderController::class);
// $container->call([$controller, 'store'], ['orderId' => 123]);
```

---

## Tagged Services

Kelompokkan beberapa binding dengan satu label (tag):

```php
// Tag beberapa services
$container->tag([
    PdfReport::class,
    CsvReport::class,
    XlsxReport::class,
], 'reports');

$container->tag([
    StripeSMS::class,
    TwilioSMS::class,
], 'notification_channels');

// Resolve semua tagged services
$reports = $container->tagged('reports');
// Returns: [PdfReport instance, CsvReport instance, XlsxReport instance]

foreach ($reports as $report) {
    $report->generate($data);
}
```

---

## Extending & Decorating

Bungkus resolved instance dengan decorator (Decorator Pattern):

```php
// Setiap kali CacheManager di-resolve, bungkus dengan LoggingCache
$container->extend(CacheManager::class, function ($cache, $container) {
    return new LoggingCache($cache, $container->make(Logger::class));
});

// Chain decorators
$container->extend(UserRepository::class, function ($repo, $container) {
    return new CachingUserRepository($repo);
});

$container->extend(UserRepository::class, function ($repo, $container) {
    return new LoggingUserRepository($repo); // Wraps CachingUserRepository
});
```

---

## Resolving Events

Register callback yang dipanggil saat binding di-resolve:

### Before Resolving

```php
$container->resolving(UserService::class, function () {
    Logging::debug('Resolving UserService...');
});
```

### After Resolving

```php
$container->afterResolving(UserService::class, function ($service, $container) {
    // Inject additional configuration
    $service->setDefaultPagination(25);

    Logging::debug('UserService resolved and configured');
});
```

---

## Debugging

### Dependency Tree

```php
// Visualisasikan dependency tree sebuah class
$tree = $container->getDependencyTree(OrderController::class);
echo $tree;

// Output:
// OrderController
//   ├── OrderService
//   │   ├── OrderRepository
//   │   │   └── Database
//   │   └── PaymentGateway
//   ├── UserService
//   │   └── UserRepository
//   │       └── Database
//   └── CacheManager
```

### Summary

```php
echo $container->summary();

// Output:
// ========================================
// CONTAINER SUMMARY
// ========================================
// Bindings    : 15
// Singletons  : 8
// Aliases     : 5
// Tags        : 3
// Contextual  : 2
// ========================================
```

### List All Bindings

```php
$bindings = $container->getBindings();
$aliases = $container->getAliases();
```

### Reset Container

```php
// Full reset (hapus semua bindings, instances, aliases, dll)
$container->flush();
```

---

## Method Reference

### Binding

| Method                                  | Deskripsi                        |
| --------------------------------------- | -------------------------------- |
| `bind($abstract, $concrete, $shared)`   | Bind class/interface ke resolver |
| `singleton($abstract, $concrete)`       | Bind sebagai singleton           |
| `bindIf($abstract, $concrete, $shared)` | Bind jika belum ada              |
| `singletonIf($abstract, $concrete)`     | Singleton jika belum ada         |
| `instance($abstract, $instance)`        | Register existing instance       |
| `alias($abstract, $alias)`              | Buat nama alternatif             |

### Resolving

| Method                                     | Return        | Deskripsi               |
| ------------------------------------------ | ------------- | ----------------------- |
| `make($abstract, $params)`                 | `mixed`       | Resolve dari container  |
| `tryMake($abstract, $params)`              | `mixed\|null` | Resolve tanpa exception |
| `build($concrete, $params)`                | `mixed`       | Build via Reflection    |
| `call($callback, $params)`                 | `mixed`       | Call dengan DI          |
| `makeAndCall($abstract, $method, $params)` | `mixed`       | Make + call shortcut    |

### Contextual

| Method                    | Return                     | Deskripsi                |
| ------------------------- | -------------------------- | ------------------------ |
| `when($concrete)`         | `ContextualBindingBuilder` | Mulai contextual binding |
| `->needs($abstract)`      | `ContextualBindingBuilder` | Define target interface  |
| `->give($implementation)` | `void`                     | Define implementasi      |

### Tagged

| Method                  | Return  | Deskripsi            |
| ----------------------- | ------- | -------------------- |
| `tag($abstracts, $tag)` | `void`  | Tag bindings         |
| `tagged($tag)`          | `array` | Resolve semua tagged |

### Events

| Method                                 | Deskripsi                          |
| -------------------------------------- | ---------------------------------- |
| `resolving($abstract, $callback)`      | Before resolving callback          |
| `afterResolving($abstract, $callback)` | After resolving callback           |
| `extend($abstract, $closure)`          | Decorator/extend resolved instance |

### Inspection

| Method                      | Return   | Deskripsi                    |
| --------------------------- | -------- | ---------------------------- |
| `bound($abstract)`          | `bool`   | Cek apakah sudah di-bind     |
| `has($abstract)`            | `bool`   | Alias `bound()`              |
| `resolved($abstract)`       | `bool`   | Cek apakah pernah di-resolve |
| `isShared($abstract)`       | `bool`   | Cek apakah singleton         |
| `getBindings()`             | `array`  | Semua bindings               |
| `getAliases()`              | `array`  | Semua aliases                |
| `getDependencyTree($class)` | `string` | Visualisasi dependency tree  |
| `summary()`                 | `string` | Ringkasan status             |

### Cleanup

| Method                      | Deskripsi                  |
| --------------------------- | -------------------------- |
| `forgetInstance($abstract)` | Hapus singleton instance   |
| `forgetInstances()`         | Hapus semua instances      |
| `flush()`                   | Reset container sepenuhnya |

---

## Real-World Example: Service Provider

```php
// app/Providers/AppServiceProvider.php
use TheFramework\App\Core\Container;

class AppServiceProvider
{
    public function register(Container $container): void
    {
        // Singleton services
        $container->singleton(CacheManager::class);
        $container->singleton(MailHandler::class);

        // Interface bindings
        $container->bind(PaymentGateway::class, MidtransGateway::class);
        $container->bind(StorageInterface::class, LocalStorage::class);

        // Contextual bindings
        $container->when(ApiController::class)
                  ->needs(AuthInterface::class)
                  ->give(JwtAuth::class);

        $container->when(WebController::class)
                  ->needs(AuthInterface::class)
                  ->give(SessionAuth::class);

        // Tagged services
        $container->tag([
            EmailNotification::class,
            SmsNotification::class,
            PushNotification::class,
        ], 'notifications');
    }
}
```

---

## 🔗 Related Documentation

- [Providers](providers.md) — Service Provider registration
- [Services](services.md) — Business logic layer
- [Architecture](architecture.md) — MVC pattern & DI
- [Core](core.md) — Overview modul core

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
