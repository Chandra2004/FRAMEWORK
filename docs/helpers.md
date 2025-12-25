# HELPER FUNCTIONS

The-Framework menyediakan kumpulan fungsi global dan class helper untuk mempercepat development.

## Global Functions

Fungsi-fungsi ini dapat dipanggil di mana saja (Controller, View, Model).

### `dd($variable)`

Dump and Die. Menampilkan isi variabel dengan format rapi dan menghentikan eksekusi script. Sangat berguna untuk debugging.

### `url($path)`

Menghasilkan URL absolut aplikasi.

```php
url('/login') // Output: http://localhost:8080/login
```

### `config($key, $default = null)`

Mengambil konfigurasi dari environment.

```php
config('APP_NAME') // Output: The Framework
```

### `view($viewName, $data = [])`

Merender tampilan Blade.

```php
return view('home', ['title' => 'Welcome']);
```

### `redirect($path)`

Melakukan HTTP redirect.

```php
redirect('/dashboard');
```

---

## Class Helpers

### 1. UploadHandler

Class kuat untuk menangani upload file dengan aman.

```php
use TheFramework\Config\UploadHandler;

// Di Controller
if ($request->hasFile('foto')) {
    // Otomatis convert ke WebP, rename random, dan simpan di /private-uploads/user-pictures
    $result = UploadHandler::handleUploadToWebP($request->file('foto'), '/user-pictures', 'avatar_');

    if (UploadHandler::isError($result)) {
        return UploadHandler::getErrorMessage($result);
    }

    $filename = $result; // String filename sukses
}
```

### 2. EmailHandler

Mengirim email via SMTP (PHPMailer wrapper).

```php
use TheFramework\Config\EmailHandler;

$mail = new EmailHandler();
$mail->sendEmail('user@example.com', 'Welcome!', '<h1>Hello World</h1>');
```

### 3. PaymentHandler

Integrasi dasar Payment Gateway (Midtrans).

```php
use TheFramework\Config\PaymentHandler;

$payment = new PaymentHandler();
$snapToken = $payment->createTransaction($orderId, $amount, $customerDetails);
```
