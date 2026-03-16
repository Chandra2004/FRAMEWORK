<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 DANA for Business Payment Driver (REST API)
 * 📦 Tidak memerlukan SDK tambahan
 */
class DanaDriver implements PaymentDriverInterface
{
    protected array $config;
    protected ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new Exception("DANA: client_id dan client_secret wajib diisi di .env");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        $this->authenticate();

        $response = $this->request('POST', '/dana/acquiring/order/createOrder.htm', $payload);

        if (isset($response['response']['body']['acquirementId'])) {
            return $response['response']['body']['checkoutUrl'] ?? $response['response']['body']['acquirementId'];
        }

        return (object) $response;
    }

    public function checkStatus(string $orderId): object
    {
        $this->authenticate();
        return (object) $this->request('POST', '/dana/acquiring/order/query.htm', [
            'acquirementId' => $orderId,
        ]);
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function authenticate(): void
    {
        if ($this->accessToken) return;

        $response = $this->request('POST', '/dana/oauth/auth/applyToken.htm', [
            'grantType' => 'CLIENT_CREDENTIALS',
        ], true);

        $this->accessToken = $response['response']['body']['accessToken'] ?? null;
    }

    protected function request(string $method, string $path, array $data = [], bool $isAuth = false): array
    {
        $headers = ['Content-Type: application/json'];
        
        if ($isAuth) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
        } elseif ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $ch = curl_init($this->config['base_url'] . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['request' => ['body' => $data]]),
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
