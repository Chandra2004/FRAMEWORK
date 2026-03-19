<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Adyen Payment Driver
 * 📦 composer require adyen/php-api-library
 */
class AdyenDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists('\Adyen\Client')) {
            throw new Exception("Adyen SDK not found. Run: composer require adyen/php-api-library");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        try {
            $clientClass = '\Adyen\Client';
            $client = new $clientClass();
            $client->setXApiKey($this->config['api_key']);
            
            $envClass = '\Adyen\Environment';
            $client->setEnvironment(
                $this->config['environment'] === 'live' ? constant($envClass . '::LIVE') : constant($envClass . '::TEST'),
                $this->config['live_prefix'] ?? null
            );

            $serviceClass = '\Adyen\Service\Checkout\PaymentsApi';
            $service = new $serviceClass($client);
            $params = array_merge([
                'merchantAccount' => $this->config['merchant_account'],
            ], $payload);

            $requestClass = '\Adyen\Model\Checkout\CreateCheckoutSessionRequest';
            $result = $service->sessions(new $requestClass($params));
            return $result->getSessionData();
        } catch (Exception $e) {
            throw new Exception("Adyen Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            $clientClass = '\Adyen\Client';
            $client = new $clientClass();
            $client->setXApiKey($this->config['api_key']);
            
            $envClass = '\Adyen\Environment';
            $client->setEnvironment(
                $this->config['environment'] === 'live' ? constant($envClass . '::LIVE') : constant($envClass . '::TEST')
            );

            // Menggunakan REST API langsung untuk cek status
            $baseUrl = $this->config['environment'] === 'live'
                ? "https://{$this->config['live_prefix']}-checkout-live.adyenpayments.com/checkout/v71"
                : 'https://checkout-test.adyen.com/v71';

            $ch = curl_init("$baseUrl/payments/details");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['details' => ['orderId' => $orderId]]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-API-key: ' . $this->config['api_key'],
                ],
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            return (object) (json_decode($response, true) ?? []);
        } catch (Exception $e) {
            throw new Exception("Adyen Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        $data = $postData ?? json_decode(file_get_contents('php://input'), true);
        return (object) ($data['notificationItems'][0]['NotificationRequestItem'] ?? $data);
    }
}
