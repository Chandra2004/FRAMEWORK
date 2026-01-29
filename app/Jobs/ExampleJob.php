<?php

namespace App\Jobs;

use TheFramework\App\Job;

class ExampleJob extends Job
{
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        // Example logic
        $email = $this->data['email'] ?? 'test@example.com';

        echo "[" . date('Y-m-d H:i:s') . "] Processing job for: {$email}\n";

        // Simulate work
        sleep(1);

        return true;
    }
}
