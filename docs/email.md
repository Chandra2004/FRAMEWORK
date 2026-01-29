# 📧 Email Handler

The Framework menyediakan class `EmailHandler` untuk memudahkan pengiriman email via SMTP menggunakan library **PHPMailer**.

---

## 📦 Persiapan

Pastikan Anda sudah menginstal library PHPMailer melalui Composer:

```bash
composer require phpmailer/phpmailer
```

---

## ⚙️ Konfigurasi `.env`

Tambahkan kredensial SMTP Anda ke file `.env`:

```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME="My App Name"
```

---

## 🚀 Cara Penggunaan

Gunakan method statis `EmailHandler::send()` untuk mengirim email.

### Penggunaan Dasar

```php
use TheFramework\Config\EmailHandler;

try {
    $to = 'user@example.com';
    $subject = 'Selamat Datang!';
    $body = '<h1>Halo!</h1><p>Terima kasih telah mendaftar di aplikasi kami.</p>';

    $status = EmailHandler::send($to, $subject, $body);

    if ($status) {
        echo "Email berhasil dikirim!";
    }
} catch (\Exception $e) {
    echo "Gagal mengirim email: " . $e->getMessage();
}
```

### Opsi Tambahan (Lampiran, CC, BCC)

Anda bisa menyertakan opsi tambahan melalui argumen keempat:

```php
$options = [
    'cc'          => ['manager@example.com'],
    'bcc'         => ['admin@example.com'],
    'attachments' => [
        'path/to/invoice.pdf',
        'path/to/image.jpg'
    ]
];

EmailHandler::send($to, $subject, $body, $options);
```

---

## 🛠️ Troubleshooting

- **Connection Timeout:** Pastikan port (misal: 465 atau 587) tidak diblokir oleh firewall hosting Anda.
- **Gmail SMTP:** Jika menggunakan Gmail, Anda harus menggunakan _App Password_ dan mengaktifkan 2FA.
- **Shared Hosting:** Beberapa hosting gratis (seperti InfinityFree) membatasi fungsi SMTP eksternal. Gunakan webmail bawaan hosting jika perlu.
