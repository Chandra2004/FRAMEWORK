<?php

namespace TheFramework\Helpers;

use Exception;
use TheFramework\App\Core\Config;

/**
 * Crypter — Secure Data Encryption
 * 
 * Menggunakan AES-256-CBC dengan HMAC-SHA256 untuk autentikasi data.
 * Membutuhkan APP_KEY di file .env.
 * 
 * @package TheFramework\Helpers
 * @version 5.0.1
 */
class Crypter
{
    /**
     * Algorithm cipher yang digunakan
     */
    private const CIPHER = 'AES-256-CBC';

    /**
     * Encrypt data menggunakan APP_KEY
     * 
     * @param string $value
     * @return string
     * @throws Exception
     */
    public static function encrypt(string $value): string
    {
        $key = self::getKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));

        $encrypted = openssl_encrypt($value, self::CIPHER, $key, 0, $iv);

        if ($encrypted === false) {
            throw new Exception('Encryption failed.');
        }

        // Gabungkan IV dan data terenkripsi (base64)
        $payload = base64_encode($iv . $encrypted);

        // Tambahkan HMAC untuk integritas data
        $hmac = hash_hmac('sha256', $payload, $key);

        return $payload . '.' . $hmac;
    }

    /**
     * Decrypt data menggunakan APP_KEY
     * 
     * @param string $payloadWithHmac
     * @return string
     * @throws Exception
     */
    public static function decrypt(string $payloadWithHmac): string
    {
        $key = self::getKey();

        if (!str_contains($payloadWithHmac, '.')) {
            throw new Exception('Invalid encrypted payload format.');
        }

        [$payload, $hmac] = explode('.', $payloadWithHmac);

        // Verifikasi HMAC (timing-attack safe comparison)
        $calculatedHmac = hash_hmac('sha256', $payload, $key);
        if (!hash_equals($calculatedHmac, $hmac)) {
            throw new Exception('Encryption integrity check failed (HMAC mismatch).');
        }

        $decoded = base64_decode($payload);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, 0, $iv);

        if ($decrypted === false) {
            throw new Exception('Decryption failed.');
        }

        return $decrypted;
    }

    /**
     * Generate random strong key (untuk .env)
     * 
     * @return string
     */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(openssl_random_pseudo_bytes(32));
    }

    /**
     * Ambil key dari Config/Env
     * 
     * @return string
     * @throws Exception
     */
    private static function getKey(): string
    {
        $key = Config::get('APP_KEY');

        if (empty($key)) {
            throw new Exception('APP_KEY tidak diset di .env. Gunakan php artisan key:generate.');
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (strlen($key) !== 32) {
            throw new Exception('APP_KEY harus memiliki panjang 32 bytes (256-bit).');
        }

        return $key;
    }
}
