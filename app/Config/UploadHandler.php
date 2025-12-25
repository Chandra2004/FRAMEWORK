<?php

namespace TheFramework\Config;

use Exception;

/**
 * UploadHandler - Universal File Upload Handler
 * 
 * Kompatibel dengan PHP 7.4+ sampai PHP 8.5+
 * Support semua jenis file dengan validasi yang fleksibel
 * 
 * @author Chandra Tri Antomo
 */
class UploadHandler
{
    // Default allowed file types
    private const DEFAULT_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    private const DEFAULT_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
    private const DEFAULT_ARCHIVE_TYPES = ['zip', 'rar', '7z', 'tar', 'gz'];
    private const DEFAULT_VIDEO_TYPES = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    private const DEFAULT_AUDIO_TYPES = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

    // MIME type mapping untuk validasi ekstra
    private const MIME_TYPES = [
        // Images
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'svg' => ['image/svg+xml'],
        // Documents
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'application/csv'],
        // Archives
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed'],
        // Videos
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        // Audio
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'wav' => ['audio/wav', 'audio/wave'],
    ];

    /**
     * Upload file dengan validasi dan processing
     * 
     * @param array $file File dari $_FILES
     * @param array $options Konfigurasi upload
     *   - uploadDir: string (default: '/default')
     *   - prefix: string (default: 'file_')
     *   - allowedTypes: array (default: semua jenis)
     *   - maxSize: int (default: 10MB dalam bytes)
     *   - convertTo: string|null (untuk gambar: 'webp', 'jpg', 'png', null)
     *   - resize: array|null ['width' => int, 'height' => int, 'quality' => int]
     *   - validateMime: bool (default: true)
     * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null, 'path' => string|null]
     * @throws Exception
     */
    public static function upload(array $file, array $options = []): array
    {
        // Default options
        $uploadDir = $options['uploadDir'] ?? '/default';
        $prefix = $options['prefix'] ?? 'file';
        $allowedTypes = $options['allowedTypes'] ?? null; // null = semua jenis
        $maxSize = $options['maxSize'] ?? (10 * 1024 * 1024); // 10MB default
        $convertTo = $options['convertTo'] ?? null;
        $resize = $options['resize'] ?? null;
        $validateMime = $options['validateMime'] ?? true;

        try {
            // Validasi dasar
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return self::error('File tidak valid atau tidak diupload dengan benar.');
            }

            // Check upload error
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return self::error(self::getUploadErrorMessage($file['error']));
            }

            // Check file size
            if ($file['size'] > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                return self::error("Ukuran file melebihi batas maksimal ({$maxSizeMB}MB).");
            }

            // Get file extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (empty($ext)) {
                return self::error('File tidak memiliki ekstensi.');
            }

            // Validasi tipe file
            if ($allowedTypes !== null && !in_array($ext, $allowedTypes, true)) {
                return self::error("Tipe file .{$ext} tidak diizinkan.");
            }

            // Validasi MIME type (jika diaktifkan)
            if ($validateMime && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($file['tmp_name']);
                if (!self::isValidMimeType($ext, $mimeType)) {
                    return self::error("MIME type tidak valid untuk file .{$ext}.");
                }
            }

            // Siapkan direktori
            $directory = rtrim(ROOT_DIR . '/private-uploads' . $uploadDir, '/') . '/';
            if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
                return self::error('Gagal membuat direktori upload.');
            }

            $isImage = self::isImageType($ext);
            $targetExt = ($convertTo && $isImage) ? strtolower($convertTo) : $ext;

            // Prepare filename in format: prefix_originalName_randomuid.ext
            // - replace spaces in original filename with '-'
            // - sanitize both prefix and name parts to remove unsafe characters
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $sanitizedOriginal = self::sanitizeNamePart($originalName);
            $sanitizedPrefix = self::sanitizeNamePart((string) $prefix);

            $fileName = ($sanitizedPrefix !== '' ? $sanitizedPrefix . '_' : '')
                . $sanitizedOriginal
                . '_' . uniqid('', true) . '.' . $targetExt;

            $targetPath = $directory . $fileName;

            // Handle non-image files (langsung upload)
            if (!$isImage) {
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    return self::error('Gagal mengupload file.');
                }
                return self::success($fileName, $targetPath);
            }

            // Handle SVG (tidak perlu processing)
            if ($ext === 'svg' || $targetExt === 'svg') {
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    return self::error('Gagal mengupload file SVG.');
                }
                return self::success($fileName, $targetPath);
            }

            // Handle image processing (resize/convert)
            $result = self::processImage($file['tmp_name'], $targetPath, $ext, $targetExt, $resize);
            if (!$result['success']) {
                return self::error($result['error']);
            }

            return self::success($fileName, $targetPath);
        } catch (Exception $e) {
            return self::error($e->getMessage());
        }
    }

    /**
     * Process image (resize/convert) - Kompatibel semua versi PHP
     */
    private static function processImage(
        string $sourcePath,
        string $targetPath,
        string $sourceExt,
        string $targetExt,
        ?array $resize
    ): array {
        // Check GD extension
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD extension tidak tersedia untuk processing gambar.'];
        }

        // Load source image (kompatibel PHP 7.4+)
        $source = null;
        switch ($sourceExt) {
            case 'jpg':
            case 'jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($sourcePath);
                }
                break;
            case 'gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
        }

        if (!$source) {
            return ['success' => false, 'error' => 'Gagal membaca gambar sumber.'];
        }

        // Calculate dimensions
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);

        if ($resize && (isset($resize['width']) || isset($resize['height']))) {
            $width = $resize['width'] ?? 0;
            $height = $resize['height'] ?? 0;
            $quality = $resize['quality'] ?? 80;

            [$newWidth, $newHeight] = self::calculateSize($origWidth, $origHeight, $width, $height);

            // Create resized image
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if (!$resized) {
                self::destroyImage($source);
                return ['success' => false, 'error' => 'Gagal membuat gambar hasil resize.'];
            }

            // Preserve transparency
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            // Resize
            imagecopyresampled(
                $resized,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $origWidth,
                $origHeight
            );

            self::destroyImage($source);
            $source = $resized;
            $origWidth = $newWidth;
            $origHeight = $newHeight;
        } else {
            $quality = 80;
        }

        // Save image (kompatibel PHP 7.4+)
        $saved = false;
        switch ($targetExt) {
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($source, $targetPath, $quality);
                }
                break;
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($source, $targetPath, $quality);
                break;
            case 'png':
                $saved = imagepng($source, $targetPath);
                break;
            case 'gif':
                $saved = imagegif($source, $targetPath);
                break;
        }

        self::destroyImage($source);

        if (!$saved) {
            return ['success' => false, 'error' => 'Gagal menyimpan gambar hasil processing.'];
        }

        return ['success' => true];
    }

    /**
     * Calculate resize dimensions dengan aspect ratio
     */
    private static function calculateSize(int $origWidth, int $origHeight, int $width, int $height): array
    {
        if ($width <= 0 && $height <= 0) {
            return [$origWidth, $origHeight];
        }

        if ($width > 0 && $height > 0) {
            return [$width, $height];
        }

        $aspect = $origHeight / $origWidth;
        $newWidth = $width > 0 ? $width : (int) ($height / $aspect);
        $newHeight = $height > 0 ? $height : (int) ($newWidth * $aspect);

        return [$newWidth, $newHeight];
    }

    /**
     * Sanitize a name part (prefix or filename) for safe use in generated filenames.
     * - Replace whitespace with '-'
     * - Remove characters except letters, numbers, underscore and hyphen
     * - Trim leading/trailing separators and limit length
     */
    private static function sanitizeNamePart(string $name): string
    {
        $name = preg_replace('/\s+/', '-', $name);
        $name = preg_replace('/[^\p{L}\p{N}_\-]/u', '', $name);
        $name = trim($name, '-_');
        if ($name === '') {
            return 'file';
        }
        if (mb_strlen($name) > 80) {
            $name = mb_substr($name, 0, 80);
        }
        return $name;
    }

    /**
     * Destroy image resource - Kompatibel PHP 7.4+ sampai 8.5+
     */
    private static function destroyImage($image): void
    {
        if (is_resource($image) || (is_object($image) && $image instanceof \GdImage)) {
            // PHP 8.0+ menggunakan GdImage object, imagedestroy() masih berfungsi
            // PHP 8.5+ imagedestroy() deprecated tapi masih bisa digunakan
            if (function_exists('imagedestroy')) {
                @imagedestroy($image);
            }
        }
    }

    /**
     * Check jika tipe file adalah gambar
     */
    private static function isImageType(string $ext): bool
    {
        return in_array($ext, self::DEFAULT_IMAGE_TYPES, true);
    }

    /**
     * Validasi MIME type
     */
    private static function isValidMimeType(string $ext, string $mimeType): bool
    {
        if (!isset(self::MIME_TYPES[$ext])) {
            return true; // Jika tidak ada mapping, skip validasi
        }

        return in_array($mimeType, self::MIME_TYPES[$ext], true);
    }

    /**
     * Get upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi ukuran maksimal yang diizinkan oleh server.',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi ukuran maksimal yang diizinkan oleh form.',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori temporary tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP.',
        ];

        return $messages[$errorCode] ?? 'Unknown upload error.';
    }

    /**
     * Delete file dari direktori upload
     * 
     * @param string $fileName Nama file
     * @param string $directory Subdirektori (default: '/default')
     * @return bool
     */
    public static function delete(string $fileName, string $directory = '/default'): bool
    {
        $directory = trim($directory, '/');
        $filePath = ROOT_DIR . '/private-uploads/' . $directory . '/' . $fileName;

        if (!file_exists($filePath)) {
            return false;
        }

        return @unlink($filePath);
    }

    /**
     * Get file info
     * 
     * @param string $fileName Nama file
     * @param string $directory Subdirektori
     * @return array|null
     */
    public static function getFileInfo(string $fileName, string $directory = '/default'): ?array
    {
        $directory = trim($directory, '/');
        $filePath = ROOT_DIR . '/private-uploads/' . $directory . '/' . $fileName;

        if (!file_exists($filePath)) {
            return null;
        }

        return [
            'name' => $fileName,
            'path' => $filePath,
            'size' => filesize($filePath),
            'size_human' => self::formatBytes(filesize($filePath)),
            'extension' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
            'mime_type' => function_exists('mime_content_type') ? mime_content_type($filePath) : null,
            'modified' => filemtime($filePath),
            'modified_human' => date('Y-m-d H:i:s', filemtime($filePath)),
        ];
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Helper: Return success response
     */
    private static function success(string $filename, string $path): array
    {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $path,
            'error' => null,
        ];
    }

    /**
     * Helper: Return error response
     */
    private static function error(string $message): array
    {
        return [
            'success' => false,
            'filename' => null,
            'path' => null,
            'error' => $message,
        ];
    }

    /**
     * Get allowed types berdasarkan kategori
     * 
     * @param string|array $categories 'images', 'documents', 'archives', 'videos', 'audio', atau array kombinasi
     * @return array
     */
    public static function getAllowedTypes($categories = 'all'): array
    {
        if ($categories === 'all') {
            return array_merge(
                self::DEFAULT_IMAGE_TYPES,
                self::DEFAULT_DOCUMENT_TYPES,
                self::DEFAULT_ARCHIVE_TYPES,
                self::DEFAULT_VIDEO_TYPES,
                self::DEFAULT_AUDIO_TYPES
            );
        }

        if (is_string($categories)) {
            $categories = [$categories];
        }

        $types = [];
        foreach ($categories as $category) {
            switch ($category) {
                case 'images':
                    $types = array_merge($types, self::DEFAULT_IMAGE_TYPES);
                    break;
                case 'documents':
                    $types = array_merge($types, self::DEFAULT_DOCUMENT_TYPES);
                    break;
                case 'archives':
                    $types = array_merge($types, self::DEFAULT_ARCHIVE_TYPES);
                    break;
                case 'videos':
                    $types = array_merge($types, self::DEFAULT_VIDEO_TYPES);
                    break;
                case 'audio':
                    $types = array_merge($types, self::DEFAULT_AUDIO_TYPES);
                    break;
            }
        }

        return array_unique($types);
    }

}