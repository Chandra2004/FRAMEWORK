<?php

namespace TheFramework\App;

use TheFramework\App\Validator;
use TheFramework\Helpers\Helper;

class Request
{
    protected array $input = [];
    protected array $files = [];
    protected bool $forceJson = false;

    public function __construct()
    {
        $this->input = array_merge($_GET ?? [], $_POST ?? []);

        // Parse JSON Input if Content-Type is application/json
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains(strtolower($contentType), 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                $this->input = array_merge($this->input, $json);
            }
        }

        foreach ($_FILES ?? [] as $key => $file) {
            if (is_array($file['name'])) {
                $files = [];
                foreach ($file['name'] as $i => $name) {
                    $files[] = [
                        'name' => $name,
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i],
                    ];
                }
                $this->files[$key] = $files;
            } else {
                $this->files[$key] = $file;
            }

            $this->input[$key] = $this->files[$key];
        }
    }

    /**
     * Aktifkan mode JSON paksa (untuk API)
     */
    public function forceJson(bool $status = true): static
    {
        $this->forceJson = $status;
        return $this;
    }

    /**
     * Ambil semua input + file
     */
    public function all(): array
    {
        return array_merge($this->input, $this->files);
    }

    /**
     * Ambil 1 input (GET/POST)
     */
    public function input(string $key, $default = null)
    {
        return $this->input[$key] ?? $default;
    }

    /**
     * Cek apakah input ada
     */
    public function has(string $key): bool
    {
        return isset($this->input[$key]);
    }

    /**
     * Cek apakah file diupload dan valid
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
            is_array($this->files[$key]) &&
            isset($this->files[$key]['error']) &&
            $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Ambil file yang diupload
     * - return null jika tidak ada
     * - return array jika ada
     */
    public function file(string $key): ?array
    {
        if ($this->hasFile($key)) {
            return $this->files[$key];
        }
        return null;
    }

    /**
     * Validasi seperti Laravel
     */
    public function validate(array $rules, array $labels = []): array
    {
        $validator = new Validator();
        $isValid = $validator->validate($this->input, $rules, $labels);

        if (!$isValid) {
            $errors = $validator->errors();
            $error = $validator->firstError();

            // Store validation errors in session untuk ditampilkan di view
            Helper::set_flash('validation_errors', $errors);
            Helper::set_flash('old_input', $this->input);

            $isJson = $this->forceJson ||
                (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

            if ($isJson) {
                Helper::json([
                    'status' => 'error',
                    'message' => $error,
                    'errors' => $errors,
                ], 422);
                exit;
            }

            $referer = parse_url($_SERVER['HTTP_REFERER'] ?? '/', PHP_URL_PATH);
            Helper::redirect($referer ?: '/', 'error', $error);
            exit;
        }

        $validated = [];
        foreach ($rules as $field => $_) {
            if (isset($this->input[$field])) {
                $validated[$field] = $this->input[$field];
            }
        }

        return $validated;
    }
}
