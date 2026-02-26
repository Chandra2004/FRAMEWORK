<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeProviderCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:provider';
    }

    public function getDescription(): string
    {
        return 'Buat service provider baru';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $name = $this->ask("Nama provider (contoh: PaymentServiceProvider)");
        }

        if (!$name) {
            $this->error("Nama provider wajib diisi.");
            return;
        }

        // Ensure name ends with ServiceProvider
        if (!str_ends_with($name, 'ServiceProvider') && !str_ends_with($name, 'Provider')) {
            $name .= 'ServiceProvider';
        }

        $dir = BASE_PATH . '/app/Providers';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = "{$dir}/{$name}.php";

        if (file_exists($filePath)) {
            $this->error("File sudah ada: app/Providers/{$name}.php");
            return;
        }

        $content = <<<PHP
<?php

namespace TheFramework\Providers;

use TheFramework\App\Core\Container;

class {$name}
{
    /**
     * Register services ke container.
     * Dipanggil saat bootstrap aplikasi.
     */
    public function register(): void
    {
        \$container = Container::getInstance();

        // Contoh: Bind service ke container
        // \$container->singleton(MyService::class, function () {
        //     return new MyService();
        // });
    }

    /**
     * Bootstrap services.
     * Dipanggil setelah semua provider ter-register.
     */
    public function boot(): void
    {
        //
    }
}

PHP;

        file_put_contents($filePath, $content);

        $this->success("Provider dibuat: app/Providers/{$name}.php");
        $this->comment("Daftarkan di bootstrap/app.php:");
        $this->line("  \$provider = new \\TheFramework\\Providers\\{$name}();", self::COLOR_CYAN);
        $this->line("  \$provider->register();", self::COLOR_CYAN);
        $this->line("  \$provider->boot();", self::COLOR_CYAN);
    }
}
