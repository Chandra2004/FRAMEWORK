<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeMiddlewareCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:middleware';
    }
    public function getDescription(): string
    {
        return 'Membuat kelas middleware baru';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "\n  \033[1;41;97m ERROR \033[0m Harap masukkan nama middleware\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        $path = BASE_PATH . "/app/Middleware/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";
        if (file_exists($path)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Middleware sudah ada: $className\n";
            exit(1);
        }

        $namespace = "TheFramework\\Middleware" . ($subNamespace ? "\\$subNamespace" : '');

        $stubPath = BASE_PATH . '/app/Console/Stubs/middleware.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/middleware.stub\n";
            exit(1);
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Middleware dibuat: $className (app/Middleware/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\n";
    }
}
