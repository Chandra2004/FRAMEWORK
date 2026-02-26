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
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama middleware\033[0m\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        $path = BASE_PATH . "/app/Middleware/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";
        if (file_exists($path)) {
            echo "\033[38;5;124m✖ ERROR  Middleware sudah ada: $className\033[0m\n";
            exit(1);
        }

        $namespace = "TheFramework\\Middleware" . ($subNamespace ? "\\$subNamespace" : '');

        $content = <<<PHP
<?php

namespace $namespace;

use TheFramework\Helpers\Helper;

class $className implements Middleware
{
    public function before()
    {
        // Logika middleware dieksekusi SEBELUM Controller
        
        // Contoh: Cek Session
        // \$user = Helper::session_get('user_id');
        // if (!\$user) {
        //      Helper::redirect('/login', 'error', 'Silakan login terlebih dahulu.');
        // }
    }

    public function after()
    {
        // Logika middleware dieksekusi SETELAH Controller (Optional)
    }
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Middleware dibuat: $className (app/Middleware/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
