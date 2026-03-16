<?php

namespace TheFramework\Handlers;

use Exception;

/**
 * 💳 PaymentHandler — Universal Multi-Driver Payment Gateway
 * 
 * Mendukung 20 Payment Gateway (10 🇮🇩 Nasional + 10 🌍 Internasional).
 * Developer cukup: composer require [sdk] → isi .env → langsung pakai.
 * 
 * USAGE:
 *   PaymentHandler::driver('midtrans')->createTransaction($payload);
 *   PaymentHandler::driver('stripe')->createTransaction($payload);
 *   PaymentHandler::driver('xendit')->checkStatus($orderId);
 * 
 * SUPPORTED DRIVERS:
 *   🇮🇩 midtrans, xendit, doku, faspay, nicepay, ipay88, ipaymu, oy, dana, espay
 *   🌍 stripe, paypal, adyen, square, authorizenet, braintree, checkoutcom, worldpay, airwallex, helcim
 */
class PaymentHandler
{
    protected static ?object $instance = null;
    protected array $config;
    protected ?object $driver = null;

    public function __construct(?string $driverName = null)
    {
        $configFile = defined('ROOT_DIR') ? ROOT_DIR . '/config/payment.php' : __DIR__ . '/../../config/payment.php';
        
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Config file 'config/payment.php' not found.");
        }

        $allConfigs = require $configFile;
        
        // Pilih driver (default ke configurasi 'default' di file config)
        $driverName = $driverName ?? ($allConfigs['default'] ?? 'midtrans');
        
        if (!isset($allConfigs[$driverName])) {
            throw new \RuntimeException("Configuration for driver '{$driverName}' not found.");
        }

        $this->config = $allConfigs[$driverName];
        $this->loadDriver($driverName);
    }

    /**
     * Factory untuk memuat class driver secara dinamis
     */
    protected function loadDriver(string $name)
    {
        $className = "TheFramework\Handlers\PaymentDrivers\\" . ucfirst($name) . "Driver";
        
        if (!class_exists($className)) {
            throw new \RuntimeException("Payment driver class '{$className}' not found.");
        }

        $this->driver = new $className($this->config);
    }

    /**
     * Static helper: PaymentHandler::driver('stripe')->createTransaction(...)
     */
    public static function driver(string $name): object
    {
        return new self($name);
    }

    /**
     * Proxy semua panggil ke driver yang dipilih
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->driver, $method)) {
            return call_user_func_array([$this->driver, $method], $arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on driver " . get_class($this->driver));
    }
}
