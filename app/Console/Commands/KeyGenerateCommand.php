<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class KeyGenerateCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'key:generate';
    }

    public function getDescription(): string
    {
        return 'Generate kunci APP_KEY baru secara acak';
    }

    public function handle(array $args): void
    {
        $key = 'base64:' . base64_encode(random_bytes(32));

        $envFile = BASE_PATH . '/.env';

        if (!file_exists($envFile)) {
            $this->error("File .env tidak ditemukan. Jalankan 'php artisan setup' terlebih dahulu.");
            return;
        }

        $content = file_get_contents($envFile);

        if (preg_match('/^APP_KEY=.*/m', $content)) {
            // Check if key already exists
            $currentKey = '';
            preg_match('/^APP_KEY=(.*)$/m', $content, $matches);
            $currentKey = trim($matches[1] ?? '');

            if (!empty($currentKey) && !in_array('--force', $args)) {
                $this->warn("APP_KEY sudah terisi. Gunakan --force untuk overwrite.");
                $this->comment("Key saat ini: " . substr($currentKey, 0, 20) . "...");

                if (!$this->confirm("Generate key baru? (data terenkripsi lama akan RUSAK)", false)) {
                    $this->comment("Operasi dibatalkan.");
                    return;
                }
            }

            $content = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $content);
        } else {
            $content .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envFile, $content);

        $this->success("APP_KEY baru berhasil di-generate.");
        $this->info("Key: " . self::STYLE_BOLD . $key);
    }
}
