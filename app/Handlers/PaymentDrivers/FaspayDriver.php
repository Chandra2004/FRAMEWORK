<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 Faspay Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class FaspayDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['merchant_id']) || empty($config['merchant_key'])) {
            throw new Exception("Faspay: merchant_id dan merchant_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $payload['merchant_id'] = $this->config['merchant_id'];
        $payload['merchant'] = $this->config['merchant_id'];
        $payload['signature'] = $this->generateSignature($payload);

        $response = $this->request('POST', '/300011/10', $payload);

        if (isset($response['redirect_url'])) {
            return $response['redirect_url'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $payload = [
            'request' => 'Inquiry Status',
            'merchant_id' => $this->config['merchant_id'],
            'bill_no' => $orderId,
            'signature' => sha1("##" . strtoupper($this->config['user_id']) . "##" . strtoupper($this->config['password']) . "##" . $orderId . "##"),
        ];

        return (object) $this->request('POST', '/100003/10', $payload);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function generateSignature(array $payload): string
    {
        $billNo = $payload['bill_no'] ?? '';
        return sha1("##" . strtoupper($this->config['user_id']) . "##" . strtoupper($this->config['password']) . "##" . $billNo . "##");
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
