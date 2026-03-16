<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 PayPal Payment Driver
 * 📦 composer require paypal/rest-api-sdk-php
 */
class PaypalDriver implements PaymentDriverInterface
{
    protected array $config;
    protected ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new Exception("PayPal: client_id dan client_secret wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $this->authenticate();

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => $payload['purchase_units'] ?? [[
                'amount' => [
                    'currency_code' => $payload['currency'] ?? 'USD',
                    'value' => $payload['amount'] ?? '0.00',
                ],
                'description' => $payload['description'] ?? '',
            ]],
            'application_context' => [
                'return_url' => $payload['success_url'] ?? '',
                'cancel_url' => $payload['cancel_url'] ?? '',
            ],
        ];

        $response = $this->request('POST', '/v2/checkout/orders', $orderData);

        // Cari link approval
        foreach ($response['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') return $link['href'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $this->authenticate();
        return (object) $this->request('GET', '/v2/checkout/orders/' . $orderId);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function authenticate(): void
    {
        if ($this->accessToken) return;

        $baseUrl = $this->config['mode'] === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->config['client_id'] . ':' . $this->config['client_secret'],
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $this->accessToken = $response['access_token'] ?? null;

        if (!$this->accessToken) {
            throw new Exception("PayPal: Failed to authenticate. Check client_id and secret.");
        }
    }

    protected function request(string $method, string $path, ?array $data = null): array
    {
        $baseUrl = $this->config['mode'] === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
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
