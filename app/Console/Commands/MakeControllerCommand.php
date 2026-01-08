<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeControllerCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:controller';
    }

    public function getDescription(): string
    {
        return 'Membuat kelas controller baru';
    }

    public function run(array $args): void
    {
        $name = null;
        $isResource = false;
        $modelName = null;

        foreach ($args as $arg) {
            if ($arg === '-r' || $arg === '--resource') {
                $isResource = true;
            } elseif (strpos($arg, '--model=') === 0) {
                $modelName = substr($arg, 8);
            } elseif (strpos($arg, '-') !== 0) {
                $name = $arg;
            }
        }

        if (!$name) {
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama controller\033[0m\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        $path = BASE_PATH . "/app/Http/Controllers/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";
        if (file_exists($path)) {
            echo "\033[38;5;124m✖ ERROR  Controller sudah ada: $className\033[0m\n";
            exit(1);
        }

        $namespace = "TheFramework\\Http\\Controllers" . ($subNamespace ? "\\$subNamespace" : '');

        // UPGRADE: Tambahkan import penting
        $useStatements = "use TheFramework\Http\Controllers\Controller;\nuse TheFramework\App\Request;\nuse TheFramework\App\View;\nuse TheFramework\Helpers\Helper;\nuse Exception;";
        if ($modelName) {
            $useStatements .= "\nuse TheFramework\\Models\\$modelName;";
        }

        // Template Content
        if ($isResource) {
            $modelClass = $modelName ?? 'Model';
            $varName = strtolower($modelName ?? 'id');

            $methods = <<<PHP
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // \$items = $modelClass::all();
        return View::render('$folderPath.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return View::render('$folderPath.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request)
    {
        // \$data = \$request->validated();
        
        // $modelClass::create(\$data);
        
        Helper::flash('notification', ['type' => 'success', 'message' => 'Data berhasil disimpan']);
        return redirect('/$folderPath');
    }

    /**
     * Display the specified resource.
     */
    public function show(\$$varName)
    {
        // \$item = $modelClass::findOrFail(\$$varName);
        return View::render('$folderPath.show', ['item' => \$$varName]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\$$varName)
    {
        // \$item = $modelClass::findOrFail(\$$varName);
        return View::render('$folderPath.edit', ['item' => \$$varName]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, \$$varName)
    {
        // \$data = \$request->validated();
        // \$item = $modelClass::findOrFail(\$$varName);
        // \$item->update(\$data);
        
        Helper::flash('notification', ['type' => 'success', 'message' => 'Data berhasil diupdate']);
        return redirect('/$folderPath');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\$$varName)
    {
        // \$item = $modelClass::findOrFail(\$$varName);
        // \$item->delete();
        
        Helper::flash('notification', ['type' => 'success', 'message' => 'Data berhasil dihapus']);
        return redirect('/$folderPath');
    }
PHP;
        } else {
            $methods = <<<PHP
    public function index()
    {
        \$notification = Helper::get_flash('notification');
        
        return View::render('welcome', [
            'notification' => \$notification,
            'title' => 'The Framework'
        ]);
    }
PHP;
        }

        $content = <<<PHP
<?php

namespace $namespace;

$useStatements

class $className extends Controller
{
$methods
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Controller dibuat: $className (app/Http/Controllers/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
