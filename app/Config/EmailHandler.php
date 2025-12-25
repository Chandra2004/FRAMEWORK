<?php

namespace TheFramework\Config;

use PHPMailer\PHPMailer\PHPMailer;
use TheFramework\App\Config;

/**
 * Handle pengiriman email via SMTP.
 * 
 * 📦 REQUIREMENT:
 * composer require phpmailer/phpmailer
 * 
 * ⚙️ .ENV CONFIGURATION:
 * MAIL_HOST=smtp.mailtrap.io
 * MAIL_PORT=2525
 * MAIL_USERNAME=your_username
 * MAIL_PASSWORD=your_password
 * MAIL_FROM=noreply@example.com
 * MAIL_FROM_NAME="My App Name"
 */
class EmailHandler
{
    /**
     * Kirim email dengan konfigurasi SMTP dari .env
     * 
     * @param string $to Email penerima
     * @param string $subject Subjek email
     * @param string $body Isi email (HTML support)
     * @param array $options [cc, bcc, attachments]
     * @return bool True jika berhasil
     * @throws Exception Jika gagal kirim
     */
    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        Config::loadEnv();

        if (empty($to))
            throw new Exception("Recipient (To) address is required.");
        if (empty($subject))
            throw new Exception("Subject is required.");
        if (empty($body))
            throw new Exception("Email body is required.");

        $mailHost = Config::get('MAIL_HOST');
        $mailPort = Config::get('MAIL_PORT', 587);
        $mailUser = Config::get('MAIL_USERNAME');
        $mailPass = Config::get('MAIL_PASSWORD');
        $mailFrom = Config::get('MAIL_FROM') ?? $mailUser;
        $mailName = Config::get('MAIL_FROM_NAME', 'No Reply');

        if (!$mailHost || !$mailUser || !$mailPass) {
            throw new Exception("SMTP Configuration is missing in .env");
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUser;
            $mail->Password = $mailPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;

            $mail->setFrom($mailFrom, $mailName);
            $mail->addAddress($to);

            if (!empty($options['cc'])) {
                foreach ((array) $options['cc'] as $cc)
                    $mail->addCC($cc);
            }
            if (!empty($options['bcc'])) {
                foreach ((array) $options['bcc'] as $bcc)
                    $mail->addBCC($bcc);
            }
            if (!empty($options['attachments'])) {
                foreach ((array) $options['attachments'] as $file) {
                    if (file_exists($file))
                        $mail->addAttachment($file);
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Mail Error: " . $mail->ErrorInfo);
        }
    }
}
