<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class MakeRequestCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:request';
    }

    public function getDescription(): string
    {
        return 'Membuat kelas request baru';
    }

    public function run(array $args): void
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "\033[38;5;124m✖ ERROR  Harap masukkan nama request\033[0m\n";
            exit(1);
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = implode('\\', $parts);
        $folderPath = implode('/', $parts);

        $path = BASE_PATH . "/app/Http/Requests/" . ($folderPath ? $folderPath . '/' : '') . "$className.php";
        if (file_exists($path)) {
            echo "\033[38;5;124m✖ ERROR  Request sudah ada: $className\033[0m\n";
            exit(1);
        }

        $namespace = "TheFramework\\Http\\Requests" . ($subNamespace ? "\\$subNamespace" : '');

        $content = <<<PHP
<?php

namespace $namespace;

use TheFramework\\App\\Request;

class $className extends Request
{
    /**
     * Aturan validasi untuk request ini.
     */
    public function rules(): array
    {
        return [
            // 'field_name' => 'required|min:3|max:100',
        ];
    }

    /**
     * Pesan error kustom untuk validasi.
     */
    public function messages(): array
    {
        return [
            // 'field_name.required' => 'Field ini wajib diisi.',
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
        echo "\033[38;5;28m★ SUCCESS  Request dibuat: $className (app/Http/Requests/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
