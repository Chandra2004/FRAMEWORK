<?php

namespace TheFramework\App\Internal\Controllers;

use TheFramework\App\Core\Config;

/**
 * FileController - Framework Internal
 * Menangani penyajian file dari folder private secara aman.
 */
class FileController
{
    public static function getAllowedFolders(): array
    {
        return ['shared', 'user-pictures', 'dummy', 'docs'];
    }

    public function Serve($params = [])
    {
        $allowedFolders = self::getAllowedFolders();
        $forbiddenExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'sql'];

        $requested = '';

        if (is_array($params) && isset($params[0])) {
            $requested = $params[0];
        } elseif (is_string($params) && $params !== '') {
            $requested = $params;
        }

        if ($requested === '') {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $uri = parse_url($uri, PHP_URL_PATH);
            $requested = preg_replace('#^/file#', '', $uri);
        }

        $requested = '/' . ltrim($requested, '/');

        $privateDir = ROOT_DIR . '/private-uploads';
        $filePath = realpath($privateDir . $requested);

        // proteksi: pastikan file tetap di dalam private-uploads
        if ($filePath === false || strpos($filePath, realpath($privateDir)) !== 0) {
            $this->abort(404, "File tidak ditemukan.");
        }

        // whitelist folder
        $relativePath = ltrim($requested, '/');
        $parts = explode('/', $relativePath);
        $folder = $parts[0] ?? '';

        if (!in_array($folder, $allowedFolders)) {
            $this->abort(403, "Folder tidak diizinkan.");
        }

        // blacklist ekstensix
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, $forbiddenExtensions)) {
            $this->abort(403, "Ekstensi file tidak diizinkan.");
        }

        // cek file ada
        if (file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            header("Content-Type: " . $mime);
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $this->abort(404, "File tidak ditemukan.");
        }
    }

    private function abort(int $code, string $message): void
    {
        if (strtolower(Config::get('APP_ENV')) === 'production') {
            if ($code === 404) ErrorController::error404();
            else ErrorController::error403();
        } else {
            http_response_code($code);
            echo $message;
        }
        exit;
    }
}
