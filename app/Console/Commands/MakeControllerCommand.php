<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeControllerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:controller';
    }

    public function getDescription(): string
    {
        return 'Buat controller baru menggunakan sistem Stub Premium';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $name = $this->ask("Masukkan nama controller (Contoh: Admin/UserController)");
        }

        $isResource = in_array('--resource', $args) || in_array('-r', $args);
        
        $parts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $subNamespace = !empty($parts) ? "\\" . implode('\\', $parts) : '';
        $viewPath = strtolower(implode('.', $parts));
        $routePath = strtolower(implode('/', $parts));

        $targetDir = BASE_PATH . "/app/Http/Controllers" . (empty($parts) ? "" : "/" . implode('/', $parts));
        $targetFile = $targetDir . "/$className.php";

        if (file_exists($targetFile)) {
            $this->error("Controller $className sudah ada!");
            return;
        }

        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $stubName = $isResource ? 'controller.resource.stub' : 'controller.stub';
        $stubPath = BASE_PATH . "/app/Console/Stubs/$stubName";
        
        if (!file_exists($stubPath)) {
            $this->error("Stub file tidak ditemukan: $stubName");
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace([
            '{{namespace}}', 
            '{{class}}', 
            '{{view_path}}', 
            '{{route_path}}'
        ], [
            "TheFramework\\Http\\Controllers$subNamespace", 
            $className,
            $viewPath ?: 'index',
            $routePath ?: strtolower($className)
        ], $content);

        file_put_contents($targetFile, $content);
        $this->success("Controller $className berhasil dibuat di " . str_replace(BASE_PATH, '', $targetFile));
    }
}
