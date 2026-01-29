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

Di terminal server Anda:

```bash
php artisan queue:work
```

### Deployment di Shared Hosting

Karena Anda tidak bisa menjalankan perintah `queue:work` yang berjalan selamanya (daemon), gunakan **Cron Job** yang menjalankan perintah `queue:work --stop-when-empty` setiap menit untuk memproses antrian yang menumpuk.

---

## ⏳ Job Delay & Retries

### Menambahkan Delay

Anda bisa menunda eksekusi job beberapa detik/menit.

```php
// Tunda 10 menit
dispatch(new SendEmailJob($data))->delay(600);
```

### Percobaan Ulang (Retries)

Jika job gagal (misal: koneksi SMTP error), worker akan mencoba mengulanginya sesuai instruksi di class Job:

```php
public $tries = 3;
public $timeout = 120; // Detik
```

---

## ❌ Penanganan Gagal (Failed Jobs)

Jika job tetap gagal setelah semua percobaan, ia akan dipindahkan ke tabel `failed_jobs`.

### Melihat Job Gagal

```bash
php artisan queue:failed
```

### Mengulangi Job Gagal

```bash
php artisan queue:retry all
# atau
php artisan queue:retry 5 (berdasarkan ID)
```

### Membersihkan

```bash
php artisan queue:flush
```

---

## 📊 Monitoring

Anda bisa memantau jumlah antrian saat ini melalui Artisan:

```bash
php artisan queue:status
```

Ini akan menunjukkan jumlah job yang:

- **Pending:** Menunggu diproses.
- **Reserved:** Sedang dikerjakan oleh worker lain.
- **Failed:** Sudah menyerah (gagal total).
