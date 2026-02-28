<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeServiceCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:service';
    }

    public function getDescription(): string
    {
        return 'Membuat kelas service baru untuk logika bisnis';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "\n  \033[1;41;97m ERROR \033[0m Harap masukkan nama service (contoh: ProductService)\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        // Ensure the name ends with Service for consistency
        if (!str_ends_with($className, 'Service')) {
            $className .= 'Service';
        }

        $path = BASE_PATH . "/app/Services/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";

        if (file_exists($path)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Service sudah ada: $className\n";
            exit(1);
        }

        $namespace = "TheFramework\\Services" . ($subNamespace ? "\\$subNamespace" : '');

        $stubPath = BASE_PATH . '/app/Console/Stubs/service.make.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/service.make.stub\n";
            exit(1);
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $content
        );

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Service dibuat: $className (app/Services/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\n";
    }
}
