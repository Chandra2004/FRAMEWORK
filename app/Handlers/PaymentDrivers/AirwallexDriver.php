<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Airwallex Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class AirwallexDriver implements PaymentDriverInterface
{
    protected array $config;
    protected ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['api_key']) || empty($config['client_id'])) {
            throw new Exception("Airwallex: api_key dan client_id wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $this->authenticate();

        $intentData = [
            'request_id' => $payload['request_id'] ?? uniqid('AW-'),
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'USD',
            'merchant_order_id' => $payload['order_id'] ?? uniqid('ORD-'),
            'order' => $payload['order'] ?? [],
        ];

        $response = $this->request('POST', '/payment_intents/create', $intentData);

        if (isset($response['id'])) {
            // Confirm untuk mendapatkan checkout URL
            $confirm = $this->request('POST', "/payment_intents/{$response['id']}/confirm", [
                'request_id' => uniqid('CONF-'),
            ]);
            return $confirm['next_action']['url'] ?? $response['id'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $this->authenticate();
        return (object) $this->request('GET', '/payment_intents/' . $orderId);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function authenticate(): void
    {
        if ($this->accessToken) return;

        $ch = curl_init($this->config['base_url'] . '/authentication/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['x-client-id' => $this->config['client_id']]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->config['api_key'],
                'x-client-id: ' . $this->config['client_id'],
            ],
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $this->accessToken = $response['token'] ?? null;
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
                'Authorization: Bearer ' . $this->accessToken,
            ],
        ]);
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
