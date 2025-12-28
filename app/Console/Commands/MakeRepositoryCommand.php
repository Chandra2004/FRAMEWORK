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
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama repository (contoh: ProductRepository)\033[0m\n";
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
            echo "\033[38;5;124m✖ ERROR  Repository sudah ada: $className\033[0m\n";
            exit(1);
        }

        $namespace = "TheFramework\\Repositories" . ($subNamespace ? "\\$subNamespace" : '');

        $content = <<<PHP
<?php

namespace $namespace;

use TheFramework\App\Database;

class $className
{
    protected \$db;

    public function __construct()
    {
        \$this->db = Database::getInstance();
    }

    /**
     * Contoh method untuk mengambil semua data
     */
    public function all()
    {
        \$this->db->query("SELECT * FROM table_name");
        return \$this->db->resultSet();
    }

    /**
     * Contoh method untuk mengambil data berdasarkan ID
     */
    public function find(\$id)
    {
        \$this->db->query("SELECT * FROM table_name WHERE id = ?");
        \$this->db->bind(1, \$id);
        return \$this->db->single();
    }
}
PHP;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Repository dibuat: $className (app/Repositories/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
