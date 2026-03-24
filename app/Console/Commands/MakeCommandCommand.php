<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeCommandCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:command';
    }

    public function getDescription(): string
    {
        return 'Buat perintah Artisan (Command) baru.';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $name = $this->ask("Masukkan nama class perintah (Contoh: MyNewCommand)");
        }

        $commandKey = strtolower(preg_replace('/(?<!^)([A-Z])/', ':$1', $name));
        $targetFile = BASE_PATH . "/app/Console/Commands/{$name}.php";

        if (file_exists($targetFile)) {
            $this->error("Perintah $name sudah ada!");
            return;
        }

        $stubPath = BASE_PATH . "/app/Console/Stubs/command.stub";
        if (!file_exists($stubPath)) {
            $this->error("Stub perintah tidak ditemukan!");
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace([
            '{{class}}', 
            '{{name}}', 
            '{{description}}'
        ], [
            $name,
            $commandKey,
            "Deskripsi untuk perintah {$commandKey}"
        ], $content);

        file_put_contents($targetFile, $content);
        $this->success("Perintah Artisan '$name' berhasil dibuat di " . str_replace(BASE_PATH, '', $targetFile));
        $this->comment("Coba jalankan: php artisan $commandKey");
    }
}
