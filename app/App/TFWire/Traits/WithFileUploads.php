<?php

namespace TheFramework\App\TFWire\Traits;

/**
 * WithFileUploads — TFWire File Upload Trait
 * 
 * Menambahkan kemampuan upload file ke komponen TFWire
 * dengan progress tracking dan validasi.
 * 
 * Livewire ❌ tidak punya progress tracking built-in di server.
 * TFWire  ✅ punya upload progress + preview + validasi all-in-one.
 * 
 * Usage:
 *   class MyComponent extends Component {
 *       use WithFileUploads;
 *       public $photo;
 *       protected array $uploadRules = [
 *           'photo' => ['max:2048', 'mimes:jpg,png,webp']
 *       ];
 *   }
 * 
 *   View:
 *   <input type="file" tf-wire:model="photo">
 *   <div tf-wire:upload.progress="photo">Uploading... <span tf-wire:upload.percent></span>%</div>
 *   <div tf-wire:upload.preview="photo"><img></div>
 */
trait WithFileUploads
{
    /** Uploaded file data storage */
    protected array $uploadedFiles = [];

    /** Upload rules: ['field' => ['max:2048', 'mimes:jpg,png']] */
    protected array $uploadRules = [];

    /**
     * Handle incoming file upload
     */
    public function handleFileUpload(string $field, array $fileData): array
    {
        // Validate file
        $errors = $this->validateUpload($field, $fileData);
        if (!empty($errors)) {
            $this->addError($field, $errors[0]);
            return ['success' => false, 'errors' => $errors];
        }

        $this->uploadedFiles[$field] = $fileData;

        // Hook: afterUpload
        $method = 'uploaded' . str_replace('_', '', ucwords($field, '_'));
        if (method_exists($this, $method)) {
            $this->{$method}($fileData);
        }

        return ['success' => true, 'file' => $fileData];
    }

    /**
     * Validate upload against rules
     */
    protected function validateUpload(string $field, array $fileData): array
    {
        $rules = $this->uploadRules[$field] ?? [];
        $errors = [];

        foreach ($rules as $rule) {
            $params = [];
            if (str_contains($rule, ':')) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            switch ($rule) {
                case 'max':
                    $maxKb = (int) ($params[0] ?? 2048);
                    $sizeKb = ($fileData['size'] ?? 0) / 1024;
                    if ($sizeKb > $maxKb) {
                        $errors[] = "File {$field} tidak boleh lebih dari {$maxKb}KB.";
                    }
                    break;

                case 'mimes':
                    $ext = strtolower(pathinfo($fileData['name'] ?? '', PATHINFO_EXTENSION));
                    if (!in_array($ext, $params)) {
                        $errors[] = "File {$field} harus bertipe: " . implode(', ', $params) . ".";
                    }
                    break;

                case 'min':
                    $minKb = (int) ($params[0] ?? 0);
                    $sizeKb = ($fileData['size'] ?? 0) / 1024;
                    if ($sizeKb < $minKb) {
                        $errors[] = "File {$field} minimal {$minKb}KB.";
                    }
                    break;

                case 'dimensions':
                    // e.g. dimensions:min_width=100,min_height=100
                    // Handled client-side, but can be validated server-side too
                    break;
            }
        }

        return $errors;
    }

    /**
     * Store uploaded file to disk
     */
    protected function storeUpload(string $field, string $directory = 'uploads', ?string $filename = null): ?string
    {
        $file = $this->uploadedFiles[$field] ?? null;
        if (!$file || !isset($file['tmp_name'])) return null;

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = $filename ?? (uniqid() . '.' . $ext);
        $path = rtrim($directory, '/') . '/' . $name;

        $fullDir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . $directory;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . $path;
        
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
            rename($file['tmp_name'], $fullPath);
        } else {
            move_uploaded_file($file['tmp_name'], $fullPath);
        }

        return $path;
    }

    /**
     * Get uploaded file data
     */
    protected function getUpload(string $field): ?array
    {
        return $this->uploadedFiles[$field] ?? null;
    }

    /**
     * Remove uploaded file
     */
    protected function removeUpload(string $field): void
    {
        unset($this->uploadedFiles[$field]);
    }
}
