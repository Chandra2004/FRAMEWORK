<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class SeedCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'db:seed';
    }

    public function getDescription(): string
    {
        return 'Menjalankan seeder database (semua atau seeder tertentu menggunakan --NamaSeeder)';
    }

    public function run(array $args): void
    {
        echo "\n  \033[1;44;97m INFO \033[0m Menjalankan seeder";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";

        $seedersPath = BASE_PATH . '/database/seeders';

        // Cek apakah user ingin menjalankan seeder tertentu
        $specificSeeder = null;
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                $specificSeeder = substr($arg, 2);
                break;
            }
        }

        if ($specificSeeder) {
            // Cari file yang berakhiran dengan string yang diminta
            // Misal: user ketik --UsersSeeder, kita cari *UsersSeeder.php
            $foundFile = null;
            $files = glob($seedersPath . '/*.php');

            foreach ($files as $file) {
                if (str_ends_with(basename($file, '.php'), $specificSeeder)) {
                    $foundFile = $file;
                    break;
                }
            }

            if ($foundFile) {
                $fileName = basename($foundFile, '.php');
                $className = 'Database\\Seeders\\Seeder_' . $fileName;

                require_once $foundFile;
                if (class_exists($className)) {
                    $seeder = new $className();
                    if (method_exists($seeder, 'run')) {
                        echo "\n  \033[1;44;97m INFO \033[0m Menjalankan seeder: {$fileName}\n";
                        $seeder->run();
                        echo "\n  \033[1;42;30m SUCCESS \033[0m Seeder {$fileName} selesai\n";
                        return;
                    }
                }
            }

            echo "\n  \033[1;41;97m ERROR \033[0m Seeder {$specificSeeder} tidak ditemukan atau tidak valid\n";
            return;
        }

        // Jalankan semua seeder berdasarkan urutan timestamp di nama file
        $seederFiles = glob($seedersPath . '/*Seeder.php');

        usort($seederFiles, function ($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        foreach ($seederFiles as $file) {
            $fileName = basename($file, '.php');

            // Baca isi file untuk cari nama class
            $content = file_get_contents($file);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = 'Database\\Seeders\\' . $matches[1];
            } else {
                // Fallback jika regex gagal
                $className = 'Database\\Seeders\\' . $fileName;
            }

            echo "\n  \033[1;44;97m INFO \033[0m Menjalankan seeder: {$fileName}\n";

            require_once $file;

            if (class_exists($className)) {
                $seeder = new $className();
                if (method_exists($seeder, 'run')) {
                    try {
                        $seeder->run();
                        echo "\n  \033[1;42;30m SUCCESS \033[0m Seeder {$fileName} selesai\n";
                    } catch (\Throwable $e) {
                        echo "\n  \033[1;41;97m ERROR \033[0m Seeder {$fileName} gagal: {$e->getMessage()}\n";
                    }
                } else {
                    echo "\n  \033[1;43;30m WARN \033[0m Seeder {$fileName} tidak memiliki method 'run'\n";
                }
            } else {
                echo "\n  \033[1;41;97m ERROR \033[0m Class {$className} tidak ditemukan\n";
            }
        }

        echo "\n  \033[1;42;30m SUCCESS \033[0m Semua seeder selesai\n";
    }
}

