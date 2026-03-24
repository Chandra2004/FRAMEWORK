<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeComponentCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:component';
    }

    public function getDescription(): string
    {
        return 'Buat komponen TFWire baru beserta view-nya.';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $name = $this->ask("Masukkan nama komponen (Contoh: Admin/DashCard)");
        }

        // --- Processing Names & Paths ---
        $parts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $subNamespace = !empty($parts) ? "\\" . implode('\\', $parts) : '';
        
        // View Path: component.admin.dash-card
        $viewDotPath = 'component.' . strtolower(implode('.', array_merge($parts, [$className])));
        $viewFilePath = str_replace('.', '/', $viewDotPath) . '.blade.php';

        $classTargetDir = BASE_PATH . "/app/Components" . (empty($parts) ? "" : "/" . implode('/', $parts));
        $classTargetFile = $classTargetDir . "/$className.php";

        $viewTargetDir = dirname(BASE_PATH . "/resources/views/" . $viewFilePath);
        $viewTargetFile = BASE_PATH . "/resources/views/" . $viewFilePath;

        // --- Validation ---
        if (file_exists($classTargetFile)) {
            $this->error("Komponen $className sudah ada!");
            return;
        }

        // Create directories
        if (!is_dir($classTargetDir)) mkdir($classTargetDir, 0755, true);
        if (!is_dir($viewTargetDir)) mkdir($viewTargetDir, 0755, true);

        // --- 1. Create Class File ---
        $classStubPath = BASE_PATH . "/app/Console/Stubs/component.stub";
        if (!file_exists($classStubPath)) {
            $this->error("Stub class komponen tidak ditemukan!");
            return;
        }

        $classContent = file_get_contents($classStubPath);
        $classContent = str_replace([
            '{{namespace}}', 
            '{{class}}', 
            '{{view_path}}'
        ], [
            "TheFramework\\Components$subNamespace", 
            $className,
            $viewDotPath
        ], $classContent);

        file_put_contents($classTargetFile, $classContent);

        // --- 2. Create View File ---
        $viewStubPath = BASE_PATH . "/app/Console/Stubs/component.view.stub";
        if (!file_exists($viewStubPath)) {
            $this->error("Stub view komponen tidak ditemukan!");
            return;
        }

        $viewContent = file_get_contents($viewStubPath);
        $viewContent = str_replace([
            '{{id}}'
        ], [
            'tf-' . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className))
        ], $viewContent);

        file_put_contents($viewTargetFile, $viewContent);

        // --- Result ---
        $this->success("Komponen TFWire '$className' berhasil dibuat!");
        $this->line("   🚀 Class: " . str_replace(BASE_PATH, '', $classTargetFile), self::COLOR_GRAY);
        $this->line("   🎨 View:  " . str_replace(BASE_PATH, '', $viewTargetFile), self::COLOR_GRAY);
        $this->line("");
        $this->comment("Gunakan {!! tfwire('{$className}') !!} di view Anda.");
    }
}
