<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeViewCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:db-view';
    }

    public function getDescription(): string
    {
        return 'Membuat file migrasi untuk Database View';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? 'new_view';
        $timestamp = date('Y_m_d_His');
        // Class name sanitization
        $className = "Migration_{$timestamp}_" . strtr(ucwords(strtr($name, ['_' => ' ', '-' => ' '])), [' ' => '']);

        $filename = "{$timestamp}_{$name}.php";
        $path = BASE_PATH . "/database/migrations/{$filename}";

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);

        // Try to guess view name
        $viewName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(['Create', 'View'], '', $name)));
        $viewName = trim($viewName, '_') ?: 'view_name';

        $content = <<<PHP
<?php

namespace Database\Migrations;

use TheFramework\App\Schema;
use TheFramework\Models\User; // Contoh Model

class {$className} {
    public function up()
    {
        // OPSI 1: Menggunakan Query Builder (Recommended)
        // \$query = User::where('active', 1)->select(['id', 'name']);
        // Schema::createView('{$viewName}', \$query);

        // OPSI 2: Menggunakan Raw SQL
        \$sql = "SELECT id, name FROM users WHERE active = 1";
        Schema::createView('{$viewName}', \$sql);
    }

    public function down()
    {
        Schema::dropView('{$viewName}');
    }
}
PHP;

        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  View Migration dibuat: $filename (database/migrations/$filename)\033[0m\n";
    }
}
