# 💳 Payment Gateway (Midtrans)

Framework ini menyertakan wrapper sederhana untuk **Midtrans Snap API**, memungkinkan Anda menerima pembayaran dengan berbagai metode (Bank Transfer, E-Wallet, Kartu Kredit) secara mudah.

---

## 📦 Persiapan

Instal SDK resmi Midtrans via Composer:

```bash
composer require midtrans/midtrans-php
```

---

## ⚙️ Konfigurasi `.env`

Dapatkan server key dari [Dashboard Midtrans](https://dashboard.midtrans.com/):

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

---

## 🚀 Cara Penggunaan

### 1. Membuat Snap Token

Snap Token diperlukan oleh frontend untuk menampilkan pop-up pembayaran Midtrans.

```php
use TheFramework\Config\PaymentHandler;

$orderId = 'ORD-' . time();
$amount = 150000;
$customer = [
    'first_name' => 'Chandra',
    'email'      => 'chandra@example.com',
    'phone'      => '08123456789'
];

try {
    $snapToken = PaymentHandler::createSnapToken($orderId, $amount, $customer);
} catch (\Exception $e) {
    die("Gagal membuat token: " . $e->getMessage());
}
```

### 2. Integrasi Frontend

Gunakan Snap Token di View Anda:

```html
<button id="pay-button">Bayar Sekarang</button>

<script
  src="https://app.sandbox.midtrans.com/snap/snap.js"
  data-client-key="<?= Config::get('MIDTRANS_CLIENT_KEY') ?>"
></script>
<script>
  document.getElementById("pay-button").onclick = function () {
    snap.pay("<?= $snapToken ?>", {
      onSuccess: function (result) {
        alert("Pembayaran Berhasil!");
      },
      onPending: function (result) {
        alert("Menunggu Pembayaran...");
      },
      onError: function (result) {
        alert("Pembayaran Gagal!");
      },
    });
  };
</script>
```

### 3. Menghandle Webhook (Notification)

Buat controller khusus untuk menerima notifikasi status pembayaran dari server Midtrans.

```php
// rute: POST /payments/notification
public function notify() {
    try {
        $notif = PaymentHandler::handleNotification();

        $order_id = $notif->order_id;
        $status = $notif->transaction_status;
        $type = $notif->payment_type;

        if ($status == 'settlement') {
            // Update database: Order telah dibayar!
        } elseif ($status == 'pending') {
            // Update database: Menunggu pembayaran
        }

        return "OK";
    } catch (\Exception $e) {
        return "Error";
    }
}
```

---

## 🔒 Keamanan

Class `PaymentHandler` otomatis mengaktifkan fitur **Sanitization** dan **3DS** untuk keamanan transaksi kartu kredit. Pastikan Anda melakukan verifikasi ulang di server saat menerima notifikasi webhook.
