<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 iPaymu Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class IpaymuDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['va']) || empty($config['api_key'])) {
            throw new Exception("iPaymu: va dan api_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $body = json_encode($payload);
        $signature = $this->generateSignature($body);

        $response = $this->request('POST', '/payment/direct', $body, $signature);

        if (isset($response['Data']['Url'])) {
            return $response['Data']['Url'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $body = json_encode(['transactionId' => $orderId, 'account' => $this->config['va']]);
        $signature = $this->generateSignature($body);
        return (object) $this->request('POST', '/transaction', $body, $signature);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? $_POST);
    }

    protected function generateSignature(string $body): string
    {
        $hash = hash('sha256', strtolower(hash('sha256', $this->config['api_key'])) . strtolower(hash('sha256', $body)));
        return $hash;
    }

    protected function request(string $method, string $path, string $body, string $signature): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'va: ' . $this->config['va'],
                'signature: ' . $signature,
                'timestamp: ' . date('YmdHis'),
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
