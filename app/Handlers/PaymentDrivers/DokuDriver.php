<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 DOKU Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan (menggunakan cURL bawaan PHP)
 */
class DokuDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['client_id']) || empty($config['secret_key'])) {
            throw new Exception("DOKU: client_id dan secret_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $requestId = $payload['request_id'] ?? uniqid('doku-');
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $body = json_encode($payload);
        
        $signature = $this->generateSignature('POST', '/checkout/v1/payment', $body, $timestamp, $requestId);

        $response = $this->request('POST', '/checkout/v1/payment', $body, [
            'Client-Id: ' . $this->config['client_id'],
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $timestamp,
            'Signature: ' . $signature,
        ]);

        if (isset($response['response']['payment']['url'])) {
            return $response['response']['payment']['url'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $requestId = uniqid('doku-status-');
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $path = '/orders/v1/status/' . $orderId;
        
        $signature = $this->generateSignature('GET', $path, '', $timestamp, $requestId);

        return (object) $this->request('GET', $path, '', [
            'Client-Id: ' . $this->config['client_id'],
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $timestamp,
            'Signature: ' . $signature,
        ]);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function generateSignature(string $method, string $path, string $body, string $timestamp, string $requestId): string
    {
        $digest = base64_encode(hash('sha256', $body, true));
        $component = "Client-Id:{$this->config['client_id']}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:{$path}\nDigest:{$digest}";
        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $this->config['secret_key'], true));
    }

    protected function request(string $method, string $path, string $body = '', array $headers = []): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($method === 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
