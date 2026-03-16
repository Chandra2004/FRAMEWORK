# 💳 Payment Handler — Universal Multi-Driver

`PaymentHandler` adalah sistem pembayaran **Multi-Driver** yang mendukung **20 Payment Gateway** sekaligus (10 Nasional 🇮🇩 + 10 Internasional 🌍). Developer cukup install SDK, isi `.env`, dan langsung pakai.

---

## 📦 Persiapan

Install SDK sesuai gateway yang Anda butuhkan:

| Gateway | Perintah Install | Tipe |
|---------|-----------------|------|
| **Midtrans** | `composer require midtrans/midtrans-php` | 🇮🇩 SDK |
| **Xendit** | `composer require xendit/xendit-php` | 🇮🇩 SDK |
| **DOKU** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **Faspay** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **NICEPAY** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **iPay88** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **iPaymu** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **OY!** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **DANA** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **Espay** | *Tidak perlu install* (REST API) | 🇮🇩 REST |
| **Stripe** | `composer require stripe/stripe-php` | 🌍 SDK |
| **PayPal** | *Tidak perlu install* (REST API v2) | 🌍 REST |
| **Adyen** | `composer require adyen/php-api-library` | 🌍 SDK |
| **Square** | `composer require square/square` | 🌍 SDK |
| **Authorize.Net** | *Tidak perlu install* (REST API) | 🌍 REST |
| **Braintree** | `composer require braintree/braintree_php` | 🌍 SDK |
| **Checkout.com** | `composer require checkout/checkout-sdk-php` | 🌍 SDK |
| **Worldpay** | *Tidak perlu install* (REST API) | 🌍 REST |
| **Airwallex** | *Tidak perlu install* (REST API) | 🌍 REST |
| **Helcim** | *Tidak perlu install* (REST API) | 🌍 REST |

> Gateway bertipe **REST** tidak memerlukan package Composer tambahan karena menggunakan cURL bawaan PHP.

---

## ⚙️ Konfigurasi

### Langkah 1: Pilih Driver di `.env`

```env
PAYMENT_GATEWAY=midtrans
```

### Langkah 2: Isi Kredensial Driver yang Dipilih

Buka `.env.example` sebagai referensi, lalu uncomment dan isi bagian yang sesuai.

<details>
<summary><b>🇮🇩 Midtrans</b></summary>

```env
PAYMENT_GATEWAY=midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```
Dashboard: https://dashboard.midtrans.com
</details>

<details>
<summary><b>🇮🇩 Xendit</b></summary>

```env
PAYMENT_GATEWAY=xendit
XENDIT_SECRET_KEY=xnd_development_xxxxxxxxxxxx
XENDIT_PUBLIC_KEY=xnd_public_development_xxxxxxxxxxxx
XENDIT_WEBHOOK_TOKEN=your_webhook_token
```
Dashboard: https://dashboard.xendit.co
</details>

<details>
<summary><b>🇮🇩 DOKU</b></summary>

```env
PAYMENT_GATEWAY=doku
DOKU_CLIENT_ID=your_client_id
DOKU_SECRET_KEY=your_secret_key
DOKU_BASE_URL=https://api-sandbox.doku.com
```
Dashboard: https://dashboard.doku.com
</details>

<details>
<summary><b>🇮🇩 Faspay</b></summary>

```env
PAYMENT_GATEWAY=faspay
FASPAY_MERCHANT_ID=your_merchant_id
FASPAY_MERCHANT_KEY=your_merchant_key
FASPAY_USER_ID=your_user_id
FASPAY_PASSWORD=your_password
FASPAY_BASE_URL=https://debit-sandbox.faspay.co.id
```
Dashboard: https://business.faspay.co.id
</details>

<details>
<summary><b>🇮🇩 NICEPAY</b></summary>

```env
PAYMENT_GATEWAY=nicepay
NICEPAY_MERCHANT_ID=your_merchant_id
NICEPAY_MERCHANT_KEY=your_merchant_key
NICEPAY_BASE_URL=https://dev.nicepay.co.id
```
Dashboard: https://bo.nicepay.co.id
</details>

<details>
<summary><b>🇮🇩 iPay88</b></summary>

```env
PAYMENT_GATEWAY=ipay88
IPAY88_MERCHANT_CODE=your_merchant_code
IPAY88_MERCHANT_KEY=your_merchant_key
IPAY88_BASE_URL=https://sandbox.ipay88.co.id
```
Dashboard: https://www.ipay88.co.id
</details>

<details>
<summary><b>🇮🇩 iPaymu</b></summary>

```env
PAYMENT_GATEWAY=ipaymu
IPAYMU_VA=your_va_number
IPAYMU_API_KEY=your_api_key
IPAYMU_BASE_URL=https://sandbox.ipaymu.com/api/v2
```
Dashboard: https://my.ipaymu.com
</details>

<details>
<summary><b>🇮🇩 OY! Indonesia</b></summary>

```env
PAYMENT_GATEWAY=oy
OY_API_KEY=your_api_key
OY_USERNAME=your_username
OY_BASE_URL=https://api-stg.oyindonesia.com
```
Dashboard: https://business.oyindonesia.com
</details>

<details>
<summary><b>🇮🇩 DANA</b></summary>

```env
PAYMENT_GATEWAY=dana
DANA_CLIENT_ID=your_client_id
DANA_CLIENT_SECRET=your_client_secret
DANA_PUBLIC_KEY=your_public_key
DANA_BASE_URL=https://api-sandbox.saas.dana.id
```
Dashboard: https://dashboard.dana.id
</details>

<details>
<summary><b>🇮🇩 Espay</b></summary>

```env
PAYMENT_GATEWAY=espay
ESPAY_MERCHANT_ID=your_merchant_id
ESPAY_API_KEY=your_api_key
ESPAY_SIGNATURE_KEY=your_signature_key
ESPAY_BASE_URL=https://sandbox-kit.espay.id
```
Dashboard: https://kit.espay.id
</details>

<details>
<summary><b>🌍 Stripe</b></summary>

```env
PAYMENT_GATEWAY=stripe
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
```
Dashboard: https://dashboard.stripe.com
</details>

<details>
<summary><b>🌍 PayPal</b></summary>

```env
PAYMENT_GATEWAY=paypal
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_MODE=sandbox
```
Dashboard: https://developer.paypal.com
</details>

<details>
<summary><b>🌍 Adyen</b></summary>

```env
PAYMENT_GATEWAY=adyen
ADYEN_API_KEY=your_api_key
ADYEN_MERCHANT_ACCOUNT=YourMerchantAccount
ADYEN_ENVIRONMENT=test
ADYEN_LIVE_PREFIX=
```
Dashboard: https://ca-test.adyen.com
</details>

<details>
<summary><b>🌍 Square</b></summary>

```env
PAYMENT_GATEWAY=square
SQUARE_ACCESS_TOKEN=your_access_token
SQUARE_LOCATION_ID=your_location_id
SQUARE_ENVIRONMENT=sandbox
```
Dashboard: https://developer.squareup.com
</details>

<details>
<summary><b>🌍 Authorize.Net</b></summary>

```env
PAYMENT_GATEWAY=authorizenet
AUTHNET_API_LOGIN_ID=your_api_login_id
AUTHNET_TRANSACTION_KEY=your_transaction_key
AUTHNET_SANDBOX=true
```
Dashboard: https://sandbox.authorize.net
</details>

<details>
<summary><b>🌍 Braintree</b></summary>

```env
PAYMENT_GATEWAY=braintree
BRAINTREE_MERCHANT_ID=your_merchant_id
BRAINTREE_PUBLIC_KEY=your_public_key
BRAINTREE_PRIVATE_KEY=your_private_key
BRAINTREE_ENVIRONMENT=sandbox
```
Dashboard: https://sandbox.braintreegateway.com
</details>

<details>
<summary><b>🌍 Checkout.com</b></summary>

```env
PAYMENT_GATEWAY=checkoutcom
CHECKOUTCOM_SECRET_KEY=sk_test_xxxxxxxxxxxx
CHECKOUTCOM_PUBLIC_KEY=pk_test_xxxxxxxxxxxx
CHECKOUTCOM_ENVIRONMENT=sandbox
```
Dashboard: https://hub.checkout.com
</details>

<details>
<summary><b>🌍 Worldpay</b></summary>

```env
PAYMENT_GATEWAY=worldpay
WORLDPAY_SERVICE_KEY=your_service_key
WORLDPAY_CLIENT_KEY=your_client_key
WORLDPAY_BASE_URL=https://try.access.worldpay.com
```
Dashboard: https://online.worldpay.com
</details>

<details>
<summary><b>🌍 Airwallex</b></summary>

```env
PAYMENT_GATEWAY=airwallex
AIRWALLEX_API_KEY=your_api_key
AIRWALLEX_CLIENT_ID=your_client_id
AIRWALLEX_BASE_URL=https://api-demo.airwallex.com/api/v1
```
Dashboard: https://www.airwallex.com
</details>

<details>
<summary><b>🌍 Helcim</b></summary>

```env
PAYMENT_GATEWAY=helcim
HELCIM_API_TOKEN=your_api_token
HELCIM_ACCOUNT_ID=your_account_id
HELCIM_BASE_URL=https://api.helcim.com/v2
```
Dashboard: https://www.helcim.com
</details>

---

## 🚀 Cara Penggunaan

### API Universal (Sama untuk Semua Driver)

Semua 20 driver memiliki **3 method yang sama**:

| Method | Fungsi | Return |
|--------|--------|--------|
| `createTransaction($payload)` | Buat transaksi baru | Token / URL / Transaction ID |
| `checkStatus($orderId)` | Cek status pembayaran | Object status |
| `handleWebhook($data)` | Verifikasi notifikasi webhook | Object notifikasi |

---

### 1. Menggunakan Driver Default (dari `.env`)

```php
use TheFramework\Handlers\PaymentHandler;

$payment = new PaymentHandler();
$result = $payment->createTransaction($payload);
```

### 2. Memilih Driver Secara Manual

```php
// 🇮🇩 Midtrans
$token = PaymentHandler::driver('midtrans')->createTransaction([
    'transaction_details' => [
        'order_id' => 'TRX-' . time(),
        'gross_amount' => 150000,
    ],
    'customer_details' => [
        'first_name' => 'Budi',
        'email' => 'budi@mail.com',
    ],
]);

// 🌍 Stripe
$url = PaymentHandler::driver('stripe')->createTransaction([
    'items' => [
        ['price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => 'Premium Plan'],
            'unit_amount' => 2000,
        ], 'quantity' => 1],
    ],
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
]);
```

### 3. Cek Status Transaksi

```php
// Midtrans
$status = PaymentHandler::driver('midtrans')->checkStatus('TRX-123456');
echo $status->transaction_status; // settlement, pending, expire, dll.

// Xendit
$status = PaymentHandler::driver('xendit')->checkStatus('INV-001');

// PayPal
$status = PaymentHandler::driver('paypal')->checkStatus('ORDER-ABC123');
```

### 4. Handle Webhook / Notifikasi

Buat route khusus untuk menerima callback dari payment gateway:

```php
// routes/web.php
Router::add('POST', '/payment/webhook', PaymentController::class, 'webhook');
```

```php
// PaymentController.php
use TheFramework\Handlers\PaymentHandler;

class PaymentController extends Controller
{
    public function webhook()
    {
        $payment = new PaymentHandler(); // Otomatis pakai driver dari .env
        $notification = $payment->handleWebhook();

        // Proses berdasarkan status
        switch ($notification->transaction_status ?? $notification->status ?? '') {
            case 'settlement':
            case 'capture':
            case 'PAID':
                // Tandai pesanan sebagai LUNAS
                break;
            case 'pending':
            case 'PENDING':
                // Tandai pesanan sebagai MENUNGGU
                break;
            case 'expire':
            case 'EXPIRED':
                // Tandai pesanan sebagai KEDALUWARSA
                break;
        }

        // Kembalikan response 200 OK agar gateway tidak mengirim ulang
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }
}
```

---

## 🧩 Contoh Lengkap Per Gateway

### 🇮🇩 Midtrans — Snap Token + Frontend

**Backend (Controller):**
```php
$payment = PaymentHandler::driver('midtrans');
$snapToken = $payment->createTransaction([
    'transaction_details' => [
        'order_id' => 'ORD-' . time(),
        'gross_amount' => 75000,
    ],
    'customer_details' => [
        'first_name' => 'Ahmad',
        'email' => 'ahmad@mail.com',
        'phone' => '08123456789',
    ],
    'item_details' => [
        ['id' => 'item1', 'price' => 75000, 'quantity' => 1, 'name' => 'Biaya Pendaftaran'],
    ],
]);

// Kirim $snapToken ke view
return View::render('payment.checkout', ['snapToken' => $snapToken]);
```

**Frontend (Blade):**
```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>

<button id="pay-button">Bayar Sekarang</button>

<script>
document.getElementById('pay-button').addEventListener('click', function () {
    snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) { window.location = '/payment/success'; },
        onPending: function(result) { window.location = '/payment/pending'; },
        onError: function(result) { alert('Pembayaran gagal!'); }
    });
});
</script>
```

### 🇮🇩 Xendit — Invoice

```php
$url = PaymentHandler::driver('xendit')->createTransaction([
    'external_id' => 'INV-' . time(),
    'amount' => 200000,
    'description' => 'Pembayaran Kursus Renang',
    'payer_email' => 'member@mail.com',
    'success_redirect_url' => 'https://example.com/success',
    'failure_redirect_url' => 'https://example.com/failed',
]);

// Redirect user ke halaman pembayaran Xendit
header("Location: $url");
```

### 🌍 PayPal — Checkout

```php
$url = PaymentHandler::driver('paypal')->createTransaction([
    'amount' => '25.00',
    'currency' => 'USD',
    'description' => 'Swimming Course Registration',
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
]);

// Redirect user ke PayPal
header("Location: $url");
```

### 🌍 Stripe — Checkout Session

```php
$url = PaymentHandler::driver('stripe')->createTransaction([
    'items' => [
        ['price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => 'Monthly Membership'],
            'unit_amount' => 4999, // $49.99 dalam cents
        ], 'quantity' => 1],
    ],
    'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'https://example.com/cancel',
]);

header("Location: $url");
```

### 🇮🇩 DOKU — Checkout

```php
$result = PaymentHandler::driver('doku')->createTransaction([
    'order' => [
        'invoice_number' => 'INV-' . time(),
        'amount' => 100000,
    ],
    'payment' => [
        'payment_due_date' => 60, // menit
    ],
    'customer' => [
        'name' => 'Siti',
        'email' => 'siti@mail.com',
    ],
]);
```

---

## 🔀 Berpindah Gateway

Untuk berpindah dari satu gateway ke gateway lain, Anda **tidak perlu mengubah kode PHP**. Cukup update `.env`:

```env
# Dari Midtrans...
# PAYMENT_GATEWAY=midtrans

# ...ke Xendit
PAYMENT_GATEWAY=xendit
XENDIT_SECRET_KEY=xnd_development_xxx
XENDIT_PUBLIC_KEY=xnd_public_xxx
```

Kode PHP Anda yang menggunakan `new PaymentHandler()` akan otomatis menyesuaikan.

---

## 🏗️ Menambah Driver Baru

Jika ingin menambahkan gateway yang belum tersedia, cukup:

**1. Buat file driver baru:**
```
app/Handlers/PaymentDrivers/CustomDriver.php
```

**2. Implementasi interface:**
```php
<?php

namespace TheFramework\Handlers\PaymentDrivers;

class CustomDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createTransaction(array $payload): mixed
    {
        // Implementasi kustom Anda
    }

    public function checkStatus(string $orderId): object
    {
        // Implementasi kustom Anda
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }
}
```

**3. Tambahkan konfigurasi di `config/payment.php`:**
```php
'custom' => [
    'api_key' => $_ENV['CUSTOM_API_KEY'] ?? null,
],
```

**4. Langsung pakai:**
```php
PaymentHandler::driver('custom')->createTransaction($payload);
```

---

## 📁 Struktur File

```
app/Handlers/
├── PaymentHandler.php                  ← Manager Utama
└── PaymentDrivers/
    ├── PaymentDriverInterface.php      ← Kontrak Semua Driver
    │
    ├── 🇮🇩 Nasional
    ├── MidtransDriver.php
    ├── XenditDriver.php
    ├── DokuDriver.php
    ├── FaspayDriver.php
    ├── NicepayDriver.php
    ├── Ipay88Driver.php
    ├── IpaymuDriver.php
    ├── OyDriver.php
    ├── DanaDriver.php
    ├── EspayDriver.php
    │
    ├── 🌍 Internasional
    ├── StripeDriver.php
    ├── PaypalDriver.php
    ├── AdyenDriver.php
    ├── SquareDriver.php
    ├── AuthorizenetDriver.php
    ├── BraintreeDriver.php
    ├── CheckoutcomDriver.php
    ├── WorldpayDriver.php
    ├── AirwallexDriver.php
    └── HelcimDriver.php
```

---

## ❓ Troubleshooting

### "Payment driver class not found"
- **Penyebab**: Nama driver di `.env` tidak cocok dengan nama file.
- **Solusi**: Pastikan `PAYMENT_GATEWAY=` sesuai dengan nama key di `config/payment.php`. Gunakan huruf kecil semua.

### "SDK not found" Error
- **Penyebab**: Package Composer untuk gateway tersebut belum diinstall.
- **Solusi**: Jalankan perintah `composer require` sesuai yang tertulis di tabel Persiapan di atas.

### "Configuration not found"
- **Penyebab**: Key konfigurasi gateway belum ditambahkan di `config/payment.php`.
- **Solusi**: Pastikan Anda sudah menambahkan array konfigurasi yang sesuai di file tersebut.

### Webhook Tidak Diterima
- **Penyebab**: URL webhook belum didaftarkan di dashboard gateway.
- **Solusi**: Login ke dashboard gateway Anda dan daftarkan URL webhook endpoint (misal: `https://yourdomain.com/payment/webhook`).
