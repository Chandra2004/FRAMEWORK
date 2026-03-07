# 💳 Payment Handler (Midtrans)

`PaymentHandler` adalah wrapper premium untuk **Midtrans Snap API**, dirancang untuk menangani alur pembayaran dengan sangat mudah dan aman.

---

## 📦 Persiapan

Instal SDK resmi Midtrans via Composer:

```bash
composer require midtrans/midtrans-php
```

---

## ⚙️ Konfigurasi

Konfigurasi dikelola secara terpusat di `config/payment.php`:

**File `.env`:**
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

---

## 🚀 Cara Penggunaan

### 1. Mengambil Snap Token (Frontend)
Snap Token digunakan oleh library `snap.js` di browser untuk memunculkan pop-up pembayaran.

```php
use TheFramework\Handlers\PaymentHandler;

$payment = new PaymentHandler();

$payload = [
    'transaction_details' => [
        'order_id' => 'TRX-' . time(),
        'gross_amount' => 50000,
    ],
    'customer_details' => [
        'first_name' => 'Budi',
        'email' => 'budi@mail.com'
    ]
];

$snapToken = $payment->getSnapToken($payload);
```

### 2. Mengecek Status Transaksi
Berbeda dengan Laravel standar yang memerlukan pengecekan manual via Curl, `PaymentHandler` menyediakan fungsi instan untuk mengecek status pesanan langsung ke server Midtrans.

```php
$status = $payment->status('TRX-123456');

echo "Status: " . $status->transaction_status;
echo "Tipe Bayar: " . $status->payment_type;
```

### 3. Handle Notification (Webhook)
Midtrans akan mengirimkan data POST ke URL notifikasi Anda saat pembayaran selesai.

```php
public function notify() {
    $payment = new PaymentHandler();
    $notif = $payment->handleNotification();

    if ($notif->transaction_status == 'settlement') {
        // Logika: Tandai pesanan sebagai LUNAS di database
    }
}
```

---

## ✨ Fitur Unggulan
- **Auto-Initialization**: Anda tidak perlu memanggil `Midtrans\Config` secara manual di setiap fungsi.
- **Direct Status API**: Integrasi fungsi `status()` untuk mempermudah sinkronisasi database manual.
- **Production-Ready**: Cukup ubah `MIDTRANS_IS_PRODUCTION` di `.env`, konfigurasi lainnya akan menyesuaikan secara otomatis.
