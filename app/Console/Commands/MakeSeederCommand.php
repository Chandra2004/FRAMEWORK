<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeSeederCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:seeder';
    }

    public function getDescription(): string
    {
        return 'Membuat file seeder baru di database/seeders dengan format timestamp seperti migrasi';
    }

    private function pluralize(string $value): string
    {
        $lastChar = strtolower(substr($value, -1));

        // Asumsi sederhana jika sudah jamak biarkan saja
        if (str_ends_with($value, 'ies') || str_ends_with($value, 'es') || (strlen($value) > 1 && $lastChar === 's' && !str_ends_with($value, 'ss'))) {
            return $value;
        }

        if ($lastChar === 'y') {
            return substr($value, 0, -1) . 'ies';
        } elseif (in_array(substr(strtolower($value), -2), ['sh', 'ch']) || in_array($lastChar, ['s', 'x', 'z'])) {
            return $value . 'es';
        }
        return $value . 's';
    }

    public function run(array $args): void
    {
        echo "\n  \033[1;44;97m INFO \033[0m Memuat perintah";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";

        if (empty($args[0])) {
            echo "\033[31m[Error]\033[0m Nama seeder harus diberikan.\n";
            echo "Contoh: php artisan make:seeder RolesSeeder\n";
            return;
        }

        $baseName = str_ends_with($args[0], 'Seeder') ? $args[0] : $args[0] . 'Seeder';

        // Perubahan: Gunakan format Seeder_TIMESTAMP_Nama agar urut & PSR-4 compliant
        $timestamp = date('Y_m_d_His');
        $fileName = "Seeder_{$timestamp}_{$baseName}.php";
        $className = "Seeder_{$timestamp}_{$baseName}";

        $tableNameRaw = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Seeder', '', $baseName)));
        $tableName = $this->pluralize(trim($tableNameRaw, '_'));

        $seederPath = BASE_PATH . "/database/seeders/{$fileName}";

        if (!is_dir(dirname($seederPath))) {
            mkdir(dirname($seederPath), 0777, true);
        }

        if (file_exists($seederPath)) {
            echo "\033[33m[Warning]\033[0m Seeder '{$fileName}' sudah ada.\n";
            return;
        }

        $stubPath = BASE_PATH . '/app/Console/Stubs/seeder.stub';
        if (!file_exists($stubPath)) {
            echo "\033[31m[Error]\033[0m Stub tidak ditemukan di app/Console/Stubs/seeder.stub\n";
            return;
        }

        $template = file_get_contents($stubPath);
        $template = str_replace(
            ['{{class}}', '{{table}}'],
            [$className, $tableName],
            $template
        );

        if (file_put_contents($seederPath, $template) !== false) {
            echo "\n  \033[1;42;30m SUCCESS \033[0m Seeder dibuat: {$fileName} (database/seeders/{$fileName})\n";
        } else {
            echo "\033[31m[Error]\033[0m Gagal membuat seeder.\n";
        }
    }
}
