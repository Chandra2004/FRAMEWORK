# 🔄 Queue (Antrian Job)

Queue memindahkan tugas berat yang memakan waktu (seperti mengirim email, memproses video, laporan PDF) ke latar belakang (background process). Ini membuat respon aplikasi ke user menjadi sangat cepat.

---

## 📋 Daftar Isi

1.  [Konfigurasi](#konfigurasi)
2.  [Membuat Job](#membuat-job)
3.  [Dispatch Job](#dispatch-job)
4.  [Menjalankan Worker](#menjalankan-worker)

---

## Konfigurasi

Framework menggunakan driver **Database**. Pastikan tabel `jobs` sudah dibuat (via migrasi bawaan framework).

```env
QUEUE_CONNECTION=database
```

---

## Membuat Job

Gunakan artisan untuk membuat class Job baru.

```bash
php artisan make:job SendEmailJob
```

File akan terbuat di `app/Jobs/SendEmailJob.php`.

```php
class SendEmailJob {
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function handle() {
        // Logika kirim email di sini
        mail($this->data['email'], 'Subject', 'Isi Pesan');
    }
}
```

---

## Dispatch Job

Panggil Job dari Controller. Kode di bawah ini akan langsung selesai (return) tanpa menunggu email terkirim.

```php
use App\Jobs\SendEmailJob;

public function register() {
    // ... simpan user ...

    // Masukkan job kirim email ke antrian
    dispatch(new SendEmailJob(['email' => 'user@test.com']));

    echo "Registrasi Sukses! Email akan dikirim sebentar lagi.";
}
```

---

## Menjalankan Worker

Worker adalah proses yang "memakan" job dari database dan menjalankannya.

Di terminal server Anda:

```bash
php artisan queue:work
```

### Deployment di Shared Hosting

Karena Anda tidak bisa menjalankan perintah `queue:work` yang berjalan selamanya (daemon), gunakan **Cron Job** yang menjalankan perintah `queue:work --stop-when-empty` setiap menit untuk memproses antrian yang menumpuk.
