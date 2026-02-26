# 🔧 Core — Config, Container, Lang, Logging

Dokumentasi lengkap komponen inti framework.

---

## 📑 Daftar Isi

- [Config](#config)
- [Container (DI)](#container-di)
- [Lang (Localization)](#lang-localization)
- [Logging](#logging)

---

## Config

Config mengelola konfigurasi aplikasi dari file `.env`, config files PHP, dan runtime overrides.

### Penggunaan Dasar

```php
use TheFramework\App\Core\Config;

// Load .env (biasanya di bootstrap)
Config::loadEnv();

// Ambil value
$appName = Config::get('APP_NAME', 'My App');
$debug   = Config::get('APP_DEBUG', false);
$dbHost  = Config::get('DB_HOST', 'localhost');
```

### Config Files PHP (Dot Notation)

Buat file di folder `config/` project:

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
        ],
    ],
];
```

Akses via dot notation:

```php
Config::get('database.default');                  // 'mysql'
Config::get('database.connections.mysql.host');    // 'localhost'
```

### Type-Casted Getters

```php
Config::getString('APP_NAME', 'Default');   // string
Config::getInt('CACHE_TTL', 3600);          // int
Config::getFloat('TAX_RATE', 0.1);          // float
Config::getBool('APP_DEBUG', false);        // bool (handles 'true'/'1'/'yes'/'on')
Config::getArray('ALLOWED_IPS', ',');       // array (split by delimiter)
Config::getOrFail('DB_PASSWORD');           // throw exception jika tidak ada
```

### Runtime Set & Check

```php
Config::set('app.timezone', 'Asia/Jakarta');
Config::has('APP_KEY');    // true/false
Config::all();             // semua config
Config::many(['APP_NAME', 'APP_ENV' => 'production']);
```

### Environment Detection

```php
Config::environment();     // 'production', 'local', 'testing'
Config::isProduction();    // true/false
Config::isLocal();         // true/false
Config::isTesting();       // true/false
Config::isDebug();         // true/false (dari APP_DEBUG)
```

### Caching

```php
Config::cache();          // Generate storage/cache/config.php
Config::clearCache();     // Hapus cache
Config::isCached();       // Cek status
```

### 🚀 Beyond Laravel

```php
// Validasi value
Config::getValidated('PORT', fn($v) => is_numeric($v) && $v > 0, 8080);

// Temporary override (untuk testing)
Config::withOverride(['APP_ENV' => 'testing'], function () {
    // APP_ENV = 'testing' di sini
    runTests();
}); // APP_ENV otomatis kembali ke semula
```

---

## Container (DI)

Container adalah jantung framework — mengelola dependency injection secara otomatis.

### Binding & Resolving

```php
use TheFramework\App\Core\Container;

$container = Container::getInstance();

// Bind interface ke implementation
$container->bind(PaymentGateway::class, StripeGateway::class);

// Singleton (hanya dibuat sekali)
$container->singleton(Database::class, fn($c) => new Database(
    Config::get('DB_HOST'),
));

// Register existing instance
$container->instance('config', $configObject);

// Resolve (auto-inject dependencies)
$gateway = $container->make(PaymentGateway::class);
```

### Auto-Wiring

Container secara otomatis mendeteksi dan inject dependencies:

```php
class OrderService
{
    // Dependencies OTOMATIS di-inject oleh Container!
    public function __construct(
        private Database $db,
        private PaymentGateway $gateway,
        private Logger $logger
    ) {}
}

// Container auto-resolve semua 3 dependencies
$service = $container->make(OrderService::class);
```

### Method Injection (call)

```php
// Inject dependencies ke method call
$result = $container->call([UserController::class, 'show'], ['id' => 5]);

// String syntax
$result = $container->call('UserController@show', ['id' => 5]);

// Closure
$result = $container->call(fn(UserService $svc) => $svc->all());
```

### Contextual Binding

```php
// PhotoController dapat LocalFS, VideoController dapat S3FS
$container->when(PhotoController::class)
    ->needs(FileSystem::class)
    ->give(LocalFileSystem::class);

$container->when(VideoController::class)
    ->needs(FileSystem::class)
    ->give(S3FileSystem::class);
```

### Tags

```php
$container->tag([ConsoleReport::class, HtmlReport::class], 'reports');

$reports = $container->tagged('reports');
foreach ($reports as $report) {
    $report->generate();
}
```

### Extending (Decorator)

```php
$container->extend('cache', function ($cache, $container) {
    return new LoggingCacheDecorator($cache);
});
```

### 🚀 Beyond Laravel

```php
// Try make — return null jika gagal (tanpa throw)
$service = $container->tryMake(SomeService::class);

// Make & langsung call method
$result = $container->makeAndCall(UserService::class, 'getAll');

// Debug dependency tree
$tree = $container->getDependencyTree(OrderController::class);
// ['OrderService' => ['Database' => [], 'PaymentGateway' => []], '$id' => 'int']

// Status
echo $container->summary();
```

---

## Lang (Localization)

Lang mengelola terjemahan multi-bahasa dengan dot notation, placeholders, dan pluralization.

### Penggunaan Dasar

```php
use TheFramework\App\Core\Lang;

Lang::setLocale('id');

// Ambil terjemahan
echo Lang::get('messages.welcome');                         // "Selamat datang"
echo Lang::get('messages.greeting', ['name' => 'John']);   // "Halo John!"
```

### File Terjemahan PHP

```php
// resources/lang/id/messages.php
return [
    'welcome'  => 'Selamat datang',
    'greeting' => 'Halo :name!',
    'auth'     => [
        'login'  => 'Masuk',
        'logout' => 'Keluar',
    ],
];
```

Akses: `Lang::get('messages.auth.login')` → `"Masuk"`

### File Terjemahan JSON

```json
// resources/lang/id.json
{
  "Welcome": "Selamat Datang",
  "Sign In": "Masuk",
  "Sign Out": "Keluar"
}
```

Akses: `Lang::get('Welcome')` → `"Selamat Datang"`

### Pluralization

```php
// resources/lang/en/items.php
return [
    'apples' => '{0} No apples|{1} One apple|[2,*] :count apples',
    'items'  => 'one item|:count items',
];

Lang::choice('items.apples', 0);   // "No apples"
Lang::choice('items.apples', 1);   // "One apple"
Lang::choice('items.apples', 5);   // "5 apples"
Lang::choice('items.items', 1);    // "one item"
Lang::choice('items.items', 10);   // "10 items"
```

### Placeholder Case Variants

```php
// 'greeting' => 'Hello :name, welcome to :APP!'
Lang::get('messages.greeting', ['name' => 'john', 'app' => 'mysite']);
// → "Hello john, welcome to MYSITE!"
// :name → lowercase, :NAME → UPPERCASE, :Name → Ucfirst
```

### Locale Detection

```php
// Auto-detect dari browser Accept-Language header
$detected = Lang::detectFromBrowser();  // 'id', 'en', etc.
Lang::autoDetect();                      // Detect & set otomatis

// Available locales
Lang::getAvailableLocales();  // ['en', 'id', 'ja']
Lang::isValidLocale('fr');    // false
```

### 🚀 Beyond Laravel

```php
// Temporarily switch locale
$result = Lang::withLocale('ja', function () {
    return Lang::get('messages.welcome'); // Japanese
}); // Otomatis kembali ke locale semula

// Track missing translations (QA tool)
Lang::handleMissing(fn($key, $locale) => error_log("Missing: $key [$locale]"));
$missing = Lang::getMissingKeys();

// Translation stats per locale
$stats = Lang::stats('id');
// ['messages' => 45, 'validation' => 120, '_json' => 30, '_total' => 195]
```

---

## Logging

Logging menyediakan convenience methods dan multi-channel logging.

### Penggunaan Dasar

```php
use TheFramework\App\Core\Logging;

Logging::info('User logged in', ['user_id' => 1]);
Logging::error('Payment failed', ['order_id' => 123]);
Logging::warning('Low stock', ['product_id' => 456, 'stock' => 2]);
Logging::debug('SQL query', ['sql' => $query, 'time' => '12ms']);
```

### Semua Log Levels (PSR-3)

```php
Logging::emergency('System is unusable!');
Logging::alert('Action must be taken immediately');
Logging::critical('Critical condition');
Logging::error('Runtime error');
Logging::warning('Warning');
Logging::notice('Normal but significant');
Logging::info('Informational');
Logging::debug('Debug info');
```

### Multiple Channels

```php
// Log ke channel tertentu
Logging::on('query')->info('SELECT * FROM users');
Logging::on('auth')->warning('Failed login attempt');
Logging::on('payment')->error('Transaction failed');

// Log ke multiple channels (stack)
Logging::stack(['app', 'slack'])->critical('Server down!');
```

### Channel Configuration

```php
Logging::configureChannel('query', [
    'driver'    => 'daily',     // single, daily, slack
    'level'     => 'debug',
    'max_files' => 7,           // Keep 7 hari
]);

Logging::configureChannel('audit', [
    'driver' => 'single',
    'format' => 'json',         // JSON format
]);
```

### 🚀 Beyond Laravel

```php
// Benchmark — otomatis log waktu eksekusi
$result = Logging::benchmark('User query', function () {
    return User::all();
}); // Log: "User query — 12.5ms"

// Log exception dengan full trace
try {
    riskyOperation();
} catch (\Throwable $e) {
    Logging::exception($e); // Auto-format: class, message, file:line, trace
}

// Conditional logging
Logging::logIf($isSlowQuery, 'warning', "Slow query: {$sql}", ['time' => $time]);

// Trace — auto-detect caller file & line
Logging::trace('Something happened here');

// Tail — baca N baris terakhir dari log
$lines = Logging::tail(20, 'app');

// Clear logs
Logging::clear('query');

// Summary
echo Logging::summary();
```

Output summary:

```
📊 Logging Summary
────────────────────────────────────────
   Directory: /path/to/storage/logs
   Default:   app
   📄 app: 5 file(s), 2.3MB
   📄 query: 3 file(s), 512KB
```

---

📖 **Selanjutnya:** [Cache](cache.md) | [Database](database.md) | [Queue](queue.md)
