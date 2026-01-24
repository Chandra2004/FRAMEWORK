<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeSeederCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:seeder';
    }

    public function getDescription(): string
    {
        return 'Membuat file seeder baru di database/seeders dengan format timestamp seperti migrasi';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Memuat perintah";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";

        if (empty($args[0])) {
            echo "\033[31m[Error]\033[0m Nama seeder harus diberikan.\n";
            echo "Contoh: php artisan make:seeder RolesSeeder\n";
            return;
        }

        $baseName = str_ends_with($args[0], 'Seeder') ? $args[0] : $args[0] . 'Seeder';

        // Perubahan: Gunakan format Seeder_TIMESTAMP_Nama agar urut & PSR-4 compliant
        $timestamp = date('Y_m_d_His');
        $fileName = "Seeder_{$timestamp}_{$baseName}.php";
        $className = "Seeder_{$timestamp}_{$baseName}";

        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Seeder', '', $baseName)));
        $tableName = trim($tableName, '_');

        $seederPath = BASE_PATH . "/database/seeders/{$fileName}";

        if (!is_dir(dirname($seederPath))) {
            mkdir(dirname($seederPath), 0777, true);
        }

        if (file_exists($seederPath)) {
            echo "\033[33m[Warning]\033[0m Seeder '{$fileName}' sudah ada.\n";
            return;
        }

        $template = <<<PHP
<?php

namespace Database\Seeders;

use Faker\Factory;
use TheFramework\Database\Seeder;
use TheFramework\Helpers\Helper;

class {$className} extends Seeder {

    public function run() {
        \$faker = Factory::create();
        Seeder::setTable('{$tableName}');

        Seeder::create([
            [
                // Contoh data:
                // 'uid' => Helper::uuid(),
                // 'name' => \$faker->name,
            ]
        ]);
    }
}

PHP;

        if (file_put_contents($seederPath, $template) !== false) {
            echo "\033[38;5;28m★ SUCCESS  Seeder dibuat: {$fileName} (database/seeders/{$fileName})\033[0m\n";
        } else {
            echo "\033[31m[Error]\033[0m Gagal membuat seeder.\n";
        }
    }
}
