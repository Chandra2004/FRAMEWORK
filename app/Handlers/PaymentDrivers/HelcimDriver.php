<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Helcim Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class HelcimDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['api_token'])) {
            throw new Exception("Helcim: api_token wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $paymentData = [
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'USD',
            'paymentMethod' => $payload['payment_method'] ?? 'cc', // cc, ach, etc.
            'cardToken' => $payload['card_token'] ?? '',
            'invoiceNumber' => $payload['invoice_number'] ?? uniqid('HLC-'),
        ];

        $response = $this->request('POST', '/payment/purchase', $paymentData);

        if (isset($response['transactionId'])) {
            return $response['transactionId'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        return (object) $this->request('GET', '/payment/transaction/' . $orderId);
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
                'api-token: ' . $this->config['api_token'],
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
