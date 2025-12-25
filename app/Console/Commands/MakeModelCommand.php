<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeModelCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:model';
    }
    public function getDescription(): string
    {
        return 'Membuat kelas model baru';
    }

    public function run(array $args): void
    {
        // Parse arguments
        $name = null;
        $createMigration = false;

        foreach ($args as $arg) {
            if ($arg === '-m' || $arg === '--migration') {
                $createMigration = true;
            } elseif (strpos($arg, '-') !== 0) {
                $name = $arg; // Ambil argumen non-flag sebagai nama model
            }
        }

        if (!$name) {
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama model\033[0m\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        $path = BASE_PATH . "/app/Models/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";
        if (file_exists($path)) {
            echo "\033[38;5;124m✖ ERROR  Model sudah ada: $className\033[0m\n";
            // Jangan exit jika cuma model yg ada, mungkin user mau buat migrasi saja?
            // Tapi standardnya error. Kita exit saja.
            exit(1);
        }

        $namespace = "TheFramework\\Models" . ($subNamespace ? "\\$subNamespace" : '');
        // Pluralize sederhana: User -> users, Category -> categories
        $lowerName = strtolower($className);
        if (substr($lowerName, -1) === 'y') {
            $tableName = substr($lowerName, 0, -1) . 'ies';
        } else if (substr($lowerName, -1) !== 's') {
            $tableName = $lowerName . 's';
        } else {
            $tableName = $lowerName;
        }

        $content = <<<PHP
<?php

namespace $namespace;

use TheFramework\App\Model;

class $className extends Model
{
    protected \$table = '$tableName';
    protected \$primaryKey = 'id';

    protected \$fillable = [
        // 'name', 'email', ...
    ];
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Model dibuat: $className (app/Models/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";

        // Buat migrasi jika diminta
        if ($createMigration) {
            $migrationName = "Create{$className}sTable"; // CreateUsersTable
            // Panggil MakeMigrationCommand secara manual
            // Kita perlu require file jika belum di-autoload (biasanya sudah via composer/artisan logic)
            // Asumsi class sudah ter-load
            if (class_exists(MakeMigrationCommand::class)) {
                $migrator = new MakeMigrationCommand();
                echo "\n\033[90m> Generating migration...\033[0m\n";
                $migrator->run([$migrationName]);
            } else {
                echo "\033[33mWarning: MakeMigrationCommand not found.\033[0m\n";
            }
        }
    }
}
