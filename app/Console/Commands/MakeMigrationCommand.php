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

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) $name = $this->ask("Masukkan nama migrasi (Contoh: create_users_table)");

        $tableName = 'table_name';
        if (preg_match('/^create_(.*)_table$/', $name, $matches)) {
            $tableName = $matches[1];
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $targetFile = BASE_PATH . "/database/migrations/$fileName";

        $stubPath = BASE_PATH . "/app/Console/Stubs/migration.stub";
        $content = file_get_contents($stubPath);
        $content = str_replace('{{table}}', $tableName, $content);

        if (!is_dir(dirname($targetFile))) mkdir(dirname($targetFile), 0755, true);

        file_put_contents($targetFile, $content);
        $this->success("Migrasi dibuat: $fileName");
    }
}
