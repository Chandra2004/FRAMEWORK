<?php

/**
 * 💳 Payment Gateway Configuration — The Framework v5.1
 * 
 * Semua nilai diambil dari .env. Developer cukup:
 * 1. composer require [package-yang-dibutuhkan]
 * 2. Isi .env dengan kredensial dari dashboard provider
 * 3. Langsung pakai: PaymentHandler::driver('midtrans')->createTransaction($payload)
 * 
 * ═══════════════════════════════════════════════════════════
 *  🇮🇩 NASIONAL (Indonesia)
 * ═══════════════════════════════════════════════════════════
 *  midtrans  → composer require midtrans/midtrans-php
 *  xendit    → composer require xendit/xendit-php
 *  doku      → (REST API, tanpa SDK khusus)
 *  faspay    → (REST API, tanpa SDK khusus)
 *  nicepay   → (REST API, tanpa SDK khusus)
 *  ipay88    → (REST API, tanpa SDK khusus)
 *  ipaymu    → (REST API, tanpa SDK khusus)
 *  oy        → (REST API, tanpa SDK khusus)
 *  dana      → (REST API, tanpa SDK khusus)
 *  espay     → (REST API, tanpa SDK khusus)
 * 
 * ═══════════════════════════════════════════════════════════
 *  🌍 INTERNASIONAL (Global)
 * ═══════════════════════════════════════════════════════════
 *  stripe       → composer require stripe/stripe-php
 *  paypal       → composer require paypal/rest-api-sdk-php
 *  adyen        → composer require adyen/php-api-library
 *  square       → composer require square/square
 *  authorizenet → (REST API via AIM)
 *  braintree    → composer require braintree/braintree_php
 *  checkoutcom  → composer require checkout/checkout-sdk-php
 *  worldpay     → (REST API, tanpa SDK khusus)
 *  airwallex    → (REST API, tanpa SDK khusus)
 *  helcim       → (REST API, tanpa SDK khusus)
 */

return [

    // ┌─────────────────────────────────────────────────┐
    // │  DEFAULT DRIVER                                 │
    // │  Ganti via .env: PAYMENT_GATEWAY=xendit         │
    // └─────────────────────────────────────────────────┘
    'default' => $_ENV['PAYMENT_GATEWAY'] ?? 'midtrans',

    // ═══════════════════════════════════════════════════
    //  🇮🇩 NASIONAL (Indonesia)
    // ═══════════════════════════════════════════════════

    'midtrans' => [
        'server_key'    => $_ENV['MIDTRANS_SERVER_KEY'] ?? null,
        'client_key'    => $_ENV['MIDTRANS_CLIENT_KEY'] ?? null,
        'is_production' => filter_var($_ENV['MIDTRANS_IS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'is_sanitized'  => true,
        'is_3ds'        => true,
    ],

    'xendit' => [
        'secret_key' => $_ENV['XENDIT_SECRET_KEY'] ?? null,
        'public_key' => $_ENV['XENDIT_PUBLIC_KEY'] ?? null,
        'webhook_token' => $_ENV['XENDIT_WEBHOOK_TOKEN'] ?? null,
    ],

    'doku' => [
        'client_id'  => $_ENV['DOKU_CLIENT_ID'] ?? null,
        'secret_key' => $_ENV['DOKU_SECRET_KEY'] ?? null,
        'base_url'   => $_ENV['DOKU_BASE_URL'] ?? 'https://api-sandbox.doku.com',
    ],

    'faspay' => [
        'merchant_id' => $_ENV['FASPAY_MERCHANT_ID'] ?? null,
        'merchant_key' => $_ENV['FASPAY_MERCHANT_KEY'] ?? null,
        'user_id'     => $_ENV['FASPAY_USER_ID'] ?? null,
        'password'    => $_ENV['FASPAY_PASSWORD'] ?? null,
        'base_url'    => $_ENV['FASPAY_BASE_URL'] ?? 'https://debit-sandbox.faspay.co.id',
    ],

    'nicepay' => [
        'merchant_id'  => $_ENV['NICEPAY_MERCHANT_ID'] ?? null,
        'merchant_key' => $_ENV['NICEPAY_MERCHANT_KEY'] ?? null,
        'base_url'     => $_ENV['NICEPAY_BASE_URL'] ?? 'https://dev.nicepay.co.id',
    ],

    'ipay88' => [
        'merchant_code' => $_ENV['IPAY88_MERCHANT_CODE'] ?? null,
        'merchant_key'  => $_ENV['IPAY88_MERCHANT_KEY'] ?? null,
        'base_url'      => $_ENV['IPAY88_BASE_URL'] ?? 'https://sandbox.ipay88.co.id',
    ],

    'ipaymu' => [
        'va'      => $_ENV['IPAYMU_VA'] ?? null,
        'api_key' => $_ENV['IPAYMU_API_KEY'] ?? null,
        'base_url' => $_ENV['IPAYMU_BASE_URL'] ?? 'https://sandbox.ipaymu.com/api/v2',
    ],

    'oy' => [
        'api_key'    => $_ENV['OY_API_KEY'] ?? null,
        'username'   => $_ENV['OY_USERNAME'] ?? null,
        'base_url'   => $_ENV['OY_BASE_URL'] ?? 'https://api-stg.oyindonesia.com',
    ],

    'dana' => [
        'client_id'     => $_ENV['DANA_CLIENT_ID'] ?? null,
        'client_secret' => $_ENV['DANA_CLIENT_SECRET'] ?? null,
        'public_key'    => $_ENV['DANA_PUBLIC_KEY'] ?? null,
        'base_url'      => $_ENV['DANA_BASE_URL'] ?? 'https://api-sandbox.saas.dana.id',
    ],

    'espay' => [
        'merchant_id'  => $_ENV['ESPAY_MERCHANT_ID'] ?? null,
        'api_key'      => $_ENV['ESPAY_API_KEY'] ?? null,
        'signature_key' => $_ENV['ESPAY_SIGNATURE_KEY'] ?? null,
        'base_url'     => $_ENV['ESPAY_BASE_URL'] ?? 'https://sandbox-kit.espay.id',
    ],

    // ═══════════════════════════════════════════════════
    //  🌍 INTERNASIONAL (Global)
    // ═══════════════════════════════════════════════════

    'stripe' => [
        'secret_key'     => $_ENV['STRIPE_SECRET_KEY'] ?? null,
        'publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? null,
        'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null,
    ],

    'paypal' => [
        'client_id'     => $_ENV['PAYPAL_CLIENT_ID'] ?? null,
        'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? null,
        'mode'          => $_ENV['PAYPAL_MODE'] ?? 'sandbox', // sandbox | live
    ],

    'adyen' => [
        'api_key'          => $_ENV['ADYEN_API_KEY'] ?? null,
        'merchant_account' => $_ENV['ADYEN_MERCHANT_ACCOUNT'] ?? null,
        'environment'      => $_ENV['ADYEN_ENVIRONMENT'] ?? 'test', // test | live
        'live_prefix'      => $_ENV['ADYEN_LIVE_PREFIX'] ?? null,
    ],

    'square' => [
        'access_token' => $_ENV['SQUARE_ACCESS_TOKEN'] ?? null,
        'location_id'  => $_ENV['SQUARE_LOCATION_ID'] ?? null,
        'environment'  => $_ENV['SQUARE_ENVIRONMENT'] ?? 'sandbox', // sandbox | production
    ],

    'authorizenet' => [
        'api_login_id'    => $_ENV['AUTHNET_API_LOGIN_ID'] ?? null,
        'transaction_key' => $_ENV['AUTHNET_TRANSACTION_KEY'] ?? null,
        'sandbox'         => filter_var($_ENV['AUTHNET_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],

    'braintree' => [
        'merchant_id'  => $_ENV['BRAINTREE_MERCHANT_ID'] ?? null,
        'public_key'   => $_ENV['BRAINTREE_PUBLIC_KEY'] ?? null,
        'private_key'  => $_ENV['BRAINTREE_PRIVATE_KEY'] ?? null,
        'environment'  => $_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox', // sandbox | production
    ],

    'checkoutcom' => [
        'secret_key' => $_ENV['CHECKOUTCOM_SECRET_KEY'] ?? null,
        'public_key' => $_ENV['CHECKOUTCOM_PUBLIC_KEY'] ?? null,
        'environment' => $_ENV['CHECKOUTCOM_ENVIRONMENT'] ?? 'sandbox',
    ],

    'worldpay' => [
        'service_key' => $_ENV['WORLDPAY_SERVICE_KEY'] ?? null,
        'client_key'  => $_ENV['WORLDPAY_CLIENT_KEY'] ?? null,
        'base_url'    => $_ENV['WORLDPAY_BASE_URL'] ?? 'https://try.access.worldpay.com',
    ],

    'airwallex' => [
        'api_key'   => $_ENV['AIRWALLEX_API_KEY'] ?? null,
        'client_id' => $_ENV['AIRWALLEX_CLIENT_ID'] ?? null,
        'base_url'  => $_ENV['AIRWALLEX_BASE_URL'] ?? 'https://api-demo.airwallex.com/api/v1',
    ],

    'helcim' => [
        'api_token'  => $_ENV['HELCIM_API_TOKEN'] ?? null,
        'account_id' => $_ENV['HELCIM_ACCOUNT_ID'] ?? null,
        'base_url'   => $_ENV['HELCIM_BASE_URL'] ?? 'https://api.helcim.com/v2',
    ],
];
