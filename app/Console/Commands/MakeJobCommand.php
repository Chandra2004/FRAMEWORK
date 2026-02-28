<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeJobCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:job';
    }

    public function getDescription(): string
    {
        return 'Membuat class Job baru untuk antrian.';
    }

    public function run(array $args): void
    {
        if (empty($args)) {
            echo "\033[31mError: Nama Job harus diisi.\033[0m\n";
            echo "Usage: php artisan make:job [NamaJob]\n";
            return;
        }

        $name = $args[0];
        $className = ucfirst($name); // Sesuaikan naming convention user

        // Buat folder app/Jobs jika belum ada
        $dir = BASE_PATH . '/app/Jobs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = "$dir/$className.php";

        if (file_exists($filePath)) {
            echo "\033[31mError: File Job '$className' sudah ada.\033[0m\n";
            return;
        }

        $stubPath = BASE_PATH . '/app/Console/Stubs/job.stub';
        if (!file_exists($stubPath)) {
            echo "\033[31mError: Stub tidak ditemukan di app/Console/Stubs/job.stub\033[0m\n";
            return;
        }

        $template = file_get_contents($stubPath);
        $template = str_replace('{{class}}', $className, $template);

        file_put_contents($filePath, $template);

        echo "\033[32mJob created successfully:\033[0m $filePath\n";
    }
}
