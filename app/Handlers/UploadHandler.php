<?php

namespace TheFramework\Handlers;

use Exception;
use TheFramework\App\Core\Config;

/**
 * 📤 UploadHandler — Advanced File Upload Engine v5.0.1
 *
 * Mendukung:
 * - Upload files ke private-uploads/ (di luar public root = aman)
 * - Auto-konversi gambar ke WebP 
 * - Intelligent resizing
 * - Validasi keamanan (block PHP, exe, dll.)
 * - Static + Instance API
 *
 * @package TheFramework\Handlers
 * @version 5.0.1
 */
class UploadHandler
{
    protected array $config;

    public function __construct()
    {
        $configFile = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2)) . '/config/upload.php';
        $this->config = file_exists($configFile) ? require $configFile : [];
    }

    // =========================================================
    //  📁 STATIC API (untuk dipanggil dari Controller/Service)
    // =========================================================

    /**
     * Upload & konversi gambar ke WebP.
     * 
     * @param array  $file      $_FILES['input_name']
     * @param string $subDir    Sub-folder di private-uploads (contoh: '/user-pictures')
     * @param string $prefix    Prefix nama file (contoh: 'foto_')
     * @param int    $quality   Kualitas WebP (1-100)
     * @return string|array     Nama file jika sukses, array error jika gagal
     */
    public static function handleUploadToWebP(array $file, string $subDir = '', string $prefix = 'img_', int $quality = 0): string|array
    {
        try {
            $handler = new static();
            $result = $handler->save($file, $subDir, [
                'prefix' => $prefix,
                'convert_to' => 'webp',
                'quality' => $quality ?: ($handler->config['webp_quality'] ?? 80),
            ]);

            return $result['name'];
        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Upload file biasa (tanpa konversi).
     */
    public static function handleUpload(array $file, string $subDir = '', string $prefix = 'file_'): string|array
    {
        try {
            $handler = new static();
            $result = $handler->save($file, $subDir, ['prefix' => $prefix]);
            return $result['name'];
        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cek apakah hasil upload adalah error.
     */
    public static function isError(mixed $result): bool
    {
        return is_array($result) && isset($result['error']) && $result['error'] === true;
    }

    /**
     * Ambil pesan error dari hasil upload.
     */
    public static function getErrorMessage(mixed $result): string
    {
        if (is_array($result) && isset($result['message'])) {
            return $result['message'];
        }
        return 'Unknown upload error.';
    }

    /**
     * Hapus file dari private-uploads.
     */
    public static function delete(string $filename, string $subDir = ''): bool
    {
        $baseDir = static::getUploadBaseDir();
        $filePath = $baseDir . $subDir . '/' . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Cek apakah file ada.
     */
    public static function exists(string $filename, string $subDir = ''): bool
    {
        $baseDir = static::getUploadBaseDir();
        return file_exists($baseDir . $subDir . '/' . $filename);
    }

    /**
     * Dapatkan path lengkap file upload.
     */
    public static function path(string $filename, string $subDir = ''): string
    {
        return static::getUploadBaseDir() . $subDir . '/' . $filename;
    }

    /**
     * Dapatkan URL untuk serve file via route (karena private-uploads tidak bisa diakses langsung).
     */
    public static function url(string $filename, string $subDir = ''): string
    {
        $base = Config::get('BASE_URL', 'http://localhost:8080');
        // Gunakan format bersih yang didukung oleh bootstrap/app.php & FileController
        // Format: /file/folder/filename.ext
        return $base . '/file/' . ltrim($subDir, '/') . '/' . $filename;
    }

    // =========================================================
    //  📤 INSTANCE API (core upload logic)
    // =========================================================

    /**
     * Upload & Proses file.
     */
    public function save(array $file, string $subDir = '', array $options = []): array
    {
        $maxSize = $options['maxSize'] ?? ($this->config['max_size'] ?? 10 * 1024 * 1024);

        // 1. Validasi upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadError($file['error']));
        }

        // 2. Validasi ukuran
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            throw new Exception("Ukuran file melebihi batas maksimum ({$maxMB}MB).");
        }

        // 3. Validasi ekstensi (keamanan)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $blocked = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
            'exe', 'sh', 'bat', 'cmd', 'com', 'vbs', 'js', 'mjs', 'cjs',
            'htaccess', 'htpasswd', 'ini', 'log', 'sql',
            'svg',  // SVG bisa mengandung XSS (JavaScript)
            'swf',  // Flash (legera vector)
            'jar', 'war'  // Java executables
        ];
        if (in_array($ext, $blocked)) {
            throw new Exception("Tipe file '{$ext}' diblokir karena alasan keamanan.");
        }

        // 4. Tentukan direktori target
        $targetDir = static::getUploadBaseDir() . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 5. Generate nama file unik
        $prefix = $options['prefix'] ?? 'up_';
        $convertTo = $options['convert_to'] ?? null;
        $finalExt = $convertTo ?: $ext;
        $filename = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $finalExt;
        $fullPath = $targetDir . '/' . $filename;

        // 6. Proses gambar (jika format gambar)
        $imageExts = $this->config['allowed_categories']['images'] ?? ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $imageExts) && $convertTo === 'webp') {
            return $this->processImage($file['tmp_name'], $fullPath, $options);
        }

        // 7. Upload biasa
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return [
                'name' => $filename,
                'path' => $subDir . '/' . $filename,
                'full_path' => $fullPath,
                'size' => $file['size'],
                'extension' => $ext,
                'success' => true,
            ];
        }

        throw new Exception("Gagal menyimpan file upload.");
    }

    /**
     * Proses gambar: konversi ke WebP + resize.
     */
    protected function processImage(string $tmpFile, string $targetPath, array $options): array
    {
        if (!extension_loaded('gd')) {
            throw new Exception("Ekstensi PHP GD dibutuhkan untuk proses gambar.");
        }

        $info = @getimagesize($tmpFile);
        if (!$info) {
            throw new Exception("File bukan gambar yang valid.");
        }

        $mime = $info['mime'];

        $img = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($tmpFile),
            'image/png' => imagecreatefrompng($tmpFile),
            'image/webp' => imagecreatefromwebp($tmpFile),
            'image/gif' => imagecreatefromgif($tmpFile),
            default => throw new Exception("Format gambar tidak didukung: {$mime}"),
        };

        if (isset($options['width']) || isset($options['height'])) {
            $resized = $this->resize($img, $options['width'] ?? null, $options['height'] ?? null);
            $img = $resized;
        }

        // Simpan sebagai WebP
        $quality = $options['quality'] ?? ($this->config['webp_quality'] ?? 80);
        imagewebp($img, $targetPath, $quality);
        // imagedestroy($img); // DEPRECATED in PHP 8.5 (Auto-GC since 8.0)

        return [
            'name' => basename($targetPath),
            'path' => str_replace(static::getUploadBaseDir(), '', $targetPath),
            'full_path' => $targetPath,
            'type' => 'image/webp',
            'success' => true,
        ];
    }

    /**
     * Resize gambar proporsional.
     */
    protected function resize($img, ?int $w, ?int $h)
    {
        $oldW = imagesx($img);
        $oldH = imagesy($img);
        if (!$h)
            $h = (int) (($oldH / $oldW) * $w);
        if (!$w)
            $w = (int) (($oldW / $oldH) * $h);
        $new = imagecreatetruecolor($w, $h);
        imagealphablending($new, false);
        imagesavealpha($new, true);
        imagecopyresampled($new, $img, 0, 0, 0, 0, $w, $h, $oldW, $oldH);
        return $new;
    }

    /**
     * Translate PHP upload error code ke pesan.
     */
    protected function getUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload_max_filesize di php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas MAX_FILE_SIZE di form.',
            UPLOAD_ERR_PARTIAL => 'File hanya ter-upload sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang di-upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.',
            default => "Unknown upload error (code: {$code}).",
        };
    }

    // =========================================================
    //  🔧 HELPERS
    // =========================================================

    /**
     * Dapatkan base directory uploads.
     * Sesuai .env UPLOAD_DIR, default: private-uploads
     */
    protected static function getUploadBaseDir(): string
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2);
        $dir = Config::get('UPLOAD_DIR', '/private-uploads');

        // Jika dimulai dengan '/' berarti relatif dari ROOT_DIR
        if (str_starts_with($dir, '/')) {
            return $root . $dir;
        }
        return $root . '/' . $dir;
    }
}
