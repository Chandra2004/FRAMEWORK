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
        $allowedExtensions = [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf',
            // Archives
            'zip', 'rar', '7z', 'gz', 'tar',
            // Web Assets (If needed for shared components)
            'css', 'js', 'json', 'map', 'xml'
        ];

        $requested = '';

        // 1. Coba ambil dari Query String (?path=...)
        if (isset($_GET['path'])) {
            $requested = $_GET['path'];
        }
        // 2. Coba ambil dari Route Params (/file/folder/filename)
        elseif (is_array($params) && !empty($params)) {
            // Join semua params jika ada multiple (misal: folder/subfolder/file)
            $requested = implode('/', $params);
        }
        // 3. Fallback: Parse dari URI (mencocokkan /file/...)
        if ($requested === '') {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $uri = parse_url($uri, PHP_URL_PATH);
            if (str_starts_with($uri, '/file')) {
                $requested = preg_replace('#^/file#', '', $uri);
            }
        }

        if ($requested === '') {
            $this->abort(400, "Bad Request: Path tidak ditentukan.");
        }

        // Normalisasi path
        $requested = '/' . ltrim($requested, '/');

        // Gunakan Config untuk direktori upload (mendukung kustomisasi .env)
        $uploadDir = Config::get('UPLOAD_DIR', '/private-uploads');
        $privateRoot = ROOT_DIR . $uploadDir;

        $filePath = realpath($privateRoot . $requested);

        // Security check: Pastikan file berada di dalam root upload yang diizinkan (Anti-Traversal)
        if ($filePath === false || !str_starts_with($filePath, realpath($privateRoot))) {
            $this->abort(404, "File tidak ditemukan.");
        }

        // Whitelist folder check
        $relativePath = ltrim($requested, '/');
        $parts = explode('/', $relativePath);
        $topFolder = $parts[0] ?? '';

        if (!in_array($topFolder, $allowedFolders)) {
            $this->abort(403, "Akses folder '{$topFolder}' tidak diizinkan.");
        }

        // Strict Whitelist Extension Check
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            $this->abort(403, "Tipe file '.{$ext}' tidak diizinkan untuk diakses secara publik.");
        }

        // Serve file dengan header yang tepat
        if (file_exists($filePath)) {
            // Bersihkan buffer untuk mencegah output korup
            if (ob_get_length())
                ob_end_clean();

            $mime = mime_content_type($filePath);

            // Fix MIME type untuk beberapa ekstensi populer yang sering salah deteksi
            $mimeMap = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'svg' => 'image/svg+xml',
            ];
            if (isset($mimeMap[$ext]))
                $mime = $mimeMap[$ext];

            header("Content-Type: " . $mime);
            header("Content-Length: " . filesize($filePath));
            header("Cache-Control: public, max-age=86400"); // Cache file selama 24 jam
            readfile($filePath);
            exit;
        } else {
            $this->abort(404, "File tidak ditemukan di disk.");
        }
    }

    private function abort(int $code, string $message): void
    {
        if (strtolower(Config::get('APP_ENV')) === 'production') {
            http_response_code($code);
            if ($code === 404)
                ErrorController::error404();
            elseif ($code === 403)
                ErrorController::error403();
            else
                echo "Error {$code}";
        } else {
            http_response_code($code);
            echo "<h3>File Error {$code}</h3><p>{$message}</p>";
        }
        exit;
    }
}
