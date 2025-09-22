<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeMigrationCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:migration';
    }
    public function getDescription(): string
    {
        return 'Membuat file migrasi baru';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? 'new_migration';
        $timestamp = date('Y_m_d_His');
        $className = "Migration_{$timestamp}_" . str_replace(' ', '_', ucwords(str_replace(['-', '_'], ' ', $name)));
        $filename = "{$timestamp}_{$name}.php";
        $path = BASE_PATH . "/database/migrations/{$filename}";
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

        // Ekstrak nama tabel berdasarkan logika lama
        $tableName = $this->getTableName($name);

        $content = <<<PHP
<?php

namespace Database\Migrations;

use TheFramework\App\Schema;

class {$className} {
    public function up()
    {
        Schema::create('{$tableName}', function (\$table) {
            \$table->increments('id');
            \$table->string('uid', 36)->unique();
            
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{$tableName}');
    }
}
PHP;
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Migrasi dibuat: $filename (database/migrations/$filename)\033[0m\n";
    }

    private function getTableName($name)
    {
        // Hapus 'Create' dan 'Table', lalu konversi ke lower case dengan garis bawah
        $baseName = str_replace(['Create', 'Table'], '', $name);
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));
        return trim($tableName, '_') ?: 'table';
    }
}
