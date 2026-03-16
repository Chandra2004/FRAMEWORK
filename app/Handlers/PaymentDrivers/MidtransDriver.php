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
        Config::$serverKey = $config['server_key'];
        Config::$isProduction = $config['is_production'] ?? false;
        Config::$isSanitized = $config['is_sanitized'] ?? true;
        Config::$is3ds = $config['is_3ds'] ?? true;
    }

    public function createTransaction(array $payload): string
    {
        try {
            return Snap::getSnapToken($payload);
        } catch (Exception $e) {
            throw new Exception("Midtrans Snap Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            return Transaction::status($orderId);
        } catch (Exception $e) {
            throw new Exception("Midtrans Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        return new Notification();
    }
}
