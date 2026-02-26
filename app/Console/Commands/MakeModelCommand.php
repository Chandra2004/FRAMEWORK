<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeModelCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:model';
    }

    public function getDescription(): string
    {
        return 'Buat model baru dengan auto-pluralization & opsi migrasi';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $name = $this->ask("Masukkan nama model (Contoh: Store/Product)");
        }

        $createMigration = in_array('-m', $args) || in_array('--migration', $args);
        
        $parts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $subNamespace = !empty($parts) ? "\\" . implode('\\', $parts) : '';
        
        $targetDir = BASE_PATH . "/app/Models" . (empty($parts) ? "" : "/" . implode('/', $parts));
        $targetFile = $targetDir . "/$className.php";

        if (file_exists($targetFile)) {
            $this->error("Model $className sudah ada!");
            return;
        }

        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        // Simple Pluralization for table name
        $lowerName = strtolower($className);
        $tableName = $lowerName . 's';
        if (substr($lowerName, -1) === 'y') $tableName = substr($lowerName, 0, -1) . 'ies';

        $stubPath = BASE_PATH . "/app/Console/Stubs/model.stub";
        $content = file_get_contents($stubPath);
        $content = str_replace([
            '{{namespace}}', 
            '{{class}}', 
            '{{table}}'
        ], [
            "TheFramework\\Models$subNamespace", 
            $className,
            $tableName
        ], $content);

        file_put_contents($targetFile, $content);
        $this->success("Model $className dibuat di " . str_replace(BASE_PATH, '', $targetFile));

        if ($createMigration) {
            $migrationName = "create_{$tableName}_table";
            $this->comment("Memicu pembuatan migrasi...");
            // We use another command instance
            $makeMigration = new MakeMigrationCommand();
            $makeMigration->run([$migrationName]);
        }
    }
}
