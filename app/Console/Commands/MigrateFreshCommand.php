<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MigrateFreshCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'migrate:fresh';
    }
    public function getDescription(): string
    {
        return 'Menghapus semua tabel dan menjalankan ulang migrasi';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;214m⚠ WARNING  Ini akan menghapus semua tabel. Lanjutkan? [y/N] \033[0m";
        $input = trim(fgets(STDIN));
        if (strtolower($input) !== 'y') {
            echo "\033[38;5;124m✖ ERROR  Operasi dibatalkan\033[0m\n";
            exit(1);
        }

        echo "\033[38;5;39m➤ INFO  Menghapus semua tabel";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";

        $migrator = new \TheFramework\App\Migrator();
        $migrator->dropAllTables();

        $migrateCommand = new MigrateCommand();
        $migrateCommand->run($args);

        echo "\033[38;5;28m★ SUCCESS  Semua tabel dihapus dan migrasi dijalankan ulang\033[0m\n";
    }
}
