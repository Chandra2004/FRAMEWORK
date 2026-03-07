# 🔄 Queue — Sistem Antrian Job

Queue memindahkan tugas berat yang memakan waktu (seperti mengirim email, memproses video, laporan PDF) ke latar belakang (background process). Ini membuat respon aplikasi ke user menjadi sangat cepat.

---

## 📑 Daftar Isi

- [Konfigurasi](#konfigurasi)
- [Membuat Job](#membuat-job)
- [Dispatch Job](#dispatch-job)
- [Menjalankan Worker](#menjalankan-worker)
- [Job Properties](#job-properties)
- [Lifecycle Hooks](#lifecycle-hooks)
- [Delay & Retry](#delay--retry)
- [Failed Jobs](#failed-jobs)
- [Priority Queues](#priority-queues)
- [Job Chains](#job-chains)
- [Batch Processing](#batch-processing)
- [Monitoring & Status](#monitoring--status)
- [Fitur Beyond Laravel 🚀](#fitur-beyond-laravel-)

---

## Konfigurasi

Framework menggunakan driver **Database**. Tabel `jobs` dan `failed_jobs` akan dibuat otomatis saat worker pertama kali dijalankan.

```env
QUEUE_CONNECTION=database
```

Jika ingin membuat tabel secara manual:

```php
use TheFramework\App\Queue\Queue;

Queue::createTables(); // Buat jobs + failed_jobs sekaligus
```

---

## Membuat Job

### Via Artisan CLI

```bash
php artisan make:job SendEmailJob
```

File akan terbuat di `app/Jobs/SendEmailJob.php`.

### Struktur Job

```php
<?php

namespace TheFramework\Jobs;

use TheFramework\App\Queue\Job;

class SendEmailJob extends Job
{
    // ── Konfigurasi ────────────────────────
    public int $tries = 3;        // Max percobaan
    public int $timeout = 120;    // Timeout (detik)
    public string $queue = 'emails'; // Queue name
    public int|array $backoff = [10, 30, 60]; // Delay antar retry

    // ── Logic Utama ────────────────────────
    public function handle()
    {
        $email = $this->getData('email');
        $subject = $this->getData('subject', 'Hello');

        // Kirim email...
        mail($email, $subject, 'Isi pesan');
    }

    // ── Dipanggil Jika Gagal Final ─────────
    public function failed(\Throwable $exception): void
    {
        // Log error, kirim notifikasi ke admin, dll.
        error_log("Email gagal dikirim: " . $exception->getMessage());
    }
}
```

---

## Dispatch Job

### Self-Dispatch Pattern (Recommended)

```php
use TheFramework\Jobs\SendEmailJob;

// Dispatch sederhana
SendEmailJob::dispatch(['email' => 'user@test.com', 'subject' => 'Welcome']);

// Dispatch dengan opsi
SendEmailJob::dispatch(['email' => 'user@test.com'])
    ->onQueue('high')       // Kirim ke queue 'high'
    ->delay(60)             // Tunda 60 detik
    ->tries(5)              // Max 5 percobaan
    ->timeout(300);         // Timeout 5 menit

// Dispatch kondisional
SendEmailJob::dispatchIf($user->wantsEmail, ['email' => $user->email]);
SendEmailJob::dispatchUnless($user->isBanned, ['email' => $user->email]);

// Dispatch synchronous (langsung, tanpa queue — bagus untuk testing)
$result = SendEmailJob::dispatchSync(['email' => 'test@test.com']);
```

### Via Queue Class

```php
use TheFramework\App\Queue\Queue;

// Push job
Queue::push(new SendEmailJob(['email' => 'test@test.com']));

// Push dengan delay
Queue::later(300, new SendEmailJob(['email' => 'test@test.com']));

// Push class name (tanpa instance)
Queue::push(SendEmailJob::class, ['email' => 'test@test.com']);

// Push banyak sekaligus (bulk)
Queue::bulk([
    new SendEmailJob(['email' => 'a@test.com']),
    new SendEmailJob(['email' => 'b@test.com']),
    new SendEmailJob(['email' => 'c@test.com']),
], 'emails');

// Schedule pada waktu tertentu
Queue::schedule(
    new SendEmailJob(['email' => 'test@test.com']),
    new \DateTime('2026-03-01 09:00:00')
);
```

---

## Menjalankan Worker

### Via Artisan CLI

```bash
# Worker standar
php artisan queue:work

# Dengan opsi
php artisan queue:work --queue=high,default,low  # Priority queues
php artisan queue:work --sleep=5                  # Sleep 5 detik jika kosong
php artisan queue:work --max-jobs=100             # Stop setelah 100 jobs
php artisan queue:work --max-time=3600            # Stop setelah 1 jam
php artisan queue:work --once                     # Proses 1 job lalu stop
php artisan queue:work --stop-when-empty          # Stop jika queue kosong
```

### Via Web Command Center

Untuk shared hosting tanpa SSH:

```
https://yoursite.com/_system/queue
```

### Deployment di Shared Hosting (Cron Job)

Karena shared hosting tidak bisa menjalankan daemon:

```bash
# Crontab — jalankan setiap menit
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --max-time=55
```

Flag `--stop-when-empty` memastikan worker berhenti jika tidak ada job, dan `--max-time=55` mencegah overlap dengan cron berikutnya.

---

## Job Properties

Semua property ini opsional dan bisa di-override di setiap Job class:

| Property         | Tipe         | Default     | Deskripsi                                             |
| ---------------- | ------------ | ----------- | ----------------------------------------------------- |
| `$queue`         | `string`     | `'default'` | Nama queue                                            |
| `$tries`         | `int`        | `3`         | Jumlah max percobaan                                  |
| `$timeout`       | `int`        | `60`        | Timeout eksekusi (detik)                              |
| `$delay`         | `int`        | `0`         | Delay sebelum pertama kali diproses                   |
| `$backoff`       | `int\|array` | `0`         | Delay antar retry. Array = escalating: `[10, 30, 60]` |
| `$maxExceptions` | `int`        | `0`         | Max exception sebelum fail (0 = ikut $tries)          |
| `$unique`        | `bool`       | `false`     | Cegah duplikasi di queue                              |
| `$uniqueFor`     | `int`        | `0`         | Lock duration untuk unique (0 = sampai selesai)       |
| `$afterCommit`   | `bool`       | `false`     | Dispatch setelah DB transaction commit                |

### Contoh

```php
class ResizeImageJob extends Job
{
    public int $tries = 5;
    public int $timeout = 300;         // 5 menit (image besar)
    public string $queue = 'images';
    public array $backoff = [10, 30, 60, 120]; // Escalating retry delay
    public bool $unique = true;         // Jangan resize image yang sama 2x
}
```

---

## Lifecycle Hooks

Setiap job memiliki lifecycle hooks yang bisa di-override:

```
before() → handle() → after() → cleanup()
                ↓ (jika error)
          shouldRetry() → retryAfter()
                ↓ (jika max tries habis)
            failed() → cleanup()
```

### before() — Pre-processing

```php
public function before(): void
{
    // Setup sebelum handle()
    $this->startTime = microtime(true);
}
```

### after() — Post-processing (hanya jika sukses)

```php
public function after(): void
{
    $elapsed = microtime(true) - $this->startTime;
    Log::info("Job selesai dalam {$elapsed}s");
}
```

### failed() — Error handler (gagal final)

```php
public function failed(\Throwable $exception): void
{
    // Notifikasi admin, log error, dll.
    Mail::send('admin@app.com', 'Job Failed', $exception->getMessage());
}
```

### cleanup() — Always runs (seperti finally)

```php
public function cleanup(): void
{
    // Selalu dipanggil, baik sukses maupun gagal
    // Berguna untuk menutup resource, hapus temp file, dll.
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
}
```

### shouldRetry() — Conditional retry

```php
public function shouldRetry(\Throwable $exception): bool
{
    // Jangan retry jika error validasi (tidak akan berhasil juga)
    if ($exception instanceof ValidationException) {
        return false;
    }
    return true; // Retry untuk error lainnya
}
```

### shouldRun() — Conditional execution 🚀

```php
public function shouldRun(): bool
{
    // Skip jika user sudah dihapus
    $user = User::find($this->getData('user_id'));
    return $user !== null;
}
```

---

## Delay & Retry

### Menambahkan Delay

```php
// Delay saat dispatch
SendEmailJob::dispatch($data)->delay(600); // 10 menit

// Delay via Queue class
Queue::later(600, new SendEmailJob($data));

// Delay via property (berlaku untuk semua dispatch)
class SendEmailJob extends Job
{
    public int $delay = 300; // Selalu tunggu 5 menit
}

// Schedule pada waktu tertentu
Queue::schedule(new SendEmailJob($data), new \DateTime('tomorrow 9:00'));
```

### Backoff Strategy

```php
class ApiCallJob extends Job
{
    // Fixed backoff: selalu 30 detik
    public int $backoff = 30;

    // Escalating backoff: 10s, 30s, 60s
    public array $backoff = [10, 30, 60];

    // Custom backoff logic (override method)
    public function retryAfter(int $attempts): int
    {
        // Exponential backoff: 5s, 10s, 20s, 40s, 80s...
        return (int) (5 * pow(2, $attempts - 1));
    }
}
```

---

## Failed Jobs

Job yang gagal setelah semua percobaan habis dipindahkan ke tabel `failed_jobs`.

### Melihat Failed Jobs

```php
// Semua failed jobs
$failed = Queue::allFailed();

// Dari queue tertentu
$failed = Queue::allFailed('emails');

// Satu failed job
$job = Queue::findFailed(5);

// Hitung total
$count = Queue::failedCount();
```

### Retry Failed Jobs

```php
// Retry satu job
Queue::retry(5);         // By ID

// Retry semua
Queue::retryAll();

// Retry dari queue tertentu
Queue::retryAll('emails');
```

### Menghapus Failed Jobs

```php
// Hapus satu
Queue::forgetFailed(5);

// Hapus semua
Queue::flushFailed();

// Hapus dari queue tertentu
Queue::flushFailed('emails');
```

---

## Priority Queues

Worker bisa memproses beberapa queue dengan prioritas:

```bash
php artisan queue:work --queue=high,default,low
```

Queue `high` diproses duluan. Jika `high` kosong, baru `default`, lalu `low`.

```php
// Dispatch ke queue high-priority
SendEmailJob::dispatch($data)->onQueue('high');

// Atau via property
class UrgentNotificationJob extends Job
{
    public string $queue = 'high';
}
```

---

## Job Chains

Jalankan beberapa job **secara berurutan**. Jika satu gagal, chain berhenti.

```php
// Method 1: Via Job::withChain()
ProcessOrderJob::withChain([
    new SendInvoiceJob(['order_id' => 123]),
    new UpdateInventoryJob(['order_id' => 123]),
    new NotifyWarehouseJob(['order_id' => 123]),
])->dispatch(['order_id' => 123]);

// Method 2: Via Queue::chain()
Queue::chain(
    ProcessOrderJob::class,
    ['order_id' => 123],
    [
        new SendInvoiceJob(['order_id' => 123]),
        new UpdateInventoryJob(['order_id' => 123]),
    ]
);
```

---

## Batch Processing

Jalankan kumpulan job dan track progress batch:

```php
$batch = Queue::batch([
    new SendEmailJob(['email' => 'a@test.com']),
    new SendEmailJob(['email' => 'b@test.com']),
    new SendEmailJob(['email' => 'c@test.com']),
], 'emails');

echo "Batch ID: " . $batch['batch_id'];  // batch_a1b2c3d4e5f6g7h8
echo "Total: " . $batch['total'];         // 3
echo "Job IDs: " . implode(', ', $batch['job_ids']); // 45, 46, 47
```

---

## Monitoring & Status

### Status Queue

```php
$status = Queue::status('default');
// [
//   'queue'    => 'default',
//   'total'    => 42,
//   'pending'  => 30,
//   'reserved' => 5,
//   'delayed'  => 7,
//   'failed'   => 3,
// ]

// Semua queue
$allStatus = Queue::allQueuesStatus();

// Daftar queue yang ada
$queues = Queue::getQueues(); // ['default', 'emails', 'high']
```

### Formatted Summary 🚀

```php
echo Queue::summary('default');
```

Output:

```
📊 Queue Status: default
────────────────────────────────────────
   Total:    42
   Pending:  30
   Reserved: 5
   Delayed:  7
   Failed:   3
   Oldest:   45s ago
```

### Individual Counts

```php
Queue::size('default');     // Total jobs
Queue::pending('default');  // Menunggu diproses
Queue::reserved('default'); // Sedang dikerjakan
Queue::delayed('default');  // Ditunda (scheduled)
Queue::failedCount();       // Total gagal
```

### Queue Management

```php
// Hapus semua job di satu queue
Queue::clear('default');      // Return: jumlah yang dihapus

// Hapus SEMUA job di SEMUA queue
Queue::purge();

// Bersihkan job yang stuck (reserved > 1 jam)
Queue::clearStuck(3600);

// Lihat job berikutnya tanpa mengambilnya
$next = Queue::peek('default');

// List semua jobs (untuk admin panel)
$jobs = Queue::list('default', limit: 50, offset: 0);
```

---

## Interaksi Dari Dalam Job

Dari dalam method `handle()`, Anda bisa berinteraksi dengan queue:

```php
public function handle()
{
    // Cek jumlah percobaan
    if ($this->attempts() > 2) {
        // Strategi berbeda untuk percobaan ke-3
    }

    // Delete job lebih awal (skip sisanya)
    if ($someCondition) {
        $this->delete();
        return;
    }

    // Release kembali ke queue (coba lagi nanti)
    if ($resourceBusy) {
        $this->release(delay: 60); // Coba 60 detik lagi
        return;
    }

    // Fail langsung tanpa retry
    if ($criticalError) {
        $this->fail(new \RuntimeException('Data corrupt'));
        return;
    }

    // Normal processing...
}
```

---

## Fitur Beyond Laravel 🚀

### Progress Tracking

Track progress job yang berjalan lama:

```php
class ImportCsvJob extends Job
{
    public int $timeout = 600; // 10 menit

    public function handle()
    {
        $rows = $this->getData('rows');
        $total = count($rows);

        foreach ($rows as $i => $row) {
            $this->processRow($row);
            $this->progress($i + 1, $total, "Processing row " . ($i + 1));
        }
    }
}

// Monitor dari luar
$progress = Queue::getProgress($jobId);
// ['current' => 500, 'total' => 1000, 'percent' => 50.0, 'message' => 'Processing row 500']
```

### Oldest Job Age

Deteksi apakah worker masih hidup:

```php
$age = Queue::oldestJobAge('default'); // 300 (seconds)
if ($age > 600) {
    // Worker mungkin mati! Kirim alert.
}
```

### Unique Jobs

Cegah duplikasi job yang sama di queue:

```php
class RecalculateStatsJob extends Job
{
    public bool $unique = true;
    public int $uniqueFor = 300; // Lock 5 menit

    public function uniqueId(): string
    {
        return 'stats:' . $this->getData('user_id');
    }
}

// Dispatch pertama: masuk queue ✅
RecalculateStatsJob::dispatch(['user_id' => 1]);

// Dispatch kedua (dalam 5 menit): SKIPPED ⏭️
RecalculateStatsJob::dispatch(['user_id' => 1]); // return null
```

### Conditional Execution

```php
class SendReminderJob extends Job
{
    public function shouldRun(): bool
    {
        // Skip jika user sudah unsubscribe
        $user = User::find($this->getData('user_id'));
        return $user && $user->wants_reminders;
    }
}
```

---

## Worker Options Reference

| Flag                | Default   | Deskripsi                                        |
| ------------------- | --------- | ------------------------------------------------ |
| `--queue=name`      | `default` | Nama queue (bisa comma-separated untuk priority) |
| `--sleep=N`         | `3`       | Sleep N detik jika queue kosong                  |
| `--max-jobs=N`      | `0` (∞)   | Stop setelah memproses N jobs                    |
| `--max-time=N`      | `0` (∞)   | Stop setelah N detik berjalan                    |
| `--once`            | -         | Proses 1 job lalu berhenti                       |
| `--stop-when-empty` | -         | Berhenti jika queue kosong                       |

---

📖 **Selanjutnya:** [Database](database.md) | [Migrations](migrations.md) | [Artisan CLI](artisan.md)
