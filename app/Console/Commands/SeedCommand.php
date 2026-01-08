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
        echo "\033[38;5;39m➤ INFO  Menjalankan seeder";
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
                        echo "\033[38;5;39m➤ INFO  Menjalankan seeder: {$fileName}\033[0m\n";
                        $seeder->run();
                        echo "\033[38;5;28m★ SUCCESS  Seeder {$fileName} selesai\033[0m\n";
                        return;
                    }
                }
            }

            echo "\033[38;5;196m✖ ERROR  Seeder {$specificSeeder} tidak ditemukan atau tidak valid\033[0m\n";
            return;
        }

        // Jalankan semua seeder berdasarkan urutan timestamp di nama file
        $seederFiles = glob($seedersPath . '/*Seeder.php');

        usort($seederFiles, function ($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        foreach ($seederFiles as $file) {
            $fileName = basename($file, '.php');
            $className = 'Database\\Seeders\\Seeder_' . $fileName;

            echo "\033[38;5;39m➤ INFO  Menjalankan seeder: {$fileName}\033[0m\n";

            require_once $file;

            if (class_exists($className)) {
                $seeder = new $className();
                if (method_exists($seeder, 'run')) {
                    try {
                        $seeder->run();
                        echo "\033[38;5;28m★ SUCCESS  Seeder {$fileName} selesai\033[0m\n";
                    } catch (\Throwable $e) {
                        echo "\033[38;5;196m✖ ERROR  Seeder {$fileName} gagal: {$e->getMessage()}\033[0m\n";
                    }
                } else {
                    echo "\033[38;5;214m⚠ WARNING  Seeder {$fileName} tidak memiliki method 'run'\033[0m\n";
                }
            } else {
                echo "\033[38;5;196m✖ ERROR  Class {$className} tidak ditemukan\033[0m\n";
            }
        }

        echo "\033[38;5;28m★ SUCCESS  Semua seeder selesai\033[0m\n";
    }
}

