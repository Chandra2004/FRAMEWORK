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

use TheFramework\\App\\FormRequest;

/**
 * Request validation untuk $className
 * 
 * Auto-validates when used in controller!
 * No need to call validate() manually.
 * 
 * Usage:
 * public function store($className \$request) {
 *     // Validation already done automatically
 *     // If we reach here, validation passed
 *     Model::create(\$request->validated());
 * }
 */
class $className extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Return false to deny access (403 Forbidden).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Available rules: required, email, min, max, unique, exists,
     * alpha, numeric, confirmed, in, between, mimes, image, etc.
     * 
     * @return array
     */
    public function rules(): array
    {
        return [
            // Example: 'email' => 'required|email|unique:users,email',
            // Example: 'password' => 'required|min:8|confirmed',
            // Example: 'age' => 'nullable|numeric|between:13,120',
        ];
    }

    /**
     * Get custom labels for validation error messages.
     * Makes error messages more user-friendly in your language.
     * 
     * @return array
     */
    public function labels(): array
    {
        return [
            // Example: 'email' => 'Alamat Email',
            // Example: 'password' => 'Kata Sandi',
        ];
    }
}
PHP;

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);
        echo "\033[38;5;28m★ SUCCESS  Request dibuat: $className (app/Http/Requests/" . ($folderPath ? $folderPath . '/' : '') . "$className.php)\033[0m\n";
    }
}
