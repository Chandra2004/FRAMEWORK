<?php

namespace TheFramework\Jobs;

use TheFramework\App\Queue\Job;
use TheFramework\Handlers\MailHandler;

class SendMailJob extends Job
{
    /**
     * @param string $recipient
     * @param string $subject
     * @param string $body
     * @param array $options
     */
    public function __construct(string $recipient, string $subject, string $body, array $options = [])
    {
        parent::__construct([
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'options' => $options
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $handler = new MailHandler();
        $handler->send(
            $this->data['recipient'],
            $this->data['subject'],
            $this->data['body'],
            $this->data['options'] ?? []
        );
    }
}
