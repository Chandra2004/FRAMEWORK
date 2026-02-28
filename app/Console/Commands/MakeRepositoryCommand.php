<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeRepositoryCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:repository';
    }

    public function getDescription(): string
    {
        return 'Membuat kelas repository baru untuk abstraksi query';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "\n  \033[1;41;97m ERROR \033[0m Harap masukkan nama repository (contoh: ProductRepository)\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        // Ensure the name ends with Repository for consistency
        if (!str_ends_with($className, 'Repository')) {
            $className .= 'Repository';
        }

        $path = BASE_PATH . "/app/Repositories/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";

        if (file_exists($path)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Repository sudah ada: $className\n";
            exit(1);
        }

        $namespace = "TheFramework\\Repositories" . ($subNamespace ? "\\$subNamespace" : '');

        $stubPath = BASE_PATH . '/app/Console/Stubs/repository.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/repository.stub\n";
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
        echo "\n  \033[1;42;30m SUCCESS \033[0m Repository dibuat: $className (app/Repositories/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\n";
    }
}
