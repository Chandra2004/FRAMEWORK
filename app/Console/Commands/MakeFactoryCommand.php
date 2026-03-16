<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

/**
 * MakeFactoryCommand — Generate file Factory baru.
 *
 * Usage:
 *   php artisan make:factory UserFactory
 *   php artisan make:factory UserFactory --model=User
 *   php artisan make:factory Store/ProductFactory --model=Store/Product
 */
class MakeFactoryCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:factory';
    }

    public function getDescription(): string
    {
        return 'Buat file factory baru untuk generate dummy data model';
    }

    private function snake(string $value): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(?<!^)[A-Z]/u', '_$0', $value));
        }
        return $value;
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $name = $this->ask("Masukkan nama factory (Contoh: UserFactory)");
        }

        if (!$name) {
            $this->error("Nama factory harus diberikan!");
            echo "  Contoh: php artisan make:factory UserFactory" . PHP_EOL;
            return;
        }

        // Parse --model=xxx flag
        $modelName = null;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--model=')) {
                $modelName = substr($arg, 8);
            }
        }

        // Normalize name — pastikan berakhiran "Factory"
        $name = str_replace('\\', '/', $name);
        if (!str_ends_with($name, 'Factory')) {
            $name .= 'Factory';
        }

        // Parse subdirectory
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subDir = !empty($parts) ? '/' . implode('/', $parts) : '';
        $subNamespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        // Resolve model name dari nama factory jika tidak di-specify
        if (!$modelName) {
            $modelName = str_replace('Factory', '', $className);
            if (!empty($parts)) {
                $modelName = implode('/', $parts) . '/' . $modelName;
            }
        }

        // Parse model namespace
        $modelParts = explode('/', str_replace('\\', '/', $modelName));
        $modelClassName = array_pop($modelParts);
        $modelSubNamespace = !empty($modelParts) ? '\\' . implode('\\', $modelParts) : '';
        $modelFQCN = "TheFramework\\Models{$modelSubNamespace}\\{$modelClassName}";

        // Target file path
        $targetDir = BASE_PATH . "/database/factories" . $subDir;
        $targetFile = $targetDir . "/{$className}.php";

        // Check if already exists
        if (file_exists($targetFile)) {
            $this->error("Factory {$className} sudah ada!");
            $this->line("  File: " . str_replace(BASE_PATH, '', $targetFile));
            return;
        }

        // Create directory
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Load stub
        $stubPath = BASE_PATH . '/app/Console/Stubs/factory.stub';
        if (!file_exists($stubPath)) {
            $this->error("Stub tidak ditemukan di app/Console/Stubs/factory.stub");
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{model}}', '{{modelImport}}'],
            [
                "Database\\Factories{$subNamespace}",
                $className,
                $modelClassName,
                $modelFQCN,
            ],
            $content
        );

        file_put_contents($targetFile, $content);

        $this->success("Factory berhasil dibuat: {$className}");
        $this->line("  📁 " . str_replace(BASE_PATH, '', $targetFile));
        $this->line("");
        $this->comment("Jangan lupa tambahkan trait HasFactory di model {$modelClassName}:");
        $this->line("  use \\TheFramework\\App\\Database\\Traits\\HasFactory;", self::COLOR_CYAN);
        $this->line("");
        $this->comment("Usage:");
        $this->line("  {$modelClassName}::factory()->create();           // 1 data + save ke DB", self::COLOR_CYAN);
        $this->line("  {$modelClassName}::factory()->count(10)->create(); // 10 data + save ke DB", self::COLOR_CYAN);
        $this->line("  {$modelClassName}::factory()->make();              // 1 data tanpa save", self::COLOR_CYAN);
    }
}
