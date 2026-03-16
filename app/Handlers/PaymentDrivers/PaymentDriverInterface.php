<?php

namespace TheFramework\Handlers\PaymentDrivers;

interface PaymentDriverInterface
{
    /**
     * Inisialisasi konfigurasi driver
     */
    public function __construct(array $config);

    /**
     * Membuat transaksi dan mendapatkan token atau redirect URL
     */
    public function createTransaction(array $payload): mixed;

    /**
     * Cek status transaksi ke server provider
     */
    public function checkStatus(string $orderId): object;

    /**
     * Verifikasi notifikasi webhook
     */
    public function handleWebhook(?array $postData = null): object;
}
