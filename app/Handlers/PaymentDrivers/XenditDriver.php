<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🇮🇩 Xendit Payment Driver
 * 📦 composer require xendit/xendit-php
 */
class XenditDriver implements PaymentDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!class_exists('\Xendit\Xendit') && !class_exists('\Xendit\Configuration')) {
            throw new Exception("Xendit SDK not found. Run: composer require xendit/xendit-php");
        }

        if (class_exists('\Xendit\Configuration')) {
            $configClass = '\Xendit\Configuration';
            $configClass::setXenditKey($config['secret_key']);
        } elseif (class_exists('\Xendit\Xendit')) {
            $xenditClass = '\Xendit\Xendit';
            $xenditClass::setApiKey($config['secret_key']);
        }
    }

    public function createTransaction(array $payload): mixed
    {
        try {
            if (class_exists('\Xendit\Invoice\InvoiceApi')) {
                $invoiceApiClass = '\Xendit\Invoice\InvoiceApi';
                $requestClass = '\Xendit\Invoice\CreateInvoiceRequest';
                $api = new $invoiceApiClass();
                $result = $api->createInvoice(new $requestClass($payload));
                return $result->getInvoiceUrl();
            }

            // Fallback: Legacy SDK
            $legacyInvoiceClass = '\Xendit\Invoice';
            return $legacyInvoiceClass::create($payload)['invoice_url'];
        } catch (Exception $e) {
            throw new Exception("Xendit Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            if (class_exists('\Xendit\Invoice\InvoiceApi')) {
                $invoiceApiClass = '\Xendit\Invoice\InvoiceApi';
                $api = new $invoiceApiClass();
                return (object) $api->getInvoiceById($orderId);
            }
            $legacyInvoiceClass = '\Xendit\Invoice';
            return (object) $legacyInvoiceClass::retrieve($orderId);
        } catch (Exception $e) {
            throw new Exception("Xendit Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        $data = $postData ?? json_decode(file_get_contents('php://input'), true);
        
        // Verifikasi webhook token jika dikonfigurasi
        if (!empty($this->config['webhook_token'])) {
            $headerToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
            if ($headerToken !== $this->config['webhook_token']) {
                throw new Exception("Xendit Webhook: Invalid callback token.");
            }
        }

        return (object) $data;
    }
}
