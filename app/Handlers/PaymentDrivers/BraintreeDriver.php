<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌍 Braintree Payment Driver (by PayPal)
 * 📦 composer require braintree/braintree_php
 */
class BraintreeDriver implements PaymentDriverInterface
{
    protected array $config;
    protected $gateway;

    public function __construct(array $config)
    {
        if (!class_exists('\Braintree\Gateway')) {
            throw new Exception("Braintree SDK not found. Run: composer require braintree/braintree_php");
        }

        $this->config = $config;
        $this->gateway = new \Braintree\Gateway([
            'environment' => $config['environment'] ?? 'sandbox',
            'merchantId'  => $config['merchant_id'],
            'publicKey'   => $config['public_key'],
            'privateKey'  => $config['private_key'],
        ]);
    }

    public function createTransaction(array $payload): mixed
    {
        try {
            // Jika butuh client token untuk Drop-in UI
            if (($payload['type'] ?? '') === 'client_token') {
                return $this->gateway->clientToken()->generate();
            }

            // Proses transaksi langsung
            $result = $this->gateway->transaction()->sale([
                'amount' => $payload['amount'] ?? '0.00',
                'paymentMethodNonce' => $payload['nonce'] ?? '',
                'orderId' => $payload['order_id'] ?? uniqid('BT-'),
                'options' => ['submitForSettlement' => true],
            ]);

            if ($result->success) {
                return $result->transaction->id;
            }

            throw new Exception($result->message);
        } catch (Exception $e) {
            throw new Exception("Braintree Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            $transaction = $this->gateway->transaction()->find($orderId);
            return (object) [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currencyIsoCode,
                'created_at' => $transaction->createdAt->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            throw new Exception("Braintree Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        try {
            $signature = $postData['bt_signature'] ?? $_POST['bt_signature'] ?? '';
            $payload = $postData['bt_payload'] ?? $_POST['bt_payload'] ?? '';
            $notification = $this->gateway->webhookNotification()->parse($signature, $payload);
            return (object) [
                'kind' => $notification->kind,
                'timestamp' => $notification->timestamp,
                'subject' => $notification->subject,
            ];
        } catch (Exception $e) {
            throw new Exception("Braintree Webhook Error: " . $e->getMessage());
        }
    }
}
