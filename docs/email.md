# 📧 Mail Handler

The Framework menyediakan class `MailHandler` yang sangat bertenaga untuk memudahkan pengiriman email via SMTP menggunakan library **PHPMailer**, lengkap dengan dukungan antrean (Queue) dan lampiran ganda.

---

## 📦 Persiapan

Pastikan Anda sudah menginstal library PHPMailer melalui Composer:

```bash
composer require phpmailer/phpmailer
```

---

## ⚙️ Konfigurasi

Berbeda dengan sistem lama, konfigurasi kini terpusat di file `config/mail.php` yang mengambil data dari `.env`:

**File `.env`:**
```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME="The Framework App"
```

---

## 🚀 Cara Penggunaan

### 1. Pengiriman Instan
Gunakan instance `MailHandler` untuk mengirim email secara langsung.

```php
use TheFramework\Handlers\MailHandler;

try {
    $mailer = new MailHandler();
    $status = $mailer->send(
        'user@example.com', 
        'Selamat Datang!', 
        '<h1>Halo!</h1><p>Terima kasih telah bergabung.</p>'
    );

    if ($status) echo "Email terkirim!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### 2. Pengiriman via Antrean (Queue)
Untuk performa aplikasi yang lebih cepat, Anda bisa memasukkan pengiriman email ke dalam antrean sehingga pengguna tidak perlu menunggu proses SMTP selesai.

```php
use TheFramework\Handlers\MailHandler;

// Gunakan syntax fluent 'to' -> 'queue'
MailHandler::to('user@example.com')->queue(
    'Konfirmasi Pesanan',
    'Pesanan Anda sedang kami proses.'
);
```

### 3. Lampiran & Opsi Lanjutan
Anda bisa menyertakan lampiran file, CC, dan BCC dengan mudah:

```php
$options = [
    'cc'  => 'manager@example.com',
    'bcc' => ['admin@example.com', 'audit@example.com'],
    'attachments' => [
        'storage/app/invoices/INV-001.pdf', // Path string biasa
        ['path' => 'storage/app/photos/user.jpg', 'name' => 'FotoProfil.jpg'] // Dengan nama kustom
    ],
    'reply_to' => 'support@example.com'
];

$mailer->send($to, $subject, $body, $options);
```

---

## 🛠️ Analisis Fitur (BEYOND Laravel)
- **Queue Simulation**: Otomatis mendeteksi jika Driver Queue aktif. Jika tidak aktif, dia akan otomatis *fallback* ke pengiriman instan tanpa merusak kode Anda.
* **Auto-AltBody**: Secara otomatis membuat versi teks murni (strip tags) dari body HTML Anda untuk kompatibilitas email client lama.
* **Global Config**: Tidak perlu memasukkan kredensial berkali-kali, cukup setting sekali di `config/mail.php`.
