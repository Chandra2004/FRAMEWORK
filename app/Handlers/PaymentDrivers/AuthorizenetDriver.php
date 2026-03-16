<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Authorize.Net Payment Driver (REST API / AIM)
 * 📦 Tidak memerlukan SDK tambahan
 */
class AuthorizenetDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['api_login_id']) || empty($config['transaction_key'])) {
            throw new Exception("Authorize.Net: api_login_id dan transaction_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $requestData = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->config['api_login_id'],
                    'transactionKey' => $this->config['transaction_key'],
                ],
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => $payload['amount'] ?? '0.00',
                    'payment' => $payload['payment'] ?? [],
                    'order' => [
                        'invoiceNumber' => $payload['invoice_number'] ?? uniqid('INV-'),
                        'description' => $payload['description'] ?? '',
                    ],
                    'billTo' => $payload['billing'] ?? [],
                ],
            ],
        ];

        $response = $this->request($requestData);

        if (isset($response['transactionResponse']['transId'])) {
            return $response['transactionResponse']['transId'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $requestData = [
            'getTransactionDetailsRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->config['api_login_id'],
                    'transactionKey' => $this->config['transaction_key'],
                ],
                'transId' => $orderId,
            ],
        ];

        return (object) $this->request($requestData);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function request(array $data): array
    {
        $baseUrl = ($this->config['sandbox'] ?? true)
            ? 'https://apitest.authorize.net/xml/v1/request.api'
            : 'https://api.authorize.net/xml/v1/request.api';

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        // Authorize.Net returns BOM, need to strip it
        $response = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response);
        return json_decode($response, true) ?? [];
    }
}
