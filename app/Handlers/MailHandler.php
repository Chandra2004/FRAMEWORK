<?php

namespace TheFramework\Handlers;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;

/**
 * 📧 MailHandler - Beyond Laravel
 * Mempermudah pengiriman email SMTP dengan fitur Queue & Multiple Attachments.
 */
class MailHandler
{
    protected array $config;
    protected string $recipient;

    public function __construct(array $config = [])
    {
        $this->config = !empty($config) ? $config : (require ROOT_DIR . '/config/mail.php')['default'];
    }

    /**
     * Kirim email secara instan
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];

            $mail->setFrom($this->config['from'], $this->config['from_name']);
            $mail->addAddress($to);

            if (isset($options['reply_to'])) $mail->addReplyTo($options['reply_to']);
            if (isset($options['cc'])) foreach ((array)$options['cc'] as $cc) $mail->addCC($cc);
            if (isset($options['bcc'])) foreach ((array)$options['bcc'] as $bcc) $mail->addBCC($bcc);
            
            if (isset($options['attachments'])) {
                foreach ((array)$options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $mail->addAttachment($attachment);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Mail Error: " . $mail->ErrorInfo, 0, $e);
        }
    }

    /**
     * Shortcut untuk akses cepat seperti Laravel Mailable
     */
    public static function to(string $recipient): self
    {
        $instance = new self();
        $instance->recipient = $recipient;
        return $instance;
    }

    /**
     * Masukkan ke sistem antrean (Queue)
     */
    public function queue(string $subject, string $body, array $options = []): void
    {
        if (class_exists('TheFramework\App\Queue\Queue')) {
            \TheFramework\App\Queue\Queue::push(
                new \TheFramework\Jobs\SendMailJob($this->recipient, $subject, $body, $options)
            );
        } else {
            $this->send($this->recipient, $subject, $body, $options);
        }
    }
}
