<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class SeedCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:seed';
    }

    public function getDescription(): string
    {
        return 'Menjalankan seeder database (semua atau seeder tertentu menggunakan --NamaSeeder)';
    }

    public function handle(array $args): void
    {
        $this->info("Menjalankan seeder...");

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
                
                require_once $foundFile;
                $content = file_get_contents($foundFile);
                if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                    $className = 'Database\\Seeders\\' . $matches[1];
                } else {
                    $className = 'Database\\Seeders\\' . $fileName;
                }

                if (class_exists($className)) {
                    $seeder = new $className();
                    if (method_exists($seeder, 'run')) {
                        $this->info("Menjalankan seeder: {$fileName}");
                        $seeder->run();
                        $this->success("Seeder {$fileName} selesai");
                        return;
                    }
                }
            }

            $this->error("Seeder {$specificSeeder} tidak ditemukan atau tidak valid");
            return;
        }

        // Jalankan semua seeder berdasarkan urutan timestamp di nama file
        $seederFiles = glob($seedersPath . '/*Seeder.php');

        usort($seederFiles, function ($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        foreach ($seederFiles as $file) {
            $fileName = basename($file, '.php');

            $content = file_get_contents($file);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = 'Database\\Seeders\\' . $matches[1];
            } else {
                $className = 'Database\\Seeders\\' . $fileName;
            }

            $this->info("Menjalankan seeder: {$fileName}");

            require_once $file;

            if (class_exists($className)) {
                $seeder = new $className();
                if (method_exists($seeder, 'run')) {
                    try {
                        $seeder->run();
                        $this->success("Seeder {$fileName} selesai");
                    } catch (\Throwable $e) {
                        $this->error("Seeder {$fileName} gagal: {$e->getMessage()}");
                    }
                } else {
                    $this->warn("Seeder {$fileName} tidak memiliki method 'run'");
                }
            } else {
                $this->error("Class {$className} tidak ditemukan");
            }
        }

        $this->success("Semua seeder selesai");
    }
}
