# Queue System (Sistem Antrian)

Queue system memungkinkan Anda untuk memproses tugas-tugas berat di latar belakang agar tidak memperiambat user (contoh: kirim email, generate laporan PDF, resize gambar).

## 1. Setup

Pastikan Anda sudah menjalankan migrasi database untuk membuat tabel `jobs`.

```bash
php artisan migrate
```

## 2. Membuat Job

Gunakan perintah artisan untuk membuat class Job baru:

```bash
php artisan make:job SendEmailJob
```

File baru akan dibuat di `app/Jobs/SendEmailJob.php`.

## 3. Menulis Logic Job

Buka file job tersebut dan isi method `handle()`:

```php
namespace TheFramework\Jobs;

use TheFramework\App\Job;

class SendEmailJob extends Job
{
    public function handle()
    {
        // Ambil data yang dikirim
        $email = $this->getData('email');
        $name = $this->getData('name');

        // Logic kirim email (pura-pura)
        // Mail::to($email)->send(...);
        file_put_contents(BASE_PATH . '/storage/logs/email.log', "Email terkirim ke $name ($email)\n", FILE_APPEND);
    }
}
```

## 4. Mengirim Job ke Antrian (Dispatch)

Di Controller atau di mana saja, gunakan global helper `dispatch()`:

```php
use TheFramework\Jobs\SendEmailJob;

// ...

$job = new SendEmailJob([
    'email' => 'user@example.com',
    'name' => 'Budi'
]);

dispatch($job); // Masuk antrian default
```

## 5. Menjalankan Worker

Untuk memproses antrian, jalankan worker di terminal terpisah:

```bash
php artisan queue:work
```

Worker akan terus berjalan (looping) dan memproses job yang masuk secara real-time. Jika gagal, job akan dicoba ulang (retry) maksimal 3 kali.
