<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeTestCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:test';
    }

    public function getDescription(): string
    {
        return 'Buat file test baru untuk PHPUnit';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $name = $this->ask("Nama test class (contoh: UserTest)");
        }

        if (!$name) {
            $this->error("Nama test wajib diisi.");
            return;
        }

        // Determine if unit or feature test
        $isUnit = in_array('--unit', $args);
        $subDir = $isUnit ? 'Unit' : 'Feature';
        $baseClass = $isUnit ? '\PHPUnit\Framework\TestCase' : '\PHPUnit\Framework\TestCase';

        // Ensure name ends with Test
        if (!str_ends_with($name, 'Test')) {
            $name .= 'Test';
        }

        $dir = BASE_PATH . "/tests/{$subDir}";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = "{$dir}/{$name}.php";

        if (file_exists($filePath)) {
            $this->error("File sudah ada: tests/{$subDir}/{$name}.php");
            return;
        }

        $namespace = "Tests\\{$subDir}";
        $stubPath = BASE_PATH . '/app/Console/Stubs/test.stub';
        if (!file_exists($stubPath)) {
            $this->error("Stub tidak ditemukan di app/Console/Stubs/test.stub");
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $name],
            $content
        );

        file_put_contents($filePath, $content);

        $this->success("Test dibuat: tests/{$subDir}/{$name}.php");
        $this->comment("Jalankan dengan: php artisan test");
    }
}
