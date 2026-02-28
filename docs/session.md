# 🔒 Session Manager

Framework menyediakan **SessionManager** yang aman dan lengkap untuk mengelola session PHP. Mendukung secure session, flash data, session timeout, dan manipulasi data terstruktur.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Secure Session](#secure-session)
3. [Data Manipulation](#data-manipulation)
4. [Flash Data](#flash-data)
5. [Session Lifecycle](#session-lifecycle)
6. [Konfigurasi](#konfigurasi)
7. [Method Reference](#method-reference)

---

## Overview

| Fitur                    | Deskripsi                                          |
| ------------------------ | -------------------------------------------------- |
| **Secure by Default**    | HttpOnly, SameSite, Strict Mode otomatis           |
| **Auto-Start**           | Session dimulai secara lazy (saat pertama diakses) |
| **Session Timeout**      | Auto-destroy session setelah periode inaktivitas   |
| **Flash Data**           | Data yang hanya bertahan 1 request (notifikasi)    |
| **Session Regeneration** | Regenerasi session ID untuk mencegah fixation      |
| **Custom Storage**       | Session disimpan di `storage/session/`             |

---

## Secure Session

Framework otomatis mengonfigurasi session dengan setting keamanan terbaik:

```php
use TheFramework\App\Http\SessionManager;

// Session otomatis di-start saat pertama kali diakses
// Konfigurasi keamanan diterapkan otomatis:
```

### Security Settings (Otomatis)

| Setting           | Nilai             | Manfaat                                         |
| ----------------- | ----------------- | ----------------------------------------------- |
| `cookie_httponly` | `true`            | Mencegah akses cookie via JavaScript (anti-XSS) |
| `cookie_secure`   | Auto-detect HTTPS | Cookie hanya dikirim via HTTPS                  |
| `use_strict_mode` | `true`            | Menolak session ID yang tidak dikenali          |
| `cookie_samesite` | `Lax`             | Mencegah CSRF via cross-site request            |

### Session Regeneration

```php
// Session ID otomatis di-regenerasi saat pertama kali dibuat
// Ini mencegah Session Fixation Attack

// Manual regeneration (misal setelah login)
SessionManager::regenerateSession();
```

### Session Timeout

```php
# .env
SESSION_LIFETIME=120  # Menit (default: 120 = 2 jam)
```

Jika user tidak aktif melebihi `SESSION_LIFETIME`, session otomatis di-destroy dan di-regenerasi.

---

## Data Manipulation

### Menyimpan Data

```php
// Simpan data ke session
SessionManager::put('user_id', 1);
SessionManager::put('cart', ['item1', 'item2', 'item3']);
SessionManager::put('preferences', ['theme' => 'dark', 'lang' => 'id']);
```

### Mengambil Data

```php
// Ambil data (dengan default value)
$userId = SessionManager::get('user_id');           // 1
$lang = SessionManager::get('lang', 'id');           // 'id' (default)
$missing = SessionManager::get('nonexistent', null); // null
```

### Cek Data

```php
// Cek apakah key ada di session
if (SessionManager::has('user_id')) {
    echo "User logged in!";
}
```

### Ambil Semua Data

```php
$allData = SessionManager::all();
// ['user_id' => 1, 'cart' => [...], 'preferences' => [...]]
```

### Hapus Data

```php
// Hapus satu key
SessionManager::forget('cart');

// Ambil dan hapus sekaligus (pull)
$cart = SessionManager::pull('cart');
// $cart berisi data cart, dan key 'cart' sudah dihapus dari session

// Hapus semua data session
SessionManager::flush();
```

---

## Flash Data

Flash data adalah data yang **hanya bertahan 1 request**. Sangat berguna untuk notifikasi, pesan sukses/error, dan old input setelah redirect.

### Membuat Flash Data

```php
// Di Controller
SessionManager::flash('success', 'Data berhasil disimpan!');
SessionManager::flash('error', 'Terjadi kesalahan');
SessionManager::flash('old', $_POST); // Old input untuk form

return redirect('/users');
```

### Mengakses Flash Data

```php
// Di View (request berikutnya)
$success = SessionManager::get('success'); // 'Data berhasil disimpan!'
// Pada request selanjutnya, data ini otomatis dihapus
```

### Mempertahankan Flash Data

```php
// Pertahankan SEMUA flash data untuk 1 request lagi
SessionManager::reflash();

// Pertahankan flash data tertentu saja
SessionManager::keep('success');
SessionManager::keep(['success', 'old']);
```

### Flow Flash Data

```
Request 1: SessionManager::flash('msg', 'Hello')
    → 'msg' masuk ke flash['new']

Request 2: ageFlashData() dipanggil
    → flash['new'] dipindah ke flash['old']
    → 'msg' masih bisa diakses via get()

Request 3: ageFlashData() dipanggil lagi
    → flash['old'] dihapus
    → 'msg' sudah tidak ada ❌
```

---

## Session Lifecycle

```
1. Request masuk
    │
    ├── SessionManager::ensureSession()
    │   ├── Cek: session sudah started?
    │   │   ├── Ya → lanjut
    │   │   └── Belum → startSecureSession()
    │   │       ├── Set cookie params (httponly, secure, samesite)
    │   │       ├── Set save path → storage/session/
    │   │       ├── session_start()
    │   │       ├── Regenerate ID (jika baru)
    │   │       └── Cek timeout → destroy jika expired
    │   │
    │   └── Inisialisasi struktur: $_SESSION['data'], $_SESSION['flash']
    │
    ├── ageFlashData()
    │   ├── Hapus flash data 'old'
    │   └── Pindahkan flash 'new' → 'old'
    │
    ├── ... proses request ...
    │
    └── Response dikirim
```

### Destroy Session (Logout)

```php
// Hapus session sepenuhnya (untuk logout)
SessionManager::destroySession();
// - session_unset(): Hapus semua data
// - session_destroy(): Hancurkan session
// - Hapus cookie session
```

---

## Konfigurasi

### Environment Variables

| Variable           | Default | Deskripsi                  |
| ------------------ | ------- | -------------------------- |
| `SESSION_LIFETIME` | `120`   | Durasi session dalam menit |

### Storage Path

Session files disimpan di:

```
storage/session/
```

Direktori ini dibuat otomatis dengan permission `0777`.

---

## Method Reference

### Session Start & Lifecycle

| Method                 | Deskripsi                             |
| ---------------------- | ------------------------------------- |
| `startSecureSession()` | Mulai session dengan konfigurasi aman |
| `regenerateSession()`  | Regenerasi session ID                 |
| `destroySession()`     | Hancurkan session sepenuhnya          |

### Data Manipulation

| Method                 | Return  | Deskripsi                 |
| ---------------------- | ------- | ------------------------- |
| `put($key, $value)`    | `void`  | Simpan data ke session    |
| `get($key, $default)`  | `mixed` | Ambil data dari session   |
| `all()`                | `array` | Ambil semua data session  |
| `has($key)`            | `bool`  | Cek apakah key ada        |
| `forget($key)`         | `void`  | Hapus satu key            |
| `pull($key, $default)` | `mixed` | Ambil dan hapus sekaligus |
| `flush()`              | `void`  | Hapus semua data + flash  |

### Flash Data

| Method                | Return | Deskripsi                       |
| --------------------- | ------ | ------------------------------- |
| `flash($key, $value)` | `void` | Simpan flash data (1 request)   |
| `reflash()`           | `void` | Pertahankan semua flash data    |
| `keep($keys)`         | `void` | Pertahankan flash data tertentu |

---

## 🔗 Related Documentation

- [Security](security.md) — CSRF Protection & secure headers
- [Middleware](middleware.md) — Auth Middleware (cek session)
- [Views](views.md) — Menampilkan flash messages
- [HTTP Module](http.md) — Request & Response

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
