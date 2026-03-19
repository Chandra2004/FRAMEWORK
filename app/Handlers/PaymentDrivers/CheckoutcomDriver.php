<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Checkout.com Payment Driver
 * 📦 composer require checkout/checkout-sdk-php
 */
class CheckoutcomDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists('\Checkout\CheckoutSdk')) {
            throw new Exception("Checkout.com SDK not found. Run: composer require checkout/checkout-sdk-php");
        }
    }

    public function createTransaction(array $payload): mixed
    {
        try {
            $api = $this->getApi();

            $paymentRequestClass = '\Checkout\Payments\Request\PaymentRequest';
            $sourceClass = '\Checkout\Payments\Request\Source\RequestTokenSource';
            
            $paymentRequest = new $paymentRequestClass();
            $paymentRequest->source = new $sourceClass();
            $paymentRequest->source->token = $payload['token'] ?? '';
            $paymentRequest->amount = $payload['amount'] ?? 0;
            $paymentRequest->currency = $payload['currency'] ?? 'USD';
            $paymentRequest->reference = $payload['reference'] ?? uniqid('CKO-');
            $paymentRequest->capture = true;

            $response = $api->getPaymentsClient()->requestPayment($paymentRequest);
            return $response['id'] ?? (object) $response;
        } catch (Exception $e) {
            throw new Exception("Checkout.com Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            $api = $this->getApi();
            $response = $api->getPaymentsClient()->getPaymentDetails($orderId);
            return (object) $response;
        } catch (Exception $e) {
            throw new Exception("Checkout.com Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        return (object) ($postData ?? json_decode(file_get_contents('php://input'), true));
    }

    protected function getApi()
    {
        $sdkClass = '\Checkout\CheckoutSdk';
        $builder = $sdkClass::builder()->staticKeys();
        $builder->secretKey($this->config['secret_key']);
        $builder->publicKey($this->config['public_key'] ?? '');

        $envClass = '\Checkout\Environment';
        if (($this->config['environment'] ?? 'sandbox') === 'production') {
            $builder->environment($envClass::production());
        } else {
            $builder->environment($envClass::sandbox());
        }

        return $builder->build();
    }
}
