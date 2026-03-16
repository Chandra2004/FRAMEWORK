<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 iPay88 Indonesia Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class Ipay88Driver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['merchant_code']) || empty($config['merchant_key'])) {
            throw new Exception("iPay88: merchant_code dan merchant_key wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        // iPay88 menggunakan form POST redirect
        $payload['MerchantCode'] = $this->config['merchant_code'];
        $payload['Signature'] = $this->generateSignature(
            $payload['RefNo'] ?? '',
            $payload['Amount'] ?? '0',
            $payload['Currency'] ?? 'IDR'
        );

        return (object) [
            'action_url' => $this->config['base_url'] . '/ePayment/entry.asp',
            'params' => $payload,
            'method' => 'POST', // Developer harus buat form HTML dgn auto-submit
        ];
    }

    public function checkStatus(string $orderId): object
    {
        $payload = [
            'MerchantCode' => $this->config['merchant_code'],
            'RefNo' => $orderId,
            'Amount' => '', // Harus disertakan amount asli
        ];

        return (object) $this->request('POST', '/ePayment/enquiry.asp', $payload);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? $_POST);
    }

    protected function generateSignature(string $refNo, string $amount, string $currency): string
    {
        $source = $this->config['merchant_key'] . $this->config['merchant_code'] . $refNo . str_replace(['.', ','], '', $amount) . $currency;
        return base64_encode(sha1($source, true));
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? ['raw' => $response];
    }
}
