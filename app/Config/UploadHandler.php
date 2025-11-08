<?php

namespace TheFramework\Config;

use Exception;

class UploadHandler
{
    public static function upload(
        array $file,
        string $uploadDir = '/default',
        string $prefix = 'file_',
        bool $convertToWebP = false,
        int $width = 800,
        int $height = 0,
        int $quality = 80
    ): string {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File tidak valid atau tidak diupload dengan benar.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedImage = ['jpg', 'jpeg', 'png'];
        $allowedFiles = ['pdf', 'docx', 'zip', 'txt', 'xlsx', 'csv'];

        $directory = ROOT_DIR . '/private-uploads' . $uploadDir . '/';
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            throw new Exception('Gagal membuat direktori upload.');
        }

        $fileName = $prefix . uniqid() . '.' . ($convertToWebP ? 'webp' : $ext);
        $targetPath = rtrim($directory, '/') . '/' . $fileName;

        if (!in_array($ext, $allowedImage)) {
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Gagal mengupload file.');
            }
            return $fileName;
        }

        $source = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'png' => @imagecreatefrompng($file['tmp_name']),
            default => throw new Exception('Format gambar tidak didukung.')
        };

        if (!$source) {
            throw new Exception('Gagal membaca gambar sumber.');
        }

        $quality = max(1, min($quality, 100));
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        if ($width > 0 && $height > 0) {
            $newWidth = $width;
            $newHeight = $height;
        } elseif ($width > 0) {
            $aspect = $originalHeight / $originalWidth;
            $newWidth = $width;
            $newHeight = intval($width * $aspect);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        imagedestroy($source);

        if ($convertToWebP) {
            if (!imagewebp($resized, $targetPath, $quality)) {
                imagedestroy($resized);
                throw new Exception('Gagal menyimpan file WebP.');
            }
        } else {
            match ($ext) {
                'jpg', 'jpeg' => imagejpeg($resized, $targetPath, $quality),
                'png' => imagepng($resized, $targetPath),
                default => throw new Exception('Format gambar tidak didukung.'),
            };
        }

        imagedestroy($resized);
        return $fileName;
    }

    public static function delete(string $directory = '/default', string $fileName): void
    {
        $filePath = ROOT_DIR . '/private-uploads' . $directory . '/' . $fileName;

        if (!file_exists($filePath)) {
            throw new Exception('File tidak ditemukan.');
        }

        if (!@unlink($filePath)) {
            throw new Exception('Gagal menghapus file.');
        }
    }
}
