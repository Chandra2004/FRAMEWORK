<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Square Payment Driver
 * 📦 composer require square/square
 */
class SquareDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists('\Square\SquareClient')) {
            throw new Exception("Square SDK not found. Run: composer require square/square");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        try {
            $clientClass = '\Square\SquareClient';
            $client = new $clientClass([
                'accessToken' => $this->config['access_token'],
                'environment' => $this->config['environment'] === 'production' ? 'production' : 'sandbox',
            ]);

            $checkoutApi = $client->getCheckoutApi();
            
            $bodyClass = '\Square\Models\CreatePaymentLinkRequest';
            $body = new $bodyClass();
            
            $quickPayClass = '\Square\Models\QuickPay';
            $moneyClass = '\Square\Models\Money';
            $quickPay = new $quickPayClass(
                $payload['name'] ?? 'Order',
                new $moneyClass($payload['amount'] ?? 0, $payload['currency'] ?? 'USD'),
                $this->config['location_id']
            );
            $body->setQuickPay($quickPay);

            $result = $checkoutApi->createPaymentLink($body);

            if ($result->isSuccess()) {
                return $result->getResult()->getPaymentLink()->getUrl();
            }

            throw new Exception(json_encode($result->getErrors()));
        } catch (Exception $e) {
            throw new Exception("Square Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            $clientClass = '\Square\SquareClient';
            $client = new $clientClass([
                'accessToken' => $this->config['access_token'],
                'environment' => $this->config['environment'] === 'production' ? 'production' : 'sandbox',
            ]);

            $result = $client->getPaymentsApi()->getPayment($orderId);

            if ($result->isSuccess()) {
                return (object) $result->getResult()->getPayment()->jsonSerialize();
            }

            throw new Exception(json_encode($result->getErrors()));
        } catch (Exception $e) {
            throw new Exception("Square Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }
}
