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
     * Kirim email dengan fitur Intelligent Fallback & Logging
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        // 1. Validasi Input Dasar
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logMail("FAILED: Invalid recipient address: '$to'");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // 2. Konfigurasi SMTP
            $mail->isSMTP();
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;
            $mail->Timeout    = 25; 
            $mail->Host       = $this->config['host'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'] ?? '';
            $mail->Password   = $this->config['password'] ?? '';
            
            // Auto-Encryption Berdasarkan Port
            $port = (int)($this->config['port'] ?? 587);
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }
            $mail->Port = $port;

            // 3. Metadata Pengirim & Penerima
            $fromEmail = $this->config['from'] ?? $mail->Username;
            $fromName  = $this->config['from_name'] ?? 'No Reply';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->addAddress($to);

            // 4. Header Anti-Spam & Branding
            $mail->XMailer  = 'TheFramework Mailer v5.1';
            $mail->Priority = 1;

            // 5. Options (CC, BCC, Attachments)
            if (isset($options['cc'])) foreach ((array)$options['cc'] as $cc) $mail->addCC($cc);
            if (isset($options['bcc'])) foreach ((array)$options['bcc'] as $bcc) $mail->addBCC($bcc);
            
            if (isset($options['attachments'])) {
                foreach ((array)$options['attachments'] as $file) {
                    is_array($file) ? $mail->addAttachment($file['path'], $file['name'] ?? '') : $mail->addAttachment($file);
                }
            }

            // 6. Konten Email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<p>'], ["\n", "\n\n"], $body));

            $status = $mail->send();
            
            if ($status) $this->logMail("SUCCESS: Sent to $to | Subject: $subject");
            
            return $status;

        } catch (Exception $e) {
            $errorMsg = "MAIL ERROR to $to: " . $mail->ErrorInfo;
            $this->logMail($errorMsg);
            
            // Fallback: Jika di Localhost, cukup kembalikan false (jangan lempar exception ke user)
            $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            
            if ($isLocal || ($this->config['debug'] ?? false)) {
                return false; 
            }
            
            throw new Exception($errorMsg, 0, $e);
        }
    }

    /**
     * Mencatat aktivitas email ke log file. Berguna untuk trace jika SMTP bermasalah.
     */
    protected function logMail(string $message): void
    {
        try {
            $logPath = defined('ROOT_DIR') ? ROOT_DIR . '/storage/logs/mail.log' : __DIR__ . '/../../storage/logs/mail.log';
            $dir = dirname($logPath);
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            
            $timestamp = date('Y-m-d H:i:s');
            @file_put_contents($logPath, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $th) {
            // Silently fail if log is not writable
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
