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
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama resource (mis. User)\033[0m\n";
            exit(1);
        }

        $base = $this->studly($name);
        $controllerClass = $base . 'Controller';
        $modelClass = $base . 'Model';
        $requestClass = $base . 'Request';
        $slug = $this->slug($base);
        $routePath = '/' . $slug;
        $viewDir = $this->basePath . '/resources/Views/' . $slug;

        $this->makeController($controllerClass, $modelClass, $requestClass, $slug, $routePath);
        $this->makeModel($modelClass, $slug);
        $this->makeService($base, $modelClass);
        $this->makeRequest($requestClass);
        $this->makeViews($viewDir, $slug, $base);
        $this->appendRoute($controllerClass, $routePath);

        echo "\033[38;5;28m★ SUCCESS  CRUD scaffolded untuk {$base}\033[0m\n";
        echo "  Controller : app/Http/Controllers/{$controllerClass}.php\n";
        echo "  Model      : app/Models/{$modelClass}.php\n";
        echo "  Request    : app/Http/Requests/{$requestClass}.php\n";
        echo "  Views      : resources/Views/{$slug}/\n";
        echo "  Route      : routes/web.php (Router::resource '{$routePath}')\n";
        echo "  Service    : app/Services/{$base}Service.php\n";
    }

    private function makeService(string $base, string $model): void
    {
        $serviceClass = $base . 'Service';
        $path = $this->basePath . "/app/Services/{$serviceClass}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Service sudah ada: {$serviceClass}\033[0m\n";
            return;
        }

        $modelVar = lcfirst($model);
        $varName = '$' . $modelVar;

        $content = "<?php\n\n";
        $content .= "namespace TheFramework\\Services;\n\n";
        $content .= "use TheFramework\\Models\\{$model};\n\n";
        $content .= "class {$serviceClass}\n";
        $content .= "{\n";
        $content .= "    protected {$varName};\n\n";
        $content .= "    public function __construct()\n";
        $content .= "    {\n";
        $content .= "        {$varName} = null;\n";
        $content .= "        \$this->{$modelVar} = new {$model}();\n";
        $content .= "    }\n\n";
        $content .= "    // Example methods - implement business logic here\n";
        $content .= "    public function getAll()\n";
        $content .= "    {\n";
        $content .= "        return \$this->{$modelVar}->GetAllUsers();\n";
        $content .= "    }\n\n";
        $content .= "    public function find(string \$id)\n";
        $content .= "    {\n";
        $content .= "        return \$this->{$modelVar}->InformationUser(\$id);\n";
        $content .= "    }\n\n";
        $content .= "    public function create(array \$data)\n";
        $content .= "    {\n";
        $content .= "        return \$this->{$modelVar}->CreateUser(\$data);\n";
        $content .= "    }\n\n";
        $content .= "    public function update(string \$id, array \$data)\n";
        $content .= "    {\n";
        $content .= "        return \$this->{$modelVar}->UpdateUser(\$data, \$id);\n";
        $content .= "    }\n\n";
        $content .= "    public function delete(string \$id)\n";
        $content .= "    {\n";
        $content .= "        return \$this->{$modelVar}->DeleteUser(\$id);\n";
        $content .= "    }\n";
        $content .= "}\n";

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Service dibuat: {$serviceClass}\033[0m\n";
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

        $content = <<<PHP
<?php

namespace TheFramework\\Http\\Controllers;

use TheFramework\\Http\\Controllers\\Traits\\BaseCrudTrait;
use TheFramework\\Models\\{$model};
use TheFramework\\Http\\Requests\\{$request};

class {$controller} extends Controller
{
    use BaseCrudTrait;

    private \${$this->lc($request)};
    private \${$this->lc($model)};

    public function __construct()
    {
        \$this->{$this->lc($request)} = new {$request}();
        \$this->{$this->lc($model)} = new {$model}();
    }

    protected function getModel()
    {
        return \$this->{$this->lc($model)};
    }

    protected function getRequest()
    {
        return \$this->{$this->lc($request)};
    }

    protected function getRoutePath(): string
    {
        return '{$routePath}';
    }

    protected function getViewPath(): string
    {
        return '{$slug}';
    }

    protected function getPrimaryKey(): string
    {
        return 'id'; // Ubah ke 'uid' jika menggunakan UUID
    }
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Controller dibuat: {$controller}\033[0m\n";
    }

    private function makeModel(string $model, string $slug): void
    {
        $path = $this->basePath . "/app/Models/{$model}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Model sudah ada: {$model}\033[0m\n";
            return;
        }

        $content = <<<PHP
<?php

namespace TheFramework\\Models;

use TheFramework\\App\\Model;

class {$model} extends Model
{
    protected \$table = '{$slug}s';
    protected \$primaryKey = 'id';
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Model dibuat: {$model}\033[0m\n";
    }

    private function makeRequest(string $request): void
    {
        $path = $this->basePath . "/app/Http/Requests/{$request}.php";
        if (file_exists($path)) {
            echo "\033[38;5;214mℹ SKIP   Request sudah ada: {$request}\033[0m\n";
            return;
        }

        $content = <<<PHP
<?php

namespace TheFramework\\Http\\Requests;

use TheFramework\\App\\Request;

class {$request} extends Request
{
    public function rules(): array
    {
        return [
            // 'name' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            // 'name.required' => 'Nama wajib diisi',
        ];
    }

    public function validated(): array
    {
        return \$this->validate(\$this->rules(), \$this->messages());
    }
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Request dibuat: {$request}\033[0m\n";
    }

    private function makeViews(string $dir, string $slug, string $base): void
    {
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        $index = <<<BLADE
@extends('template.layout')
@section('main-content')
<h1 class="text-2xl font-semibold mb-4">{{ \$title ?? 'List' }}</h1>
<a class="text-blue-500 underline" href="/{$slug}/create">Tambah {$base}</a>

@if(!empty(\${$this->lc($slug)}s))
    <table class="mt-4 border w-full text-left">
        <thead>
            <tr><th class="p-2">ID</th><th class="p-2">Data</th><th class="p-2">Aksi</th></tr>
        </thead>
        <tbody>
        @foreach(\${$this->lc($slug)}s as \$item)
            <tr class="border-t">
                <td class="p-2">{{ \$item['id'] ?? '-' }}</td>
                <td class="p-2"><code>{{ json_encode(\$item) }}</code></td>
                <td class="p-2 space-x-2">
                    <a class="text-blue-500" href="/{$slug}/{{ \$item['id'] ?? '' }}">Detail</a>
                    <a class="text-amber-500" href="/{$slug}/{{ \$item['id'] ?? '' }}/edit">Edit</a>
                    <form class="inline" action="/{$slug}/{{ \$item['id'] ?? '' }}/delete" method="POST" onsubmit="return confirm('Hapus data?')">
                        @csrf
                        <button type="submit" class="text-red-500">Hapus</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="mt-4 text-gray-500">Belum ada data.</p>
@endif
@endsection
BLADE;

        $create = <<<BLADE
@extends('template.layout')
@section('main-content')
<h1 class="text-2xl font-semibold mb-4">{{ \$title ?? 'Create' }}</h1>

@if(\$errors)
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach(\$errors as \$error)
                <li>{{ \$error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="/{$slug}" method="POST" class="space-y-3">
    @csrf
    <!-- TODO: Tambah field sesuai kebutuhan -->
    <!-- Contoh: <input type="text" name="name" value="{{ old('name') }}" class="border rounded px-3 py-2"> -->
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
</form>
@endsection
BLADE;

        $edit = <<<BLADE
@extends('template.layout')
@section('main-content')
<h1 class="text-2xl font-semibold mb-4">{{ \$title ?? 'Edit' }}</h1>

@if(\$errors)
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach(\$errors as \$error)
                <li>{{ \$error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="/{$slug}/{{ \$item['id'] ?? '' }}" method="POST" class="space-y-3">
    @csrf
    <!-- TODO: Prefill field dari \$item -->
    <!-- Contoh: <input type="text" name="name" value="{{ \$item['name'] ?? old('name') }}" class="border rounded px-3 py-2"> -->
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
</form>
@endsection
BLADE;

        $show = <<<BLADE
@extends('template.layout')
@section('main-content')
<h1 class="text-2xl font-semibold mb-4">{{ \$title ?? 'Detail' }}</h1>
<pre class="bg-gray-100 p-3 rounded">{{ print_r(\$item, true) }}</pre>
<a class="text-blue-500 underline" href="/{$slug}">Kembali</a>
@endsection
BLADE;

        file_put_contents($dir . '/index.blade.php', $index);
        file_put_contents($dir . '/create.blade.php', $create);
        file_put_contents($dir . '/edit.blade.php', $edit);
        file_put_contents($dir . '/show.blade.php', $show);
        echo "\033[38;5;28m★ SUCCESS  Views dibuat di resources/Views/{$slug}\033[0m\n";
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

        $resourceLine = "Router::resource('{$routePath}', {$controller}::class, ['middleware' => [\\TheFramework\\Middleware\\CsrfMiddleware::class]]);";
        if (strpos($content, $resourceLine) === false) {
            $content = rtrim($content) . "\n\n{$resourceLine}\n";
        }

        file_put_contents($routeFile, $content);
        echo "\033[38;5;28m★ SUCCESS  Route resource ditambahkan di routes/web.php\033[0m\n";
    }

    private function lc(string $value): string
    {
        return lcfirst($value);
    }
}

