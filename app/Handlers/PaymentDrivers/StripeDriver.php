<?php

namespace TheFramework\Handlers\PaymentDrivers;

use Exception;

/**
 * 🌎 Stripe International Driver
 * Membutuhkan: composer require stripe/stripe-php
 */
class StripeDriver implements PaymentDriverInterface
{
    protected array $config;
    protected $stripe;

    public function __construct(array $config)
    {
        if (!class_exists('\Stripe\StripeClient')) {
            throw new Exception("Stripe PHP Library not found. Run: composer require stripe/stripe-php");
        }
        
        $this->config = $config;
        $this->stripe = new \Stripe\StripeClient($config['secret_key']);
    }

    public function createTransaction(array $payload): string
    {
        try {
            // Stripe biasanya menggunakan Checkout Session
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => $payload['methods'] ?? ['card'],
                'line_items' => $payload['items'],
                'mode' => 'payment',
                'success_url' => $payload['success_url'],
                'cancel_url' => $payload['cancel_url'],
            ]);
            return $session->url;
        } catch (Exception $e) {
            throw new Exception("Stripe Session Error: " . $e->getMessage());
        }
    }

    public function checkStatus(string $orderId): object
    {
        try {
            return (object)$this->stripe->paymentIntents->retrieve($orderId)->toArray();
        } catch (Exception $e) {
            throw new Exception("Stripe Status Error: " . $e->getMessage());
        }
    }

    public function handleWebhook(?array $postData = null): object
    {
        // Implementasi webhook stripe
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        try {
            return (object)\Stripe\Webhook::constructEvent(
                $payload, $sig_header, $this->config['webhook_secret']
            )->toArray();
        } catch (Exception $e) {
             throw new Exception("Stripe Webhook Error: " . $e->getMessage());
        }
    }
}
