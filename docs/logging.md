# 📝 Logging System

Framework menyediakan sistem logging multi-channel berbasis **Monolog** yang powerful. Mendukung daily rotation, multiple channels, Slack webhook, stack logging, performance benchmarking, dan banyak lagi.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Log Levels](#log-levels)
4. [Channels](#channels)
5. [Konfigurasi](#konfigurasi)
6. [Channel Logging](#channel-logging)
7. [Stack Logging](#stack-logging)
8. [Advanced Features](#advanced-features)
9. [Log Management](#log-management)
10. [Method Reference](#method-reference)

---

## Overview

| Fitur                   | Deskripsi                                                  |
| ----------------------- | ---------------------------------------------------------- |
| **Multi-Channel**       | Pisahkan log berdasarkan konteks (app, query, auth, dll)   |
| **Daily Rotation**      | File log otomatis dirotasi harian (configurable retention) |
| **Stack Logging**       | Kirim log ke beberapa channel sekaligus                    |
| **Slack Integration**   | Kirim alert ke Slack via webhook                           |
| **Benchmarking**        | Ukur execution time sebuah callback                        |
| **Conditional Logging** | Log hanya jika kondisi terpenuhi                           |
| **Exception Logging**   | Log exception dengan stack trace lengkap                   |
| **Trace Logging**       | Auto-detect file & line caller                             |
| **History Tracking**    | Simpan log history per-request untuk debugging             |
| **Log Management**      | Tail, clear, size check via API                            |

---

## Quick Start

```php
use TheFramework\App\Core\Logging;

// Log sederhana
Logging::info('User berhasil login', ['user_id' => 1]);
Logging::error('Gagal koneksi database', ['host' => 'localhost']);
Logging::warning('Disk space hampir penuh', ['usage' => '95%']);

// Log ke channel tertentu
Logging::on('auth')->info('Login sukses', ['email' => 'admin@example.com']);
Logging::on('query')->debug('SELECT * FROM users', ['time' => '2.3ms']);

// Log exception
try {
    // ... operasi berisiko
} catch (\Exception $e) {
    Logging::exception($e);
}
```

---

## Log Levels

Framework mengikuti standar **PSR-3** (RFC 5424) untuk log levels:

| Level         | Method                 | Deskripsi         | Contoh Use Case                |
| :------------ | :--------------------- | :---------------- | :----------------------------- |
| **EMERGENCY** | `Logging::emergency()` | System unusable   | Database server down total     |
| **ALERT**     | `Logging::alert()`     | Butuh aksi segera | Disk space habis               |
| **CRITICAL**  | `Logging::critical()`  | Kondisi kritis    | Database koneksi gagal         |
| **ERROR**     | `Logging::error()`     | Runtime error     | Query gagal dieksekusi         |
| **WARNING**   | `Logging::warning()`   | Peringatan        | Deprecated function dipakai    |
| **NOTICE**    | `Logging::notice()`    | Event signifikan  | User login dari IP baru        |
| **INFO**      | `Logging::info()`      | Informasi umum    | User berhasil registrasi       |
| **DEBUG**     | `Logging::debug()`     | Debug detail      | Dump variabel saat development |

### Minimum Level

```php
// Hanya log WARNING ke atas (ignore INFO, DEBUG, NOTICE)
Logging::setMinLevel('warning');

// Atau menggunakan Monolog constant
Logging::setMinLevel(\Monolog\Logger::WARNING);
```

---

## Channels

Channel adalah cara untuk **memisahkan log berdasarkan konteks**. Setiap channel menulis ke file log yang berbeda.

### Default Channel

```php
// Log ke default channel ('app')
Logging::info('Hello');
// → storage/logs/app-2026-02-28.log
```

### Membuat Channel Baru

```php
// Log ke channel 'auth'
Logging::on('auth')->info('Login sukses');
// → storage/logs/auth-2026-02-28.log

// Log ke channel 'query'
Logging::on('query')->debug('SELECT * FROM users');
// → storage/logs/query-2026-02-28.log

// Log ke channel custom
Logging::on('payment')->info('Payment received', ['amount' => 500000]);
// → storage/logs/payment-2026-02-28.log
```

### Mengubah Default Channel

```php
Logging::setDefaultChannel('api');
// Sekarang Logging::info() menulis ke api-YYYY-MM-DD.log
```

### Channel vs Lokasi File

| Channel         | File Log                              |
| --------------- | ------------------------------------- |
| `app` (default) | `storage/logs/app-2026-02-28.log`     |
| `auth`          | `storage/logs/auth-2026-02-28.log`    |
| `query`         | `storage/logs/query-2026-02-28.log`   |
| `payment`       | `storage/logs/payment-2026-02-28.log` |
| `slack`         | File log + Slack webhook              |

---

## Konfigurasi

### Mengatur Log Directory

```php
// Default: ROOT_DIR/storage/logs
Logging::setLogDir('/path/to/custom/logs');
```

### Mengonfigurasi Channel

Channel bisa dikonfigurasi dengan handler, format, dan level yang berbeda:

```php
Logging::configureChannel('query', [
    'handler' => 'daily',           // daily | single | slack
    'level' => 'debug',             // Minimum level untuk channel ini
    'days' => 7,                    // Retention (hanya untuk daily)
    'format' => 'json',             // line | json
]);

Logging::configureChannel('slack', [
    'handler' => 'slack',
    'level' => 'critical',
    'webhook' => 'https://hooks.slack.com/services/xxx/yyy/zzz',
    'channel' => '#alerts',
    'username' => 'Framework Bot',
]);
```

### Handler Types

| Handler  | Deskripsi                      | Config Key                       |
| -------- | ------------------------------ | -------------------------------- |
| `daily`  | Rotating file harian (default) | `days` → retention               |
| `single` | Satu file tanpa rotasi         | —                                |
| `slack`  | Kirim ke Slack via webhook     | `webhook`, `channel`, `username` |

### Format Options

| Format           | Deskripsi                                                           |
| ---------------- | ------------------------------------------------------------------- |
| `line` (default) | `[2026-02-28 12:00:00] app.INFO: Message {"context":"data"}`        |
| `json`           | `{"datetime":"...","channel":"app","level":"INFO","message":"..."}` |

---

## Channel Logging

### Fluent API

```php
$authLogger = Logging::on('auth');

$authLogger->info('Login attempt', ['email' => $email]);
$authLogger->warning('Failed login', ['email' => $email, 'ip' => $ip]);
$authLogger->error('Account locked', ['user_id' => $userId]);
```

### Contoh Real-World

```php
// Di AuthController
public function login() {
    $email = $_POST['email'];

    Logging::on('auth')->info('Login attempt', [
        'email' => $email,
        'ip'    => Helper::get_client_ip()
    ]);

    $user = User::where('email', $email)->first();

    if (!$user || !password_verify($_POST['password'], $user->password)) {
        Logging::on('auth')->warning('Login failed', ['email' => $email]);
        return redirect('/login', 'error', 'Invalid credentials');
    }

    Logging::on('auth')->info('Login success', [
        'user_id' => $user->id,
        'email'   => $email
    ]);

    // ... set session
}
```

---

## Stack Logging

Kirim satu log message ke **beberapa channel sekaligus**:

```php
// Log ke 'app' dan 'slack' secara bersamaan
Logging::stack(['app', 'slack'])->critical('Server overloaded!', [
    'cpu' => '99%',
    'memory' => '95%'
]);

// Log ke 'app', 'auth', dan 'query'
Logging::stack(['app', 'auth'])->error('Suspicious activity', [
    'user_id' => $userId,
    'action'  => 'mass_delete'
]);
```

---

## Advanced Features

### 🕐 Benchmark — Ukur Execution Time

```php
// Ukur waktu eksekusi query
$result = Logging::benchmark('Heavy query', function() {
    return User::with('posts', 'comments')->get();
});

// Log output: [DEBUG] Heavy query completed in 0.0234s

// Dengan custom level
$result = Logging::benchmark('API call', function() {
    return Http::get('https://api.example.com/data');
}, 'info');
```

### 🔍 Exception Logging

```php
try {
    $user->save();
} catch (\Exception $e) {
    // Log exception dengan stack trace lengkap
    Logging::exception($e);

    // Dengan level custom
    Logging::exception($e, 'critical');
}

// Output log:
// [ERROR] PDOException: SQLSTATE[23000] Duplicate entry 'user@mail.com'
//   File: /app/Models/User.php:45
//   Trace:
//     #0 /app/Controllers/UserController.php(30): ...
//     #1 /app/Http/Router.php(120): ...
```

### ✅ Conditional Logging

```php
// Log hanya jika kondisi terpenuhi
Logging::logIf($user->isAdmin(), 'info', 'Admin action', [
    'action' => 'delete_all_posts'
]);

// Berguna untuk:
Logging::logIf(config('app.debug'), 'debug', 'Query executed', [
    'sql' => $query
]);
```

### 📍 Trace Logging — Auto Caller Info

```php
// Otomatis menambahkan file & line number pemanggil
Logging::trace('Something happened here');

// Output: [DEBUG] Something happened here
//   → Called from UserController.php:42
```

### 📊 Log History

```php
// Ambil semua log history dari request ini
$all = Logging::getHistory();

// Filter berdasarkan level
$errors = Logging::getHistory('error');
$warnings = Logging::getHistory('warning');

// Clear history
Logging::clearHistory();
```

---

## Log Management

### 📖 Tail — Baca Log Terakhir

```php
// Baca 50 baris terakhir dari log 'app'
$lines = Logging::tail(50, 'app');
echo $lines;

// Baca 100 baris dari log 'auth'
$authLogs = Logging::tail(100, 'auth');
```

### 📏 Ukuran Log

```php
// Cek ukuran file log (bytes)
$size = Logging::getLogSize('app');
echo "Log size: " . round($size / 1024) . " KB";
```

### 🗑️ Clear Log

```php
// Hapus file log untuk channel tertentu
Logging::clear('app');
Logging::clear('query');
```

### 📋 Summary

```php
// Dapatkan ringkasan status logging
$summary = Logging::summary();
echo $summary;

// Output:
// ========================================
// LOGGING SUMMARY
// ========================================
// Default Channel : app
// Log Directory    : /var/www/storage/logs
// Min Level        : DEBUG
// Channels Active  : app, auth, query
// History Count    : 15
// ========================================
```

---

## Method Reference

### Logging Methods (PSR-3)

| Method                             | Deskripsi                        |
| ---------------------------------- | -------------------------------- |
| `Logging::emergency($msg, $ctx)`   | System is unusable               |
| `Logging::alert($msg, $ctx)`       | Action must be taken immediately |
| `Logging::critical($msg, $ctx)`    | Critical conditions              |
| `Logging::error($msg, $ctx)`       | Runtime errors                   |
| `Logging::warning($msg, $ctx)`     | Exceptional occurrences          |
| `Logging::notice($msg, $ctx)`      | Normal but significant events    |
| `Logging::info($msg, $ctx)`        | Interesting events               |
| `Logging::debug($msg, $ctx)`       | Detailed debug information       |
| `Logging::log($level, $msg, $ctx)` | Log with arbitrary level         |

### Channel & Stack

| Method                      | Return          | Deskripsi                          |
| --------------------------- | --------------- | ---------------------------------- |
| `Logging::on($channel)`     | `ChannelLogger` | Log ke channel tertentu            |
| `Logging::stack($channels)` | `StackLogger`   | Log ke multiple channels           |
| `Logging::channel($name)`   | `Logger`        | Get/create Monolog Logger instance |

### Configuration

| Method                                      | Deskripsi             |
| ------------------------------------------- | --------------------- |
| `Logging::setLogDir($path)`                 | Set direktori log     |
| `Logging::getLogDir()`                      | Get direktori log     |
| `Logging::setDefaultChannel($ch)`           | Ubah default channel  |
| `Logging::setMinLevel($level)`              | Set minimum log level |
| `Logging::configureChannel($name, $config)` | Konfigurasi channel   |

### Advanced

| Method                              | Return  | Deskripsi                    |
| ----------------------------------- | ------- | ---------------------------- |
| `Logging::benchmark($label, $fn)`   | `mixed` | Ukur execution time callback |
| `Logging::exception($e, $level)`    | `void`  | Log exception + stack trace  |
| `Logging::logIf($cond, $lvl, $msg)` | `void`  | Conditional logging          |
| `Logging::trace($msg, $level)`      | `void`  | Log dengan caller info       |

### Management

| Method                        | Return   | Deskripsi               |
| ----------------------------- | -------- | ----------------------- |
| `Logging::tail($lines, $ch)`  | `string` | Baca N baris terakhir   |
| `Logging::getLogSize($ch)`    | `int`    | Ukuran file log (bytes) |
| `Logging::clear($ch)`         | `void`   | Hapus file log          |
| `Logging::getHistory($level)` | `array`  | Get log request history |
| `Logging::clearHistory()`     | `void`   | Clear log history       |
| `Logging::summary()`          | `string` | Ringkasan status        |

---

## Best Practices

### ✅ DO

```php
// Gunakan context array untuk data terstruktur
Logging::info('Order placed', [
    'order_id' => $order->id,
    'amount'   => $order->total,
    'user_id'  => $user->id
]);

// Pisahkan channel berdasarkan domain
Logging::on('payment')->info('Payment received');
Logging::on('auth')->warning('Failed login');

// Log exception dengan stack trace
Logging::exception($e);
```

### ❌ DON'T

```php
// Jangan concat data ke message
Logging::info("Order $orderId for user $userId placed"); // ❌

// Jangan log data sensitif
Logging::info('Login', ['password' => $password]); // ❌ BAHAYA!

// Jangan log di hot loop tanpa kondisi
foreach ($items as $item) {
    Logging::debug("Processing item $item->id"); // ❌ Bisa ribuan log
}
```

---

## 🔗 Related Documentation

- [Error Handling](error-handling.md) — Exception patterns
- [Exceptions](exceptions.md) — Custom exception classes
- [Performance](performance.md) — Optimisasi logging
- [Core](core.md) — Overview modul core

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
