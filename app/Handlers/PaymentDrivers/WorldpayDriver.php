<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Worldpay (FIS) Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class WorldpayDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['service_key'])) {
            throw new Exception("Worldpay: service_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $orderData = [
            'transactionReference' => $payload['reference'] ?? uniqid('WP-'),
            'merchant' => ['entity' => $payload['entity'] ?? 'default'],
            'instruction' => [
                'narrative' => ['line1' => $payload['description'] ?? 'Payment'],
                'value' => [
                    'currency' => $payload['currency'] ?? 'USD',
                    'amount' => $payload['amount'] ?? 0,
                ],
                'paymentInstrument' => $payload['payment_instrument'] ?? [],
            ],
        ];

        $response = $this->request('POST', '/payments/authorizations', $orderData);

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        return (object) $this->request('GET', '/payments/events/' . $orderId);
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
                'Content-Type: application/vnd.worldpay.payments-v6+json',
                'Authorization: ' . $this->config['service_key'],
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
