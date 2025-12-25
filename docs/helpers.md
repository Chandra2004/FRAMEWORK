> **Version**: 4.0.0 | **Author**: Chandra Tri A | **Updated**: 2025

# 🛠️ Helpers & Utilities Guide

Dokumentasi lengkap fungsi bantuan (helpers) dan library konfigurasi eksternal.

---

## 1. Global Helper Functions

Fungsi-fungsi ini dapat dipanggil di mana saja (Controller, View, Model, dll) tanpa perlu mengimport class.

### 🌐 URL & Navigation

| Fungsi                          | Deskripsi                                        | Contoh                                          |
| :------------------------------ | :----------------------------------------------- | :---------------------------------------------- |
| `url($path)`                    | Menghasilkan URL lengkap berdasarkan `BASE_URL`. | `url('/users')` → `http://localhost:8080/users` |
| `redirect($url, $status, $msg)` | Redirect ke halaman lain dengan flash message.   | `redirect('/login', 'error', 'Login gagal')`    |
| `request($key, $default)`       | Mengambil input dari `$_GET` atau `$_POST`.      | `$name = request('name', 'Guest')`              |
| `csrf_token()`                  | Mengambil token CSRF saat ini.                   | `<input value="{{ csrf_token() }}">`            |

### 📝 String & Formatting

| Fungsi            | Deskripsi                           | Contoh                                 |
| :---------------- | :---------------------------------- | :------------------------------------- |
| `rupiah($number)` | Format angka ke mata uang Rupiah.   | `rupiah(50000)` → `Rp 50.000`          |
| `updateAt()`      | Timestamp sekarang (Timezone DB).   | `2024-12-25 10:00:00`                  |
| `uuid()`          | Generate UUID v4 random (36 chars). | `550e8400-e29b-41d4-a716-446655440000` |

### ⚡ Session & Flash Data

| Fungsi                  | Deskripsi                                | Contoh                            |
| :---------------------- | :--------------------------------------- | :-------------------------------- |
| `set_flash($key, $val)` | Set flash data (hilang setelah refresh). | `set_flash('info', 'Data saved')` |
| `get_flash($key)`       | Ambil dan hapus flash data.              | `$msg = get_flash('info')`        |

---

## 2. Helper Class Methods

Tersedia via `TheFramework\Helpers\Helper`.

### `Helper::json($data, $statusCode)`

Mengembalikan response JSON dengan header yang benar dan menghentikan eksekusi script.

```php
Helper::json(['status' => 'success', 'data' => $users], 200);
```

### `Helper::slugify($text)`

Mengubah teks menjadi slug URL-friendly.

```php
Helper::slugify('Judul Berita Hari Ini'); // judul-berita-hari-ini
```

### `Helper::random_string($length)`

Membuat string acak hex.

```php
$token = Helper::random_string(32);
```

---

## 3. Email Handler (SMTP)

Mengirim email menggunakan wrapper PHPMailer.

### Konfigurasi `.env`

```ini
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@myapp.com
MAIL_FROM_NAME="My App Name"
```

### Penggunaan

```php
use TheFramework\Config\EmailHandler;

try {
    // Kirim Email Sederhana
    EmailHandler::send(
        'user@example.com',
        'Selamat Datang',
        '<h1>Halo!</h1><p>Terima kasih sudah mendaftar.</p>'
    );

    // Kirim dengan Lampiran (contoh implementasi lanjutan bisa ditambahkan)

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## 4. Upload Handler

Menangani upload file dengan validasi keamanan ketat (MIME check) dan rename otomatis.

### Fitur Utama

- **Secure**: Cek MIME type, bukan cuma ekstensi.
- **Auto Rename**: File di-rename random string agar tidak tertimpa.
- **Support**: `$_FILES` array handling.

### Penggunaan

```php
use TheFramework\Config\UploadHandler;

// Di Controller
if (isset($_FILES['avatar'])) {

    // 1. Proses Upload
    $result = UploadHandler::upload($_FILES['avatar'], [
        'uploadDir' => '/user-pictures',    // Relatif terhadap root/private-uploads atau public/assets
        'allowedTypes' => ['jpg', 'png', 'webp'],
        'maxSize' => 2 * 1024 * 1024        // 2 MB
    ]);

    // 2. Cek Hasil
    if ($result['success']) {
        // Simpan path ke database
        $user->profile_picture = $result['path'];
        $user->save();
        echo "Upload sukses: " . $result['path'];
    } else {
        // Tampilkan Error
        echo "Gagal: " . $result['error'];
    }
}

// 3. Hapus File Lama
UploadHandler::delete($oldFilePath);
```

---

## 5. Payment Handler (Midtrans)

Integrasi bawaan dengan Payment Gateway Midtrans (Snap API).

### Konfigurasi `.env`

```ini
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
```

### Mendapatkan Snap Token

```php
use TheFramework\Config\PaymentHandler;

$params = [
    'transaction_details' => [
        'order_id' => 'ORDER-' . time(),
        'gross_amount' => 100000,
    ],
    'customer_details' => [
        'first_name' => 'Budi',
        'email' => 'budi@example.com',
    ]
];

try {
    $snapToken = PaymentHandler::createSnapToken($params);
    // Kirim token ini ke frontend untuk membuka popup pembayaran
    return Helper::json(['token' => $snapToken]);
} catch (Exception $e) {
    return Helper::json(['error' => $e->getMessage()], 500);
}
```

### Handle Notification (Webhook)

Di Controller untuk callback Midtrans:

```php
public function notificationCheck()
{
    $notification = PaymentHandler::handleNotification();

    $orderId = $notification->order_id;
    $status = $notification->transaction_status;

    if ($status == 'settlement') {
        // Update status order jadi LUNAS
    }
}
```
