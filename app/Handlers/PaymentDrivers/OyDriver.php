<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 OY! Indonesia Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class OyDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['api_key']) || empty($config['username'])) {
            throw new Exception("OY!: api_key dan username wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $response = $this->request('POST', '/api/payment-checkout/create-v2', $payload);

        if (isset($response['url'])) {
            return $response['url'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $response = $this->request('GET', '/api/payment-checkout/status?partner_tx_id=' . urlencode($orderId));
        return (object) $response;
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function request(string $method, string $path, ?array $data = null): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-oy-username: ' . $this->config['username'],
                'x-api-key: ' . $this->config['api_key'],
            ],
        ]);
        if ($data && $method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
