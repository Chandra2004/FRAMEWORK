<?php

namespace TheFramework\Config;

use Exception;

class UploadHandler
{
    private const ALLOWED_IMAGES = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    private const ALLOWED_FILES  = ['pdf', 'docx', 'zip', 'txt', 'xlsx', 'csv'];

    /**
     * Upload file dan (opsional) konversi gambar ke WebP atau SVG.
     *
     * @param array  $file File dari $_FILES
     * @param string $uploadDir Direktori penyimpanan relatif dari /private-uploads
     * @param string $prefix Prefix nama file
     * @param string|null $convertTo Format konversi ('webp' atau 'svg')
     * @param int $width Resize width
     * @param int $height Resize height
     * @param int $quality Kualitas gambar (1–100)
     * @return string Nama file baru
     * @throws Exception
     */
    public static function upload(
        array $file,
        string $uploadDir = '/default',
        string $prefix = 'file_',
        ?string $convertTo = null, // bisa 'webp', 'svg', atau null
        int $width = 800,
        int $height = 0,
        int $quality = 80
    ): string {
        // 🔹 Validasi dasar
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File tidak valid atau tidak diupload dengan benar.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isImage = in_array($ext, self::ALLOWED_IMAGES, true);

        if (!$isImage && !in_array($ext, self::ALLOWED_FILES, true)) {
            throw new Exception("Tipe file .$ext tidak diizinkan.");
        }

        // 🔹 Siapkan direktori upload
        $directory = rtrim(ROOT_DIR . '/private-uploads' . $uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('Gagal membuat direktori upload.');
        }

        $targetExt = $convertTo && $isImage ? strtolower($convertTo) : $ext;
        $fileName = sprintf('%s%s.%s', $prefix, uniqid('', true), $targetExt);
        $targetPath = $directory . $fileName;

        // 🔹 Upload file non-gambar (langsung simpan)
        if (!$isImage) {
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Gagal mengupload file non-gambar.');
            }
            return $fileName;
        }

        // 🔹 SVG tidak perlu diubah via GD
        if ($ext === 'svg' || $targetExt === 'svg') {
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Gagal mengupload file SVG.');
            }
            return $fileName;
        }

        // 🔹 Buat resource dari gambar raster
        $source = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'png' => imagecreatefrompng($file['tmp_name']),
            'webp' => imagecreatefromwebp($file['tmp_name']),
            default => throw new Exception('Format gambar tidak didukung untuk konversi.'),
        };

        if (!$source) {
            throw new Exception('Gagal membaca gambar sumber.');
        }

        [$newWidth, $newHeight] = self::calculateSize($source, $width, $height);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled(
            $resized,
            $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            imagesx($source),
            imagesy($source)
        );
        imagedestroy($source);

        $quality = max(1, min($quality, 100));

        $saved = match ($targetExt) {
            'webp' => imagewebp($resized, $targetPath, $quality),
            'jpg', 'jpeg' => imagejpeg($resized, $targetPath, $quality),
            'png' => imagepng($resized, $targetPath),
            default => false,
        };

        imagedestroy($resized);

        if (!$saved) {
            throw new Exception('Gagal menyimpan hasil gambar.');
        }

        return $fileName;
    }

    /**
     * Menghitung ukuran gambar hasil resize.
     */
    private static function calculateSize($source, int $width, int $height): array
    {
        $origW = imagesx($source);
        $origH = imagesy($source);

        if ($width <= 0 && $height <= 0) {
            return [$origW, $origH];
        }

        if ($height > 0 && $width > 0) {
            return [$width, $height];
        }

        $aspect = $origH / $origW;
        $newW = $width > 0 ? $width : (int)($height / $aspect);
        $newH = (int)($newW * $aspect);

        return [$newW, $newH];
    }

    /**
     * Menghapus file dari direktori upload.
     *
     * @param string $directory Subdirektori (misalnya '/default')
     * @param string $fileName Nama file yang akan dihapus
     * @throws Exception
     */
    public static function delete(string $directory = '/default', string $fileName): void
    {
        $filePath = ROOT_DIR . '/private-uploads' . $directory . '/' . $fileName;

        if (!file_exists($filePath)) {
            throw new Exception('File tidak ditemukan: ' . $fileName);
        }

        if (!@unlink($filePath)) {
            throw new Exception('Gagal menghapus file: ' . $fileName);
        }
    }
}
