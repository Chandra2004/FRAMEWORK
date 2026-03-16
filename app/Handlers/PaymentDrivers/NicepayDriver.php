<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 NICEPAY Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class NicepayDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['merchant_id']) || empty($config['merchant_key'])) {
            throw new Exception("NICEPAY: merchant_id dan merchant_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $timestamp = date('YmdHis');
        $payload['iMid'] = $this->config['merchant_id'];
        $payload['merchantToken'] = $this->generateToken($timestamp, $payload['referenceNo'] ?? '', $payload['amt'] ?? '0');

        $response = $this->request('POST', '/nicepay/direct/v2/registration', $payload);

        if (isset($response['tXid'])) {
            return $response['tXid']; // Transaction ID (redirect ke NICEPAY)
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $timestamp = date('YmdHis');
        $payload = [
            'iMid' => $this->config['merchant_id'],
            'tXid' => $orderId,
            'merchantToken' => $this->generateToken($timestamp, $orderId, '0'),
            'referenceNo' => $orderId,
            'amt' => '0',
        ];

        return (object) $this->request('POST', '/nicepay/direct/v2/inquiry', $payload);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true) ?? $_POST);
    }

    protected function generateToken(string $timestamp, string $referenceNo, string $amount): string
    {
        return hash('sha256', $timestamp . $this->config['merchant_id'] . $referenceNo . $amount . $this->config['merchant_key']);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
