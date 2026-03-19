<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Midtrans\Notification;
use Exception;

class MidtransDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists('\Midtrans\Config')) {
            throw new Exception("Midtrans SDK tidak ditemukan! Silakan install dengan: composer require midtrans/midtrans-php");
        }

        // Trick akses static untuk menghindari IDE error merah
        $configClass = '\Midtrans\Config';
        $configClass::$serverKey = $config['server_key'];
        $configClass::$isProduction = $config['is_production'] ?? false;
        $configClass::$isSanitized = $config['is_sanitized'] ?? true;
        $configClass::$is3ds = $config['is_3ds'] ?? true;
    }

    public function createTransaction(array $payload): string
    {
        if (!class_exists('\Midtrans\Snap')) {
            throw new Exception("Midtrans Snap class tidak ditemukan.");
        }

        try {
            $snapClass = '\Midtrans\Snap';
            return $snapClass::getSnapToken($payload);
        } catch (Exception $e) {
            throw new Exception("Midtrans Snap Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        if (!class_exists('\Midtrans\Transaction')) {
            throw new Exception("Midtrans Transaction class tidak ditemukan.");
        }

        try {
            $transClass = '\Midtrans\Transaction';
            return $transClass::status($orderId);
        } catch (Exception $e) {
            throw new Exception("Midtrans Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        $notifClass = '\Midtrans\Notification';
        return new $notifClass();
    }
}
