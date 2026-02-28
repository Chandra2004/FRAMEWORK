<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeMigrationCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:migration';
    }

    public function getDescription(): string
    {
        return 'Buat file migrasi database baru';
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

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name)
            $name = $this->ask("Masukkan nama migrasi (Contoh: create_users_table)");

        // Format nama menjadi snake_case jika pengguna menginput PascalCase / CamelCase (misal: CreateCategoryTable)
        if (!str_contains($name, '_')) {
            $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        } else {
            $name = strtolower($name);
        }

        $tableName = 'table_name';
        if (preg_match('/^create_(.*)_table$/', $name, $matches)) {
            $extracted = $matches[1];
            $tableName = $this->pluralize($extracted); // Pastikan selalu plural
            $name = "create_{$tableName}_table"; // Sinkronisasikan nama file
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $targetFile = BASE_PATH . "/database/migrations/$fileName";

        $stubPath = BASE_PATH . "/app/Console/Stubs/migration.stub";
        $content = file_get_contents($stubPath);
        $content = str_replace('{{table}}', $tableName, $content);

        if (!is_dir(dirname($targetFile)))
            mkdir(dirname($targetFile), 0755, true);

        file_put_contents($targetFile, $content);
        $this->success("Migrasi dibuat: $fileName");
    }
}
