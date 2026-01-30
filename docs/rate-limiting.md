# 🚦 Rate Limiting

Rate Limiting membatasi berapa kali seorang pengguna dapat melakukan akses ke rute tertentu dalam jangka waktu tertentu. Ini sangat penting untuk mencegah serangan brute-force, spamming, dan penyalahgunaan API.

---

## 🚀 Cara Penggunaan

Gunakan class `RateLimiter` di dalam Controller Anda.

### Contoh Sederhana (Login Protection)

```php
use TheFramework\App\RateLimiter;
use TheFramework\Helpers\Helper;

public function login() {
    $ip = Helper::get_client_ip();
    $key = 'login-attempt-' . $ip;

    // Batasi 5 kali percobaan dalam 60 detik
    if (RateLimiter::tooManyAttempts($key, 5, 60)) {
        $seconds = RateLimiter::availableIn($key);
        Helper::set_flash('error', "Terlalu banyak mencoba. Silakan tunggu $seconds detik.");
        return Helper::redirect('/login');
    }

    // Jika gagal login, catat attempt
    if (!$auth_success) {
        RateLimiter::hit($key);
        // ...
    } else {
        // Jika sukses, hapus catatan limit
        RateLimiter::clear($key);
    }
}
```

---

## ⚙️ Driver & Penyimpanan

Secara default, `RateLimiter` menggunakan driver **File** yang menyimpan data di `storage/cache/ratelimit/`.

### Konfigurasi `.env`

(Fitur Redis/APCu coming soon)

```env
# Saat ini hanya mendukung driver file
RATE_LIMIT_DRIVER=file
```

---

## 🌐 API Rate Limiting

Untuk API, Anda bisa menggunakan middleware atau memanggilnya langsung di rute:

```php
Router::get('/api/data', function() {
    if (RateLimiter::tooManyAttempts('api-access-' . Helper::get_client_ip(), 100, 3600)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    // ...
});
```

---

## 🛠️ Method Referensi

| Method                                | Deskripsi                                    |
| :------------------------------------ | :------------------------------------------- |
| `tooManyAttempts($key, $max, $decay)` | Cek apakah sudah melebihi batas.             |
| `hit($key, $decay)`                   | Tambah jumlah percobaan.                     |
| `availableIn($key)`                   | Cek sisa waktu (detik) sampai limit direset. |
| `clear($key)`                         | Hapus catatan limit untuk key tersebut.      |
| `remaining($key, $max)`               | Sisa jatah percobaan yang tersedia.          |

---

---

## 🛠️ Developer Experience

Sangat menyebalkan jika kita terkena blokir (Rate Limit) saat sedang melakukan _debug_ atau _coding_ di localhost. Oleh karena itu, Framework v5.0.0 ini memperkenalkan fitur **Bypass**:

Rate Limiting akan secara otomatis **NONAKTIF** jika:

- `.env` memiliki `APP_ENV=local`
- Atau `.env` memiliki `APP_DEBUG=true`

Ini memungkinkan Anda melakukan refresh halaman berkali-kali tanpa khawatir terkena pesan "Too many requests" di lingkungan pengembangan.

---

## 🔒 Security Tips

Selalu gunakan kombinasi **IP Address** dan **Action Name** sebagai `key` agar limit tidak tertukar antar pengguna.
