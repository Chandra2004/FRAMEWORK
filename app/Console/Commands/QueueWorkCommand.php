<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Core\Queue;
use Throwable;

class QueueWorkCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'queue:work';
    }

    public function getDescription(): string
    {
        return 'Mulai memproses job di antrean (Worker)';
    }

    public function handle(array $args): void
    {
        $this->info("Antrean Worker sedang berjalan...");
        $this->comment("Tekan Ctrl+C untuk berhenti.");

        while (true) {
            try {
                $job = Queue::pop();

                if ($job) {
                    $this->comment("Processing Job: [" . date('Y-m-d H:i:s') . "]");
                    $start = microtime(true);
                    
                    // Logic to execute job
                    $job->handle();
                    
                    $duration = round((microtime(true) - $start) * 1000, 2);
                    $this->success("Job Selesai dalam {$duration}ms");
                }
            } catch (Throwable $e) {
                $this->error("Worker Error: " . $e->getMessage());
            }

            usleep(1000000); // Sleep 1 sec to prevent CPU spikes
        }
    }
}
