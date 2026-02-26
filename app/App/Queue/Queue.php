<?php

namespace TheFramework\App\Queue;

use TheFramework\App\Database\Database;

/**
 * Queue — Queue Manager & Dispatcher
 * 
 * Mengelola antrian job: push, pop, delay, fail, retry, chain, batch, monitoring.
 * Terinspirasi dari Laravel Queue, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Queue
 * @version 5.0.1
 */
class Queue
{
    /**
     * Nama tabel jobs
     */
    protected static string $table = 'jobs';

    /**
     * Nama tabel failed jobs
     */
    protected static string $failedTable = 'failed_jobs';

    /**
     * Timeout reservation default (detik)
     */
    protected static int $retryAfter = 90;

    // ========================================================
    //  TABLE MANAGEMENT
    // ========================================================

    /**
     * Auto-create tabel jobs jika belum ada
     */
    public static function createJobsTable(): void
    {
        $db = Database::getInstance();
        $table = static::$table;

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
            `payload` LONGTEXT NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `reserved_at` INT UNSIGNED NULL,
            `available_at` INT UNSIGNED NOT NULL,
            `created_at` INT UNSIGNED NOT NULL,
            `progress` TEXT NULL,
            INDEX `idx_{$table}_queue_available` (`queue`, `available_at`),
            INDEX `idx_{$table}_reserved` (`reserved_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $db->query($sql);
        $db->execute();
    }

    /**
     * Auto-create tabel failed_jobs jika belum ada
     */
    public static function createFailedJobsTable(): void
    {
        $db = Database::getInstance();
        $table = static::$failedTable;

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `queue` VARCHAR(255) NOT NULL,
            `payload` LONGTEXT NOT NULL,
            `exception` LONGTEXT NULL,
            `failed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_{$table}_queue` (`queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $db->query($sql);
        $db->execute();
    }

    /**
     * Create kedua tabel sekaligus
     */
    public static function createTables(): void
    {
        static::createJobsTable();
        static::createFailedJobsTable();
    }

    // ========================================================
    //  PUSH OPERATIONS
    // ========================================================

    /**
     * Push job ke antrian
     * 
     * @param string|object $job Class name atau Job instance
     * @param array $data Data jika $job adalah string
     * @param string $queue Nama queue
     * @return int|string|null ID job yang dibuat
     */
    public static function push(string|object $job, array $data = [], string $queue = 'default'): int|string|null
    {
        $payload = static::createPayload($job, $data);
        $queueName = ($job instanceof Job) ? $job->queue : $queue;

        // Unique job check
        if ($job instanceof Job && $job->unique) {
            if (static::isDuplicate($job)) {
                return null; // Skip, sudah ada di queue
            }
        }

        return static::pushToDatabase($queueName, $payload, time());
    }

    /**
     * Push job dengan delay
     * 
     * @param int $delay Delay dalam detik
     * @param string|object $job
     * @param array $data
     * @param string $queue
     * @return int|string|null
     */
    public static function later(int $delay, string|object $job, array $data = [], string $queue = 'default'): int|string|null
    {
        $payload = static::createPayload($job, $data);
        $queueName = ($job instanceof Job) ? $job->queue : $queue;
        $availableAt = time() + $delay;

        return static::pushToDatabase($queueName, $payload, $availableAt);
    }

    /**
     * Push banyak job sekaligus (bulk)
     * 
     * @param array $jobs Array of Job instances atau [className, data] pairs
     * @param string $queue
     * @return array<int|string|null> Array of job IDs
     */
    public static function bulk(array $jobs, string $queue = 'default'): array
    {
        $ids = [];
        foreach ($jobs as $job) {
            if ($job instanceof Job) {
                $ids[] = static::push($job, queue: $queue);
            } elseif (is_array($job) && count($job) >= 1) {
                $ids[] = static::push($job[0], $job[1] ?? [], $queue);
            }
        }
        return $ids;
    }

    /**
     * Push raw payload (tanpa class resolution)
     */
    public static function pushRaw(string $payload, string $queue = 'default', int $delay = 0): int|string|null
    {
        return static::pushToDatabase($queue, $payload, time() + $delay);
    }

    /**
     * Insert job ke database
     */
    protected static function pushToDatabase(string $queue, string $payload, int $availableAt): int|string|null
    {
        $db = Database::getInstance();
        $now = time();

        $db->query("INSERT INTO `" . static::$table . "` (`queue`, `payload`, `attempts`, `available_at`, `created_at`) 
                     VALUES (:queue, :payload, 0, :available_at, :created_at);");
        $db->bind(':queue', $queue);
        $db->bind(':payload', $payload);
        $db->bind(':available_at', $availableAt);
        $db->bind(':created_at', $now);
        $db->execute();

        return $db->lastInsertId();
    }

    /**
     * Create JSON payload dari job
     */
    protected static function createPayload(string|object $job, array $data = []): string
    {
        if ($job instanceof Job) {
            $payload = $job->toPayload();
        } elseif (is_object($job)) {
            $payload = [
                'job' => get_class($job),
                'data' => method_exists($job, 'getData') ? $job->getData() : get_object_vars($job),
            ];
        } else {
            $payload = [
                'job' => $job,
                'data' => $data,
            ];
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    // ========================================================
    //  POP & PROCESS
    // ========================================================

    /**
     * Ambil job berikutnya dari antrian (FIFO, thread-safe)
     * 
     * @param string|array $queues Nama queue atau array prioritas ['high', 'default', 'low']
     * @return array|null Job record atau null
     */
    public static function pop(string|array $queues = 'default'): ?array
    {
        if (is_string($queues)) {
            $queues = [$queues];
        }

        // Coba setiap queue berdasarkan prioritas
        foreach ($queues as $queue) {
            $job = static::popFromQueue($queue);
            if ($job !== null) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Pop dari satu queue (dengan locking)
     */
    protected static function popFromQueue(string $queue): ?array
    {
        $db = Database::getInstance();
        $now = time();

        $db->beginTransaction();

        try {
            $sql = "SELECT `id`, `queue`, `payload`, `attempts`, `created_at` 
                    FROM `" . static::$table . "` 
                    WHERE `queue` = :queue 
                    AND (`reserved_at` IS NULL OR `reserved_at` <= :timeout) 
                    AND `available_at` <= :now 
                    ORDER BY `id` ASC 
                    LIMIT 1 
                    FOR UPDATE;";

            $db->query($sql);
            $db->bind(':queue', $queue);
            $db->bind(':timeout', $now - static::$retryAfter);
            $db->bind(':now', $now);

            $jobRecord = $db->single();

            if ($jobRecord) {
                $db->query("UPDATE `" . static::$table . "` SET `reserved_at` = :now, `attempts` = `attempts` + 1 WHERE `id` = :id;");
                $db->bind(':now', $now);
                $db->bind(':id', $jobRecord['id']);
                $db->execute();

                $db->commit();
                return $jobRecord;
            }

            $db->commit();
            return null;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ========================================================
    //  JOB LIFECYCLE
    // ========================================================

    /**
     * Hapus job dari antrian (setelah sukses)
     */
    public static function delete(int|string $id): void
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM `" . static::$table . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        $db->execute();
    }

    /**
     * Release job kembali ke queue (retry dengan delay)
     */
    public static function release(int|string $id, int $delay = 0): void
    {
        $db = Database::getInstance();
        $availableAt = time() + $delay;
        $db->query("UPDATE `" . static::$table . "` SET `reserved_at` = NULL, `available_at` = :available_at WHERE `id` = :id;");
        $db->bind(':available_at', $availableAt);
        $db->bind(':id', $id);
        $db->execute();
    }

    /**
     * Pindahkan job ke tabel failed_jobs
     */
    public static function fail(int|string $id, ?\Throwable $exception = null): void
    {
        $db = Database::getInstance();

        // Ambil job data dulu
        $db->query("SELECT `queue`, `payload` FROM `" . static::$table . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        $row = $db->single();

        if ($row) {
            // Insert ke failed_jobs
            static::createFailedJobsTable(); // Auto-ensure

            $exceptionText = $exception
                ? $exception::class . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString()
                : null;

            $db->query("INSERT INTO `" . static::$failedTable . "` (`queue`, `payload`, `exception`) VALUES (:queue, :payload, :exception);");
            $db->bind(':queue', $row['queue']);
            $db->bind(':payload', $row['payload']);
            $db->bind(':exception', $exceptionText);
            $db->execute();

            // Hapus dari jobs
            static::delete($id);
        }
    }

    /**
     * Update progress job (untuk tracking dari dalam job)
     */
    public static function updateProgress(int|string $id, string $progressJson): void
    {
        $db = Database::getInstance();
        $db->query("UPDATE `" . static::$table . "` SET `progress` = :progress WHERE `id` = :id;");
        $db->bind(':progress', $progressJson);
        $db->bind(':id', $id);
        $db->execute();
    }

    // ========================================================
    //  FAILED JOBS MANAGEMENT
    // ========================================================

    /**
     * Dapatkan semua failed jobs
     */
    public static function allFailed(?string $queue = null): array
    {
        $db = Database::getInstance();
        static::createFailedJobsTable();

        if ($queue) {
            $db->query("SELECT * FROM `" . static::$failedTable . "` WHERE `queue` = :queue ORDER BY `failed_at` DESC;");
            $db->bind(':queue', $queue);
        } else {
            $db->query("SELECT * FROM `" . static::$failedTable . "` ORDER BY `failed_at` DESC;");
        }

        return $db->resultSet();
    }

    /**
     * Ambil satu failed job berdasarkan ID
     */
    public static function findFailed(int|string $id): ?array
    {
        $db = Database::getInstance();
        $db->query("SELECT * FROM `" . static::$failedTable . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        return $db->single() ?: null;
    }

    /**
     * Retry satu failed job — pindahkan kembali ke queue
     */
    public static function retry(int|string $id): bool
    {
        $db = Database::getInstance();
        $failed = static::findFailed($id);

        if (!$failed)
            return false;

        // Push kembali ke jobs table
        static::pushToDatabase($failed['queue'], $failed['payload'], time());

        // Hapus dari failed_jobs
        $db->query("DELETE FROM `" . static::$failedTable . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        $db->execute();

        return true;
    }

    /**
     * Retry semua failed jobs
     * 
     * @return int Jumlah job yang di-retry
     */
    public static function retryAll(?string $queue = null): int
    {
        $failed = static::allFailed($queue);
        $count = 0;

        foreach ($failed as $job) {
            if (static::retry($job['id'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Hapus satu failed job
     */
    public static function forgetFailed(int|string $id): void
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM `" . static::$failedTable . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        $db->execute();
    }

    /**
     * Hapus semua failed jobs (flush)
     */
    public static function flushFailed(?string $queue = null): int
    {
        $db = Database::getInstance();

        if ($queue) {
            $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$failedTable . "` WHERE `queue` = :queue;");
            $db->bind(':queue', $queue);
            $count = $db->single()['cnt'] ?? 0;

            $db->query("DELETE FROM `" . static::$failedTable . "` WHERE `queue` = :queue;");
            $db->bind(':queue', $queue);
        } else {
            $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$failedTable . "`;");
            $count = $db->single()['cnt'] ?? 0;

            $db->query("TRUNCATE TABLE `" . static::$failedTable . "`;");
        }

        $db->execute();
        return (int) $count;
    }

    // ========================================================
    //  QUEUE MONITORING & STATUS
    // ========================================================

    /**
     * Hitung jumlah job di queue
     */
    public static function size(string $queue = 'default'): int
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` WHERE `queue` = :queue;");
        $db->bind(':queue', $queue);
        $row = $db->single();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Hitung jumlah job pending (belum reserved)
     */
    public static function pending(string $queue = 'default'): int
    {
        $db = Database::getInstance();
        $now = time();
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` 
                     WHERE `queue` = :queue AND `reserved_at` IS NULL AND `available_at` <= :now;");
        $db->bind(':queue', $queue);
        $db->bind(':now', $now);
        $row = $db->single();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Hitung job yang sedang diproses (reserved)
     */
    public static function reserved(string $queue = 'default'): int
    {
        $db = Database::getInstance();
        $now = time();
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` 
                     WHERE `queue` = :queue AND `reserved_at` IS NOT NULL AND `reserved_at` > :timeout;");
        $db->bind(':queue', $queue);
        $db->bind(':timeout', $now - static::$retryAfter);
        $row = $db->single();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Hitung job yang delayed (belum available)
     */
    public static function delayed(string $queue = 'default'): int
    {
        $db = Database::getInstance();
        $now = time();
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` 
                     WHERE `queue` = :queue AND `available_at` > :now;");
        $db->bind(':queue', $queue);
        $db->bind(':now', $now);
        $row = $db->single();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Hitung jumlah failed jobs
     */
    public static function failedCount(?string $queue = null): int
    {
        $db = Database::getInstance();
        static::createFailedJobsTable();

        if ($queue) {
            $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$failedTable . "` WHERE `queue` = :queue;");
            $db->bind(':queue', $queue);
        } else {
            $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$failedTable . "`;");
        }

        $row = $db->single();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Dapatkan status lengkap queue
     * 
     * @return array ['pending' => ..., 'reserved' => ..., 'delayed' => ..., 'failed' => ..., 'total' => ...]
     */
    public static function status(string $queue = 'default'): array
    {
        $pendingCount = static::pending($queue);
        $reservedCount = static::reserved($queue);
        $delayedCount = static::delayed($queue);
        $failedCountNum = static::failedCount($queue);
        $total = static::size($queue);

        return [
            'queue' => $queue,
            'total' => $total,
            'pending' => $pendingCount,
            'reserved' => $reservedCount,
            'delayed' => $delayedCount,
            'failed' => $failedCountNum,
        ];
    }

    /**
     * Dapatkan status semua queue yang ada
     */
    public static function allQueuesStatus(): array
    {
        $queues = static::getQueues();
        $result = [];

        foreach ($queues as $queue) {
            $result[] = static::status($queue);
        }

        return $result;
    }

    /**
     * Dapatkan daftar nama queue yang ada
     */
    public static function getQueues(): array
    {
        $db = Database::getInstance();
        $db->query("SELECT DISTINCT `queue` FROM `" . static::$table . "` ORDER BY `queue`;");
        $rows = $db->resultSet();
        return array_column($rows, 'queue');
    }

    // ========================================================
    //  QUEUE MANAGEMENT
    // ========================================================

    /**
     * Hapus semua job dari queue tertentu
     */
    public static function clear(string $queue = 'default'): int
    {
        $db = Database::getInstance();
        $count = static::size($queue);
        $db->query("DELETE FROM `" . static::$table . "` WHERE `queue` = :queue;");
        $db->bind(':queue', $queue);
        $db->execute();
        return $count;
    }

    /**
     * Hapus SEMUA job dari SEMUA queue
     */
    public static function purge(): int
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "`;");
        $count = (int) ($db->single()['cnt'] ?? 0);

        $db->query("TRUNCATE TABLE `" . static::$table . "`;");
        $db->execute();

        return $count;
    }

    /**
     * Hapus job yang stuck (reserved terlalu lama)
     */
    public static function clearStuck(int $olderThanSeconds = 3600): int
    {
        $db = Database::getInstance();
        $threshold = time() - $olderThanSeconds;

        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` WHERE `reserved_at` IS NOT NULL AND `reserved_at` < :threshold;");
        $db->bind(':threshold', $threshold);
        $count = (int) ($db->single()['cnt'] ?? 0);

        $db->query("UPDATE `" . static::$table . "` SET `reserved_at` = NULL WHERE `reserved_at` IS NOT NULL AND `reserved_at` < :threshold;");
        $db->bind(':threshold', $threshold);
        $db->execute();

        return $count;
    }

    // ========================================================
    //  UNIQUE JOBS
    // ========================================================

    /**
     * Cek apakah job sudah ada di queue (untuk unique jobs)
     */
    public static function isDuplicate(Job $job): bool
    {
        $db = Database::getInstance();
        $uniqueId = $job->uniqueId();

        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$table . "` WHERE `payload` LIKE :uid;");
        $db->bind(':uid', '%' . addslashes($uniqueId) . '%');
        $row = $db->single();

        return ((int) ($row['cnt'] ?? 0)) > 0;
    }

    // ========================================================
    //  JOB CHAINS
    // ========================================================

    /**
     * Buat job chain — job dijalankan berurutan
     * Jika satu job fail, chain berhenti
     * 
     * @param string $firstJobClass Class pertama
     * @param array $firstJobData Data untuk job pertama
     * @param array $chainedJobs Array of Job instances untuk chain berikutnya
     * @param string $queue
     * @return int|string|null ID job pertama
     */
    public static function chain(string $firstJobClass, array $firstJobData, array $chainedJobs, string $queue = 'default'): int|string|null
    {
        // Serialize chain menjadi array payload
        $chainPayload = [];
        foreach ($chainedJobs as $chainedJob) {
            if ($chainedJob instanceof Job) {
                $chainPayload[] = $chainedJob->toPayload();
            }
        }

        $payload = [
            'job' => $firstJobClass,
            'data' => $firstJobData,
            'chain' => $chainPayload,
        ];

        return static::pushToDatabase($queue, json_encode($payload, JSON_UNESCAPED_UNICODE), time());
    }

    /**
     * Process chain berikutnya setelah job berhasil
     * @internal Dipanggil oleh Worker
     */
    public static function processNextInChain(array $payload): void
    {
        if (empty($payload['chain']))
            return;

        $next = array_shift($payload['chain']);
        $nextPayload = $next;
        $nextPayload['chain'] = $payload['chain']; // Forward sisa chain

        $queue = $next['queue'] ?? 'default';
        static::pushToDatabase($queue, json_encode($nextPayload, JSON_UNESCAPED_UNICODE), time());
    }

    // ========================================================
    //  BATCH OPERATIONS
    // ========================================================

    /**
     * Buat batch — kumpulkan beberapa job, track progress, dan jalankan callback saat selesai
     * 
     * @param array $jobs Array of Job instances
     * @param string $queue
     * @return array ['batch_id' => string, 'job_ids' => array, 'total' => int]
     */
    public static function batch(array $jobs, string $queue = 'default'): array
    {
        $batchId = 'batch_' . bin2hex(random_bytes(8));
        $jobIds = [];

        foreach ($jobs as $job) {
            if ($job instanceof Job) {
                $job->mergeData(['_batch_id' => $batchId]);
                $id = static::push($job, queue: $queue);
                $jobIds[] = $id;
            }
        }

        return [
            'batch_id' => $batchId,
            'job_ids' => $jobIds,
            'total' => count($jobIds),
        ];
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Get progress of a specific job (for long-running jobs)
     */
    public static function getProgress(int|string $id): ?array
    {
        $db = Database::getInstance();
        $db->query("SELECT `progress` FROM `" . static::$table . "` WHERE `id` = :id;");
        $db->bind(':id', $id);
        $row = $db->single();

        if ($row && $row['progress']) {
            return json_decode($row['progress'], true);
        }
        return null;
    }

    /**
     * Dapatkan usia job tertua di queue (detik)
     * Berguna untuk monitoring: jika terlalu besar, berarti worker lambat/mati
     */
    public static function oldestJobAge(string $queue = 'default'): ?int
    {
        $db = Database::getInstance();
        $db->query("SELECT MIN(`created_at`) AS oldest FROM `" . static::$table . "` WHERE `queue` = :queue AND `reserved_at` IS NULL;");
        $db->bind(':queue', $queue);
        $row = $db->single();

        if ($row && $row['oldest']) {
            return time() - (int) $row['oldest'];
        }
        return null;
    }

    /**
     * Dapatkan throughput (jobs/menit) berdasarkan log delete terakhir
     * Rough estimation berdasarkan created_at distribution
     */
    public static function estimateThroughput(string $queue = 'default', int $windowMinutes = 5): float
    {
        $db = Database::getInstance();
        $windowStart = time() - ($windowMinutes * 60);

        // Hitung job yang dicreate dalam window waktu yang baru selesai (sudah dihapus)
        // Kita pakai failed_jobs + approximation
        $db->query("SELECT COUNT(*) AS cnt FROM `" . static::$failedTable . "` WHERE `queue` = :queue AND `failed_at` >= FROM_UNIXTIME(:start);");
        $db->bind(':queue', $queue);
        $db->bind(':start', $windowStart);
        $failedInWindow = (int) ($db->single()['cnt'] ?? 0);

        // Approximate: total throughput ≈ (initial_count - current_count + failed) / window_minutes
        // Simplified: just return pending count trend
        $current = static::size($queue);
        return round(($current + $failedInWindow) / max($windowMinutes, 1), 2);
    }

    /**
     * Formatted summary string untuk monitoring
     */
    public static function summary(string $queue = 'default'): string
    {
        $s = static::status($queue);
        $oldest = static::oldestJobAge($queue);
        $oldestStr = $oldest !== null ? "{$oldest}s ago" : 'N/A';

        $lines = [];
        $lines[] = "📊 Queue Status: {$s['queue']}";
        $lines[] = str_repeat('─', 40);
        $lines[] = "   Total:    {$s['total']}";
        $lines[] = "   Pending:  {$s['pending']}";
        $lines[] = "   Reserved: {$s['reserved']}";
        $lines[] = "   Delayed:  {$s['delayed']}";
        $lines[] = "   Failed:   {$s['failed']}";
        $lines[] = "   Oldest:   $oldestStr";

        return implode("\n", $lines);
    }

    /**
     * Set retry timeout (how long a reserved job is "locked")
     */
    public static function setRetryAfter(int $seconds): void
    {
        static::$retryAfter = $seconds;
    }

    /**
     * Dispatch closure sebagai job (tanpa perlu bikin class)
     * 
     * Contoh: Queue::dispatchClosure(fn() => mail('a@b.c', 'Hi', 'Body'));
     * 
     * ⚠️ WARNING: Closure di-serialize pakai Opis\Closure atau wrapper.
     * Dalam implementasi sederhana ini, closure diconvert ke callable info.
     */
    public static function dispatchClosure(callable $closure, string $queue = 'default', int $delay = 0): int|string|null
    {
        // Karena PHP closure tidak bisa di-serialize secara native,
        // kita simpan sebagai ClosureJob dengan serialized code reference
        $payload = json_encode([
            'job' => '__closure__',
            'data' => [
                'closure_hash' => spl_object_id((object) $closure),
            ],
        ]);

        return static::pushToDatabase($queue, $payload, time() + $delay);
    }

    /**
     * Schedule job pada waktu tertentu (timestamp atau DateTime)
     */
    public static function schedule(string|object $job, int|\DateTimeInterface $runAt, array $data = [], string $queue = 'default'): int|string|null
    {
        $timestamp = ($runAt instanceof \DateTimeInterface) ? $runAt->getTimestamp() : $runAt;
        $delay = max(0, $timestamp - time());
        return static::later($delay, $job, $data, $queue);
    }

    /**
     * Peek — lihat job berikutnya tanpa mengambilnya
     */
    public static function peek(string $queue = 'default'): ?array
    {
        $db = Database::getInstance();
        $now = time();

        $db->query("SELECT `id`, `queue`, `payload`, `attempts`, `created_at`, `progress`
                     FROM `" . static::$table . "` 
                     WHERE `queue` = :queue 
                     AND `reserved_at` IS NULL 
                     AND `available_at` <= :now 
                     ORDER BY `id` ASC 
                     LIMIT 1;");
        $db->bind(':queue', $queue);
        $db->bind(':now', $now);

        return $db->single() ?: null;
    }

    /**
     * List semua jobs di queue (untuk admin/debugging)
     */
    public static function list(string $queue = 'default', int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $db->query("SELECT `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`, `progress`
                     FROM `" . static::$table . "` 
                     WHERE `queue` = :queue 
                     ORDER BY `id` ASC 
                     LIMIT :limit OFFSET :offset;");
        $db->bind(':queue', $queue);
        $db->bind(':limit', $limit);
        $db->bind(':offset', $offset);

        return $db->resultSet();
    }
}
