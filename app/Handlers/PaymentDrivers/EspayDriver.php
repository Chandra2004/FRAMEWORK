<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 Espay Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class EspayDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['merchant_id']) || empty($config['api_key'])) {
            throw new Exception("Espay: merchant_id dan api_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $payload['merchantId'] = $this->config['merchant_id'];
        $payload['signature'] = $this->generateSignature($payload);

        $response = $this->request('POST', '/rest/merchant/trxorder', $payload);

        if (isset($response['url'])) {
            return $response['url'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $payload = [
            'merchantId' => $this->config['merchant_id'],
            'orderId' => $orderId,
            'signature' => hash_hmac('sha256', $this->config['merchant_id'] . $orderId, $this->config['signature_key']),
        ];
        return (object) $this->request('POST', '/rest/merchant/statusorder', $payload);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function generateSignature(array $payload): string
    {
        $orderId = $payload['orderId'] ?? '';
        $amount = $payload['amount'] ?? '0';
        return hash_hmac('sha256', $this->config['merchant_id'] . $orderId . $amount, $this->config['signature_key'] ?? $this->config['api_key']);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->config['api_key'],
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
