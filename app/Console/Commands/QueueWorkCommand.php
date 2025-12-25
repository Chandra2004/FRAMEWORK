<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use TheFramework\App\Queue;
use TheFramework\App\Config;

class QueueWorkCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'queue:work';
    }

    public function getDescription(): string
    {
        return 'Jalankan worker untuk memproses job dalam antrian.';
    }

    public function run(array $args): void
    {
        // Parsing argument sederhana: queue=default default
        $queueName = 'default';
        foreach ($args as $arg) {
            if (strpos($arg, 'queue=') === 0) {
                $queueName = substr($arg, 6);
            }
        }

        echo "\n\033[1;36m   THE FRAMEWORK \033[0m\033[90m Queue Worker ($queueName)\033[0m\n\n";

        $delay = 3; // Detik sleep jika kosong

        while (true) {
            try {
                $job = Queue::pop($queueName);

                if ($job) {
                    $this->processJob($job);
                } else {
                    // Istirahat jika tidak ada job
                    sleep($delay);
                }
            } catch (\Throwable $e) {
                echo "\033[31m[ERROR]\033[0m Worker Crash: " . $e->getMessage() . "\n";
                sleep(5); // Sleep extra jika DB error dsb
            }
        }
    }

    protected function processJob($jobRecord)
    {
        $id = $jobRecord['id'];
        $payload = json_decode($jobRecord['payload'], true);
        $jobClass = $payload['job'];
        $data = $payload['data'] ?? [];

        echo "\033[33m[PROCESSING]\033[0m $jobClass (ID: $id)...";

        try {
            if (!class_exists($jobClass)) {
                throw new \Exception("Job class $jobClass not found.");
            }

            // Instansiasi Job
            $instance = new $jobClass($data);

            // Jalankan handle
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }

            // Hapus dari antrian jika sukses
            Queue::delete($id);
            echo " \033[32mDONE\033[0m\n";

        } catch (\Throwable $e) {
            echo " \033[31mFAILED\033[0m\n";
            echo "    Error: " . $e->getMessage() . "\n";

            // Logic retry sederhana:
            // Jika attempts < 3, release kembali dengan delay
            if ($jobRecord['attempts'] < 3) {
                $retryDelay = 5 * $jobRecord['attempts']; // 5s, 10s, 15s
                Queue::release($id, $retryDelay);
                echo "    \033[90mReleased back to queue (delay {$retryDelay}s)\033[0m\n";
            } else {
                // Max attempts reached -> Failed Job (bisa dipindah ke tabel failed_jobs nanti)
                // Untuk sekarang kita hapus saja atau biarkan nyangkut (opsi: delete)
                Queue::delete($id); // Hapus agar tidak loop selamanya
                echo "    \033[41;37m BURY \033[0m Job dropped after 3 attempts.\n";
            }
        }
    }
}
