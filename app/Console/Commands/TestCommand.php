<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class TestCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'test';
    }

    public function getDescription(): string
    {
        return 'Jalankan unit & feature testing dengan tampilan cantik.';
    }

    public function run(array $args): void
    {
        echo "\n";
        echo "\033[1;36m   THE FRAMEWORK \033[0m\033[90m Testing Runner\033[0m\n";
        echo "\n";

        // Pastikan kita berada di root project
        $root = dirname(__DIR__, 3);

        // Command PHPUnit native dengan opsi untuk cleaning output
        // Kita pakai passthru agar warnanya tetap keluar dari PHPUnit
        // Opsi --testdox untuk checklist style
        // Opsi --colors=always agar warna tetap ada meski lewat exec
        $cmd = "vendor\\bin\\phpunit --testdox --colors=always";

        // Jika ada argumen filter (misal: php artisan test --filter=User)
        if (!empty($args)) {
            $cmd .= " " . implode(" ", $args);
        }

        passthru($cmd . " 2>&1", $returnVar);

        echo "\n";
        if ($returnVar === 0) {
            echo "\033[42;30m PASS \033[0m \033[32mAll tests passed successfully.\033[0m\n";
        } else {
            echo "\033[41;37m FAIL \033[0m \033[31mSome tests failed. Check output above.\033[0m\n";
        }
        echo "\n";
    }
}
