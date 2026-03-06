<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeCrudCommand implements CommandInterface
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
    }

    public function getName(): string
    {
        return 'make:crud';
    }

    public function getDescription(): string
    {
        return 'Generate controller+model+request+views CRUD dan tambahkan route resource';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "\n  \033[1;41;97m ERROR \033[0m Harap masukkan nama resource (mis. User)\n";
            exit(1);
        }

        $base = $this->studly($name);
        $controllerClass = $base . 'Controller';
        $modelClass = $base;
        $requestClass = $base . 'Request';
        $slug = $this->slug($base);
        $routePath = '/' . $slug;
        $viewDir = $this->basePath . '/resources/Views/' . $slug;

        $repositoryClass = $base . 'Repository';

        $this->makeController($controllerClass, $modelClass, $requestClass, $slug, $routePath);
        $this->makeModel($modelClass, $slug);
        $this->makeRepository($base, $modelClass);
        $this->makeService($base, $repositoryClass);
        $this->makeRequest($requestClass);
        $this->makeViews($viewDir, $slug, $base);
        $this->appendRoute($controllerClass, $routePath);

        echo "\n  \033[1;42;30m SUCCESS \033[0m CRUD scaffolded untuk {$base}\n";
        echo "  Controller : app/Http/Controllers/{$controllerClass}.php\n";
        echo "  Request    : app/Http/Requests/{$requestClass}.php\n";
        echo "  Service    : app/Services/{$base}Service.php\n";
        echo "  Repository : app/Repositories/{$repositoryClass}.php\n";
        echo "  Model      : app/Models/{$modelClass}.php\n";
        echo "  Views      : resources/Views/{$slug}/\n";
        echo "  Route      : routes/web.php (Router::resource '{$routePath}')\n";
    }

    private function makeRepository(string $base, string $model): void
    {
        $repositoryClass = $base . 'Repository';
        $path = $this->basePath . "/app/Repositories/{$repositoryClass}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Repository sudah ada: {$repositoryClass}\033[0m\n";
            return;
        }

        $stubPath = $this->basePath . '/app/Console/Stubs/repository.crud.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/repository.crud.stub\n";
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{model}}', '{{class}}'],
            [$model, $repositoryClass],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Repository dibuat: {$repositoryClass}\n";
    }

    private function makeService(string $base, string $repository): void
    {
        $serviceClass = $base . 'Service';
        $path = $this->basePath . "/app/Services/{$serviceClass}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Service sudah ada: {$serviceClass}\033[0m\n";
            return;
        }

        $stubPath = $this->basePath . '/app/Console/Stubs/service.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/service.stub\n";
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{repository}}', '{{class}}', '{{lc_repository}}'],
            [$repository, $serviceClass, $this->lc($repository)],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Service dibuat: {$serviceClass}\n";
    }

    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/(?<!^)[A-Z]/', '-$0', $value);
        return strtolower(str_replace('_', '-', $value));
    }

    private function makeController(string $controller, string $model, string $request, string $slug, string $routePath): void
    {
        $path = $this->basePath . "/app/Http/Controllers/{$controller}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Controller sudah ada: {$controller}\033[0m\n";
            return;
        }

        $stubPath = $this->basePath . '/app/Console/Stubs/controller.crud.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/controller.crud.stub\n";
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            [
                '{{model}}',
                '{{request}}',
                '{{class}}',
                '{{lc_request}}',
                '{{lc_model}}',
                '{{route_path}}',
                '{{slug}}',
                '{{primary_key}}'
            ],
            [
                $model,
                $request,
                $controller,
                $this->lc($request),
                $this->lc($model),
                $routePath,
                $slug,
                'id'
            ],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Controller dibuat: {$controller}\n";
    }

    private function pluralize(string $value): string
    {
        $lastChar = strtolower(substr($value, -1));
        if ($lastChar === 'y') {
            return substr($value, 0, -1) . 'ies';
        } elseif (in_array(substr(strtolower($value), -2), ['sh', 'ch']) || in_array($lastChar, ['s', 'x', 'z'])) {
            return $value . 'es';
        }
        return $value . 's';
    }

    private function makeModel(string $model, string $slug): void
    {
        $path = $this->basePath . "/app/Models/{$model}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Model sudah ada: {$model}\033[0m\n";
            return;
        }

        $stubPath = $this->basePath . '/app/Console/Stubs/model.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/model.stub\n";
            return;
        }

        $tableName = $this->pluralize($slug);

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{table}}'],
            ['TheFramework\\Models', $model, $tableName],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Model dibuat: {$model}\n";
    }

    private function makeRequest(string $request): void
    {
        $path = $this->basePath . "/app/Http/Requests/{$request}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Request sudah ada: {$request}\033[0m\n";
            return;
        }

        $stubPath = $this->basePath . '/app/Console/Stubs/request.form.stub';
        if (!file_exists($stubPath)) {
            echo "\n  \033[1;41;97m ERROR \033[0m Stub tidak ditemukan di app/Console/Stubs/request.form.stub\n";
            return;
        }

        $content = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            ['TheFramework\\Http\\Requests', $request],
            $content
        );

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Request dibuat: {$request}\n";
    }

    private function makeViews(string $dir, string $slug, string $base): void
    {
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        $stubsPath = $this->basePath . '/app/Console/Stubs/';

        $index = file_exists($stubsPath . 'view.index.stub') ? file_get_contents($stubsPath . 'view.index.stub') : '';
        $create = file_exists($stubsPath . 'view.create.stub') ? file_get_contents($stubsPath . 'view.create.stub') : '';
        $edit = file_exists($stubsPath . 'view.edit.stub') ? file_get_contents($stubsPath . 'view.edit.stub') : '';
        $show = file_exists($stubsPath . 'view.show.stub') ? file_get_contents($stubsPath . 'view.show.stub') : '';

        $replacements = [
            '{{slug}}' => $slug,
            '{{base}}' => $base,
            '{{lc_slug}}' => $this->lc($slug)
        ];

        $index = str_replace(array_keys($replacements), array_values($replacements), $index);
        $create = str_replace(array_keys($replacements), array_values($replacements), $create);
        $edit = str_replace(array_keys($replacements), array_values($replacements), $edit);
        $show = str_replace(array_keys($replacements), array_values($replacements), $show);

        file_put_contents($dir . '/index.blade.php', $index);
        file_put_contents($dir . '/create.blade.php', $create);
        file_put_contents($dir . '/edit.blade.php', $edit);
        file_put_contents($dir . '/show.blade.php', $show);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Views dibuat di resources/Views/{$slug}\n";
    }

    private function appendRoute(string $controller, string $routePath): void
    {
        $routeFile = $this->basePath . '/routes/web.php';
        if (!file_exists($routeFile)) {
            echo "\033[38;5;214mℹ INFO   routes/web.php tidak ditemukan; lewati penambahan route\033[0m\n";
            return;
        }

        $content = file_get_contents($routeFile);
        $useLine = "use TheFramework\\Http\\Controllers\\{$controller};";
        if (strpos($content, $useLine) === false) {
            $content = preg_replace('/(<\?php\s*\n)/', "$0$useLine\n", $content, 1);
        }

        $resourceLine = "Router::resource('{$routePath}', {$controller}::class, ['middleware' => [\\TheFramework\\Middleware\\CsrfMiddleware::class, \\TheFramework\\Middleware\\WAFMiddleware::class]]);";
        if (strpos($content, $resourceLine) === false) {
            $content = rtrim($content) . "\n\n{$resourceLine}\n";
        }

        file_put_contents($routeFile, $content);
        echo "\n  \033[1;42;30m SUCCESS \033[0m Route resource ditambahkan di routes/web.php\n";
    }

    private function lc(string $value): string
    {
        return lcfirst($value);
    }
}

