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
        $content = <<<PHP
<?php

namespace {$namespace};

use PHPUnit\Framework\TestCase;

class {$name} extends TestCase
{
    /**
     * Setup sebelum setiap test dijalankan.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Contoh test method.
     */
    public function test_example(): void
    {
        \$this->assertTrue(true);
    }
}

PHP;

        file_put_contents($filePath, $content);

        $this->success("Test dibuat: tests/{$subDir}/{$name}.php");
        $this->comment("Jalankan dengan: php artisan test");
    }
}
