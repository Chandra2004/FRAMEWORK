<?php

namespace TheFramework\App\Queue;

/**
 * Job — Abstract Base Class untuk Queue Jobs
 * 
 * Semua job yang ingin dimasukkan ke antrian harus extends class ini.
 * Terinspirasi dari Laravel Queue Jobs, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Queue
 * @version 5.0.1
 */
abstract class Job
{
    // ========================================================
    //  CONFIGURABLE PROPERTIES
    // ========================================================

    /**
     * Nama queue tempat job ini diproses
     */
    public string $queue = 'default';

    /**
     * Jumlah maksimal percobaan sebelum dianggap gagal
     */
    public int $tries = 3;

    /**
     * Timeout eksekusi dalam detik (0 = tanpa batas)
     */
    public int $timeout = 60;

    /**
     * Delay (detik) sebelum job pertama kali diproses
     */
    public int $delay = 0;

    /**
     * Delay antar retry (detik)
     * Bisa int (sama untuk semua retry) atau array [10, 30, 60] (escalating)
     */
    public int|array $backoff = 0;

    /**
     * Jumlah maksimal exception yang diizinkan sebelum fail
     */
    public int $maxExceptions = 0;

    /**
     * Apakah job harus unique (tidak boleh duplicate di queue)
     * Jika true, job dengan signature sama tidak akan di-push lagi selama sudah ada di queue
     */
    public bool $unique = false;

    /**
     * Durasi (detik) lock untuk unique job
     * 0 = sampai job selesai
     */
    public int $uniqueFor = 0;

    /**
     * Apakah job harus di-dispatch setelah DB transaction commit
     */
    public bool $afterCommit = false;

    /**
     * Apakah job harus dihapus saat model terkait dihapus
     */
    public bool $deleteWhenMissingModels = false;

    // ========================================================
    //  INTERNAL STATE
    // ========================================================

    /**
     * Data yang dibawa oleh Job
     */
    protected array $data = [];

    /**
     * Job record ID (diisi saat diproses oleh worker)
     */
    private ?int $jobId = null;

    /**
     * Jumlah percobaan saat ini (diisi oleh worker)
     */
    private int $currentAttempts = 0;

    /**
     * Apakah job sudah di-delete oleh handler
     */
    private bool $deleted = false;

    /**
     * Apakah job sudah di-release oleh handler
     */
    private bool $released = false;

    /**
     * Apakah job sudah di-fail oleh handler
     */
    private bool $failed = false;

    /**
     * Tag-tag untuk monitoring/grouping
     */
    protected array $tags = [];

    // ========================================================
    //  CONSTRUCTOR
    // ========================================================

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    // ========================================================
    //  ABSTRACT METHOD — WAJIB DI-IMPLEMENT
    // ========================================================

    /**
     * Logic utama yang akan dijalankan oleh Job
     * 
     * @return void|bool|mixed
     */
    abstract public function handle();

    // ========================================================
    //  LIFECYCLE HOOKS (OPTIONAL OVERRIDE)
    // ========================================================

    /**
     * Dipanggil saat job GAGAL FINAL (setelah semua retry habis)
     * Override method ini untuk logging, notifikasi, cleanup, dll.
     * 
     * @param \Throwable $exception Exception terakhir yang terjadi
     */
    public function failed(\Throwable $exception): void
    {
        // Override di child class untuk custom handling
        // Contoh: Log::error("Job failed: " . $exception->getMessage());
        // Contoh: Mail::send('admin@app.com', 'Job Failed', $exception->getMessage());
    }

    /**
     * Dipanggil SEBELUM job dijalankan (pre-processing hook)
     */
    public function before(): void
    {
        // Override untuk setup sebelum handle()
    }

    /**
     * Dipanggil SETELAH job berhasil dijalankan (post-processing hook)
     */
    public function after(): void
    {
        // Override untuk cleanup setelah handle()
    }

    /**
     * Return middleware yang harus dijalankan sebelum job
     * 
     * @return array<string|object>
     */
    public function middleware(): array
    {
        return [];
    }

    /**
     * Hitung delay retry secara dinamis berdasarkan jumlah percobaan
     * Override untuk custom backoff strategy
     * 
     * @param int $attempts Percobaan saat ini
     * @return int Delay dalam detik
     */
    public function retryAfter(int $attempts): int
    {
        if (is_array($this->backoff)) {
            $index = min($attempts - 1, count($this->backoff) - 1);
            return $this->backoff[$index] ?? 0;
        }

        if ($this->backoff > 0) {
            return $this->backoff;
        }

        // Default: exponential backoff (5s, 10s, 20s, 40s...)
        return (int) (5 * pow(2, $attempts - 1));
    }

    /**
     * Tentukan apakah job harus di-retry saat exception tertentu terjadi
     * Override untuk conditional retry
     * 
     * @param \Throwable $exception
     * @return bool True = retry, False = langsung gagal
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        return true; // Default: selalu retry sampai $tries habis
    }

    // ========================================================
    //  DATA ACCESS
    // ========================================================

    /**
     * Ambil data dari job
     * 
     * @param string|null $key Null = return semua data
     * @param mixed $default Default value jika key tidak ada
     * @return mixed
     */
    public function getData(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data pada job
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Merge data ke data yang sudah ada
     */
    public function mergeData(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Cek apakah data key ada
     */
    public function hasData(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    // ========================================================
    //  DISPATCH METHODS (Self-Dispatch Pattern)
    // ========================================================

    /**
     * Dispatch job ke queue
     * 
     * Contoh: SendEmailJob::dispatch(['email' => 'test@test.com'])
     * 
     * @param array $data
     * @return PendingDispatch
     */
    public static function dispatch(array $data = []): PendingDispatch
    {
        $job = new static($data);
        return new PendingDispatch($job);
    }

    /**
     * Dispatch job hanya jika kondisi true
     * 
     * Contoh: SendEmailJob::dispatchIf($user->wantsEmail, ['email' => $email])
     */
    public static function dispatchIf(bool $condition, array $data = []): ?PendingDispatch
    {
        if ($condition) {
            return static::dispatch($data);
        }
        return null;
    }

    /**
     * Dispatch job kecuali kondisi true
     */
    public static function dispatchUnless(bool $condition, array $data = []): ?PendingDispatch
    {
        return static::dispatchIf(!$condition, $data);
    }

    /**
     * Jalankan job secara synchronous (tanpa queue, langsung eksekusi)
     * Berguna untuk testing atau proses yang harus segera selesai
     */
    public static function dispatchSync(array $data = []): mixed
    {
        $job = new static($data);
        $job->before();
        $result = $job->handle();
        $job->after();
        return $result;
    }

    /**
     * Dispatch setelah response dikirim (jika framework support)
     */
    public static function dispatchAfterResponse(array $data = []): PendingDispatch
    {
        $dispatch = static::dispatch($data);
        $dispatch->afterResponse();
        return $dispatch;
    }

    // ========================================================
    //  INTERACTS WITH QUEUE (Dari dalam job saat diproses)
    // ========================================================

    /**
     * Set job record ID (dipanggil oleh Worker)
     * @internal
     */
    public function setJobId(int $id): void
    {
        $this->jobId = $id;
    }

    /**
     * Get job record ID
     */
    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    /**
     * Set jumlah percobaan saat ini (dipanggil oleh Worker)
     * @internal
     */
    public function setAttempts(int $attempts): void
    {
        $this->currentAttempts = $attempts;
    }

    /**
     * Dapatkan jumlah percobaan saat ini
     */
    public function attempts(): int
    {
        return $this->currentAttempts;
    }

    /**
     * Hapus job dari queue (dari dalam handler)
     * Berguna jika job berhasil sebagian dan tidak perlu di-retry
     */
    public function delete(): void
    {
        $this->deleted = true;
        if ($this->jobId !== null) {
            Queue::delete($this->jobId);
        }
    }

    /**
     * Release job kembali ke queue dengan delay (dari dalam handler)
     * Berguna saat perlu menunggu external resource
     */
    public function release(int $delay = 0): void
    {
        $this->released = true;
        if ($this->jobId !== null) {
            Queue::release($this->jobId, $delay);
        }
    }

    /**
     * Tandai job sebagai gagal (dari dalam handler)
     * Akan langsung dipindahkan ke failed_jobs tanpa retry
     */
    public function fail(?\Throwable $exception = null): void
    {
        $this->failed = true;
        if ($this->jobId !== null) {
            Queue::fail($this->jobId, $exception);
        }
    }

    /**
     * Cek status internal
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Cek apakah sudah melewati max attempts
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->tries > 0 && $this->currentAttempts >= $this->tries;
    }

    // ========================================================
    //  IDENTIFICATION & DISPLAY
    // ========================================================

    /**
     * Nama yang ditampilkan di log/monitoring
     * Override untuk custom display name
     */
    public function displayName(): string
    {
        return static::class;
    }

    /**
     * Tags untuk monitoring/grouping
     * Override di child class
     */
    public function tags(): array
    {
        return $this->tags;
    }

    /**
     * Unique signature untuk mencegah duplikasi
     * Default: class name + md5(data)
     */
    public function uniqueId(): string
    {
        return static::class . ':' . md5(json_encode($this->data));
    }

    // ========================================================
    //  SERIALIZATION
    // ========================================================

    /**
     * Convert job ke payload array untuk disimpan di database
     * @internal
     */
    public function toPayload(): array
    {
        return [
            'job' => static::class,
            'data' => $this->data,
            'tries' => $this->tries,
            'timeout' => $this->timeout,
            'backoff' => $this->backoff,
            'maxExceptions' => $this->maxExceptions,
            'unique' => $this->unique,
            'uniqueFor' => $this->uniqueFor,
            'tags' => $this->tags(),
            'displayName' => $this->displayName(),
        ];
    }

    /**
     * Restore properties dari payload
     * @internal
     */
    public function fromPayload(array $payload): void
    {
        if (isset($payload['data']))
            $this->data = $payload['data'];
        if (isset($payload['tries']))
            $this->tries = $payload['tries'];
        if (isset($payload['timeout']))
            $this->timeout = $payload['timeout'];
        if (isset($payload['backoff']))
            $this->backoff = $payload['backoff'];
        if (isset($payload['maxExceptions']))
            $this->maxExceptions = $payload['maxExceptions'];
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Progress tracking — update progress dari dalam job
     * Berguna untuk long-running jobs (export, import, processing)
     * 
     * @param int $current Item saat ini
     * @param int $total Total item
     * @param string|null $message Pesan opsional
     */
    public function progress(int $current, int $total, ?string $message = null): void
    {
        if ($this->jobId === null)
            return;

        $percent = $total > 0 ? round(($current / $total) * 100, 1) : 0;
        $progressData = json_encode([
            'current' => $current,
            'total' => $total,
            'percent' => $percent,
            'message' => $message,
        ]);

        Queue::updateProgress($this->jobId, $progressData);
    }

    /**
     * Conditional handle — jalankan hanya jika kondisi terpenuhi
     * Jika tidak terpenuhi, job dihapus (skip)
     */
    public function shouldRun(): bool
    {
        return true; // Override untuk custom check
    }

    /**
     * Cleanup method — selalu dipanggil di akhir, baik sukses maupun gagal
     * Mirip finally block
     */
    public function cleanup(): void
    {
        // Override untuk resource cleanup
    }

    /**
     * Helper: buat chain dari job-job yang harus dijalankan berurutan
     * 
     * Contoh: SendEmailJob::withChain([
     *     new LogJob(['action' => 'email_sent']),
     *     new NotifyJob(['user_id' => 1]),
     * ])->dispatch(['email' => 'test@test.com']);
     */
    public static function withChain(array $chain): PendingChain
    {
        return new PendingChain(static::class, $chain);
    }
}

// ========================================================
//  PENDING DISPATCH — Fluent Dispatch Builder
// ========================================================

/**
 * PendingDispatch — Fluent builder untuk dispatch job
 * 
 * Memungkinkan chaining: Job::dispatch($data)->onQueue('high')->delay(60)
 */
class PendingDispatch
{
    private Job $job;
    private bool $afterResponse = false;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * Set queue name
     */
    public function onQueue(string $queue): static
    {
        $this->job->queue = $queue;
        return $this;
    }

    /**
     * Set delay (detik)
     */
    public function delay(int $seconds): static
    {
        $this->job->delay = $seconds;
        return $this;
    }

    /**
     * Set max tries
     */
    public function tries(int $tries): static
    {
        $this->job->tries = $tries;
        return $this;
    }

    /**
     * Set timeout
     */
    public function timeout(int $seconds): static
    {
        $this->job->timeout = $seconds;
        return $this;
    }

    /**
     * Set backoff
     */
    public function backoff(int|array $backoff): static
    {
        $this->job->backoff = $backoff;
        return $this;
    }

    /**
     * Mark as unique
     */
    public function unique(int $lockFor = 0): static
    {
        $this->job->unique = true;
        $this->job->uniqueFor = $lockFor;
        return $this;
    }

    /**
     * Mark as after response
     */
    public function afterResponse(): static
    {
        $this->afterResponse = true;
        return $this;
    }

    /**
     * Merge additional data
     */
    public function with(array $data): static
    {
        $this->job->mergeData($data);
        return $this;
    }

    /**
     * Send to queue — auto-called on destruct
     */
    public function send(): int|string|null
    {
        $queue = $this->job->queue;
        $delay = $this->job->delay;

        if ($delay > 0) {
            return Queue::later($delay, $this->job, queue: $queue);
        }

        return Queue::push($this->job, queue: $queue);
    }

    /**
     * Auto-dispatch saat object di-destruct (Laravel behavior)
     */
    public function __destruct()
    {
        $this->send();
    }
}

// ========================================================
//  PENDING CHAIN — Job Chain Builder
// ========================================================

/**
 * PendingChain — Builder untuk menjalankan job secara berurutan
 */
class PendingChain
{
    private string $firstJobClass;
    private array $chain;
    private string $queue = 'default';

    public function __construct(string $firstJobClass, array $chain)
    {
        $this->firstJobClass = $firstJobClass;
        $this->chain = $chain;
    }

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    public function dispatch(array $data = []): int|string|null
    {
        return Queue::chain($this->firstJobClass, $data, $this->chain, $this->queue);
    }
}
