<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeMailCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:mail';
    }

    public function getDescription(): string
    {
        return 'Buat class Mail baru untuk pengiriman email';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $name = $this->ask("Nama mail class (contoh: WelcomeMail)");
        }

        if (!$name) {
            $this->error("Nama mail wajib diisi.");
            return;
        }

        if (!str_ends_with($name, 'Mail')) {
            $name .= 'Mail';
        }

        $dir = BASE_PATH . '/app/Mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = "{$dir}/{$name}.php";

        if (file_exists($filePath)) {
            $this->error("File sudah ada: app/Mail/{$name}.php");
            return;
        }

        $viewName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', str_replace('Mail', '', $name)));

        $stubPath = BASE_PATH . '/app/Console/Stubs/mail.stub';
        if (!file_exists($stubPath)) {
            $this->error("Stub tidak ditemukan di app/Console/Stubs/mail.stub");
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{class}}', '{{viewName}}'],
            [$name, $viewName],
            $content
        );

        file_put_contents($filePath, $content);

        $this->success("Mail dibuat: app/Mail/{$name}.php");
        $this->comment("Buat juga view: resources/views/emails/{$viewName}.blade.php");
    }
}
