<?php
namespace TheFramework\Http\Controllers\Services;

use TheFramework\App\Config;

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
            // Fix: Parse URL to ignore query strings (e.g. ?t=123 for cache busting)
            $uri = parse_url($uri, PHP_URL_PATH);
            $requested = preg_replace('#^/file#', '', $uri);
        }

        $requested = '/' . ltrim($requested, '/');

        $privateDir = ROOT_DIR . '/private-uploads';
        $filePath = realpath($privateDir . $requested);

        // proteksi: pastikan file tetap di dalam private-uploads
        if ($filePath === false || strpos($filePath, realpath($privateDir)) !== 0) {
            if (strtolower(Config::get('APP_ENV')) === 'production') {
                ErrorController::error404();
                exit;
            } else {
                http_response_code(404);
                echo "File tidak ditemukan.";
                exit;
            }
        }

        // whitelist folder
        $relativePath = ltrim($requested, '/');
        $parts = explode('/', $relativePath);
        $folder = $parts[0] ?? '';

        if (!in_array($folder, $allowedFolders)) {
            if (strtolower(Config::get('APP_ENV')) === 'production') {
                ErrorController::error403();
                exit;
            } else {
                http_response_code(403);
                echo "Folder tidak diizinkan.";
                exit;
            }
        }

        // blacklist ekstensi
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, $forbiddenExtensions)) {
            if (strtolower(Config::get('APP_ENV')) === 'production') {
                ErrorController::error403();
                exit;
            } else {
                http_response_code(403);
                echo "Ekstensi file tidak diizinkan.";
                exit;
            }
        }

        // cek file ada
        if (file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            header("Content-Type: " . $mime);
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            if (strtolower(Config::get('APP_ENV')) === 'production') {
                ErrorController::error404();
                exit;
            } else {
                http_response_code(404);
                echo "File tidak ditemukan.";
                exit;
            }
        }
    }
}
