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
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama service (contoh: ProductService)\033[0m\n";
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
            echo "\033[38;5;124m✖ ERROR  Service sudah ada: $className\033[0m\n";
            exit(1);
        }

        $namespace = "TheFramework\\Services" . ($subNamespace ? "\\$subNamespace" : '');

        $content = <<<PHP
<?php

namespace $namespace;

class $className
{
    /**
     * Constructor untuk Dependency Injection
     * Contoh: public function __construct(protected ProductRepository \$repository) {}
     */
    public function __construct()
    {
        // 
    }

    /**
     * Contoh method logika bisnis
     */
    public function execute()
    {
        // Masukkan logika bisnis Anda di sini
        return true;
    }
}
PHP;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Service dibuat: $className (app/Services/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
