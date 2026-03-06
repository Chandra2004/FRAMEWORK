<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Queue\Queue;
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
                $jobRecord = Queue::pop();

                if ($jobRecord) {
                    $this->comment("Processing Job: [" . date('Y-m-d H:i:s') . "] ID: " . $jobRecord['id']);
                    $start = microtime(true);
                    
                    $payload = json_decode($jobRecord['payload'], true);
                    $jobClass = $payload['job'] ?? null;
                    $data = $payload['data'] ?? [];

                    if ($jobClass && class_exists($jobClass)) {
                        $instance = new $jobClass($data);
                        
                        if (method_exists($instance, 'setJobId')) {
                            $instance->setJobId($jobRecord['id']);
                        }
                        if (method_exists($instance, 'setAttempts')) {
                            $instance->setAttempts($jobRecord['attempts']);
                        }

                        $instance->handle();
                        
                        // Auto-delete if not handled by job
                        if (method_exists($instance, 'isDeleted') && !$instance->isDeleted() && 
                            method_exists($instance, 'isReleased') && !$instance->isReleased() && 
                            method_exists($instance, 'isFailed') && !$instance->isFailed()) {
                            Queue::delete($jobRecord['id']);
                        }
                    } else {
                        throw new \Exception("Job class [{$jobClass}] tidak ditemukan.");
                    }
                    
                    $duration = round((microtime(true) - $start) * 1000, 2);
                    $this->success("Job Selesai dalam {$duration}ms");
                }
            } catch (Throwable $e) {
                $this->error("Worker Error: " . $e->getMessage());
                if (isset($jobRecord['id'])) {
                    Queue::fail($jobRecord['id'], $e);
                }
            }

            usleep(1000000); // Sleep 1 sec to prevent CPU spikes
        }
    }
}
