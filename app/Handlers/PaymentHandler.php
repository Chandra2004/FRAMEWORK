<?php

namespace TheFramework\Handlers;

use Exception;

/**
 * 💳 PaymentHandler - Premium Midtrans Wrapper
 * Mempermudah integrasi pembayaran Midtrans Snap & Status Checking.
 */
class PaymentHandler
{
    protected array $config;

    public function __construct()
    {
        $configFile = ROOT_DIR . '/config/payment.php';
        if (!file_exists($configFile)) {
            throw new \RuntimeException(
                "File konfigurasi payment tidak ditemukan: config/payment.php. " .
                "Salin dari config/payment.example.php atau buat manual."
            );
        }
        $config = require $configFile;
        $this->config = $config['midtrans'] ?? throw new \RuntimeException(
            "Key 'midtrans' tidak ditemukan di config/payment.php"
        );
        $this->init();
    }

    protected function init()
    {
        \Midtrans\Config::$serverKey = $this->config['server_key'];
        \Midtrans\Config::$isProduction = $this->config['is_production'];
        \Midtrans\Config::$isSanitized = $this->config['is_sanitized'];
        \Midtrans\Config::$is3ds = $this->config['is_3ds'];
    }

    /**
     * Ambil Snap Token untuk ditampilkan di frontend
     */
    public function getSnapToken(array $payload): string
    {
        try {
            return \Midtrans\Snap::getSnapToken($payload);
        } catch (Exception $e) {
            throw new Exception("Midtrans Error: " . $e->getMessage());
        }
    }

    /**
     * Cek status transaksi langsung ke server Midtrans
     */
    public function status(string $orderId): object
    {
        try {
            return \Midtrans\Transaction::status($orderId);
        } catch (Exception $e) {
            throw new Exception("Midtrans Status Error: " . $e->getMessage());
        }
    }

    /**
     * Handle Webhook Notification
     */
    public function handleNotification(): object
    {
        return new \Midtrans\Notification();
    }
}
