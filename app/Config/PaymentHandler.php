<?php

namespace TheFramework\Config;

use TheFramework\App\Config;

/**
 * Simple Wrapper untuk Midtrans Payment Gateway (Snap API)
 * 
 * 📦 REQUIREMENT:
 * composer require midtrans/midtrans-php
 * 
 * ⚙️ .ENV CONFIGURATION:
 * MIDTRANS_SERVER_KEY=SB-Mid-server-...
 * MIDTRANS_CLIENT_KEY=SB-Mid-client-...
 * MIDTRANS_IS_PRODUCTION=false
 */
class PaymentHandler
{
    private static bool $configured = false;

    /**
     * Configure Midtrans (hanya sekali)
     */
    private static function configure()
    {
        if (self::$configured)
            return;

        Config::loadEnv();
        \Midtrans\Config::$serverKey = Config::get('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = filter_var(Config::get('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        self::$configured = true;
    }

    /**
     * Buat Transaksi Snap Token
     * 
     * @param string $orderId ID Order unik (misal: "ORD-123")
     * @param int $grossAmount Total bayar (tanpa desimal untuk IDR)
     * @param array $customerData ['first_name', 'email', 'phone']
     * @param array $items (Opsional) Detail item [['id', 'price', 'quantity', 'name']]
     * @return string Snap Token
     */
    public static function createSnapToken(string $orderId, int $grossAmount, array $customerData = [], array $items = []): string
    {
        self::configure();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $customerData,
        ];

        if (!empty($items)) {
            $params['item_details'] = $items;
        }

        try {
            return \Midtrans\Snap::getSnapToken($params);
        } catch (\Exception $e) {
            throw new \Exception("Midtrans Error: " . $e->getMessage());
        }
    }

    /**
     * Verifikasi Notification Webhook dari Midtrans
     * Mengembalikan status order jika valid
     */
    public static function handleNotification(): object
    {
        self::configure();
        try {
            $notif = new \Midtrans\Notification();
            return $notif;
        } catch (\Exception $e) {
            throw new \Exception("Midtrans Notification Error: " . $e->getMessage());
        }
    }
}
