# 📧 Mail Handler

The Framework menyediakan class `MailHandler` yang sangat bertenaga untuk memudahkan pengiriman email via SMTP menggunakan library **PHPMailer**, lengkap dengan fitur **Auto-Logging**, **Intelligent Fallback**, dan dukungan **Queue**.

---

## 📦 Persiapan

```bash
composer require phpmailer/phpmailer
```

> **Catatan IDE-Safe:** Instansiasi PHPMailer dilakukan secara dinamis untuk mencegah editor Anda menampilkan error merah. Fungsi baru akan memunculkan pesan error yang jelas jika library ini belum dipasang via Composer.

---

## ⚙️ Konfigurasi

Konfigurasi terpusat di `config/mail.php` yang mengambil data dari `.env`:

```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME="The Framework App"
```

> **Catatan Port & Enkripsi (Auto-Detection):**
> - Port `465` → Otomatis menggunakan **SSL (SMTPS)** — Direkomendasikan untuk Hostinger, GoDaddy
> - Port `587` → Otomatis menggunakan **TLS (STARTTLS)** — Direkomendasikan untuk Gmail, Mailtrap
> - Anda **tidak perlu** mengatur enkripsi secara manual, sistem akan mendeteksi otomatis berdasarkan port.

---

## 🚀 Cara Penggunaan

### 1. Pengiriman Instan

```php
use TheFramework\Handlers\MailHandler;

$mailer = new MailHandler();
$status = $mailer->send(
    'user@example.com',
    'Selamat Datang!',
    '<h1>Halo!</h1><p>Terima kasih telah bergabung.</p>'
);

if ($status) {
    echo "Email terkirim!";
} else {
    echo "Gagal, cek storage/logs/mail.log";
}
```

### 2. Pengiriman via Fluent API

Untuk sintaks yang lebih ringkas dan elegan:

```php
use TheFramework\Handlers\MailHandler;

MailHandler::to('user@example.com')->send(
    'Judul Email',
    '<p>Isi pesan email Anda di sini.</p>'
);
```

### 3. Pengiriman via Antrean (Queue)

Agar pengguna tidak menunggu proses SMTP selesai, masukkan ke antrean:

```php
MailHandler::to('user@example.com')->queue(
    'Konfirmasi Pesanan',
    'Pesanan Anda sedang kami proses.'
);
```

> Jika sistem Queue belum aktif, email akan otomatis dikirim secara instan (*fallback*).

### 4. Lampiran, CC, BCC

```php
$options = [
    'cc'  => 'manager@example.com',
    'bcc' => ['admin@example.com', 'audit@example.com'],
    'attachments' => [
        'storage/app/invoices/INV-001.pdf',
        ['path' => 'storage/app/photos/user.jpg', 'name' => 'FotoProfil.jpg']
    ],
];

$mailer->send('user@example.com', 'Invoice Anda', $htmlBody, $options);
```

---

## 🛡️ Fitur Keamanan & Deliverability

MailHandler secara otomatis menambahkan header-header berikut agar email Anda tidak masuk ke folder Spam:

| Header | Fungsi |
|--------|--------|
| `Reply-To` | Otomatis diset ke alamat pengirim (`from`) |
| `X-Mailer` | Branding `TheFramework Mailer v5.1` |
| `Priority` | Ditandai sebagai prioritas tinggi |
| `AltBody` | Otomatis membuat versi teks biasa dari HTML Anda |
| `CharSet` | UTF-8 (mendukung emoji & karakter internasional) |

---

## 📝 Auto-Logging

Semua aktivitas email (sukses maupun gagal) otomatis dicatat ke:

```
storage/logs/mail.log
```

Contoh isi log:
```
[2026-03-16 07:30:00] SUCCESS: Sent to user@gmail.com | Subject: Reset Password
[2026-03-16 07:31:15] MAIL ERROR to test@test.com: SMTP Error: Could not authenticate.
[2026-03-16 07:32:00] FAILED: Invalid recipient address: ''
```

> Berguna saat debugging tanpa perlu membuka email sungguhan.

---

## 🏠 Mode Localhost (Silent Fail)

Saat Anda mengembangkan di `localhost` atau `127.0.0.1`:
- Jika pengiriman email gagal (misal internet mati), sistem **tidak akan melempar Exception**.
- Sistem hanya mengembalikan `false` dan mencatat error ke `mail.log`.
- Aplikasi Anda tetap berjalan normal tanpa halaman error.

Behavior ini bisa diubah dengan menambahkan `'debug' => true` di `config/mail.php`.

---

## 🌐 Kompatibilitas SMTP Provider

| Provider | Host | Port | Enkripsi | Status |
|----------|------|------|----------|--------|
| **Hostinger** | `smtp.hostinger.com` | `465` | SSL (Auto) | ✅ Tested |
| **Gmail** | `smtp.gmail.com` | `587` | TLS (Auto) | ✅ Tested |
| **Mailtrap** | `smtp.mailtrap.io` | `2525` | TLS (Auto) | ✅ Tested |
| **GoDaddy** | `smtpout.secureserver.net` | `465` | SSL (Auto) | ✅ Supported |
| **Outlook** | `smtp.office365.com` | `587` | TLS (Auto) | ✅ Supported |
| **SendGrid** | `smtp.sendgrid.net` | `587` | TLS (Auto) | ✅ Supported |
| **Mailgun** | `smtp.mailgun.org` | `587` | TLS (Auto) | ✅ Supported |
| **Zoho** | `smtp.zoho.com` | `465` | SSL (Auto) | ✅ Supported |
| **Yahoo** | `smtp.mail.yahoo.com` | `465` | SSL (Auto) | ✅ Supported |

> Anda **tidak perlu** mengubah kode PHP untuk berpindah provider. Cukup ganti `.env`.

---

## 🔧 Konfigurasi Lanjutan (`config/mail.php`)

```php
return [
    'default' => [
        'host'      => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
        'port'      => $_ENV['MAIL_PORT'] ?? 587,
        'username'  => $_ENV['MAIL_USERNAME'] ?? '',
        'password'  => $_ENV['MAIL_PASSWORD'] ?? '',
        'from'      => $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'No Reply',
        'debug'     => false, // Set true untuk melihat SMTP debug output
    ],
];
```

---

## ❓ Troubleshooting

### Loading Lama / Timeout
- **Penyebab**: Mismatch port & enkripsi (misal port 465 tapi pakai STARTTLS).
- **Solusi**: Sistem sudah auto-detect. Pastikan port di `.env` sesuai dengan yang disarankan provider.

### Could Not Authenticate
- **Penyebab**: Password salah atau password mengandung karakter spesial.
- **Solusi**: Bungkus password dengan tanda petik di `.env`:
  ```env
  MAIL_PASSWORD="P@ssw0rd#Special!"
  ```

### Email Masuk Spam / Bounce
- **Penyebab**: Domain belum punya record SPF, DKIM, atau DMARC.
- **Solusi**: Setup DNS records di panel hosting Anda (biasanya ada tombol "Fix this" di Hostinger).
