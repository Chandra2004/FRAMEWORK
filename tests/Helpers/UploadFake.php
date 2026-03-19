<?php

namespace Tests\Helpers;

class UploadFake
{
    /**
     * Membuat simulasi file upload (array $_FILES).
     *
     * @param string $name Nama asal file
     * @param int $sizeKb Ukuran file dalam KB
     * @param string $mime Mime type (otomatis jika gambar)
     * @return array Array yang menggantikan $_FILES
     */
    public static function create(string $name, int $sizeKb = 100, string $mime = 'application/octet-stream'): array
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'tf_fake_');
        
        // Buat file fiktif sebesar sizeKb
        $content = str_repeat('A', $sizeKb * 1024);
        
        // Jika file adalah gambar, kita buat gambar asli 1x1 agar lolos getimagesize()
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $name, $matches)) {
            $ext = strtolower($matches[1]);
            $mime = "image/" . ($ext === 'jpg' ? 'jpeg' : $ext);
            $im = imagecreatetruecolor(1, 1);
            
            ob_start();
            switch ($ext) {
                case 'jpg': case 'jpeg': imagejpeg($im); break;
                case 'png': imagepng($im); break;
                case 'gif': imagegif($im); break;
                case 'webp': imagewebp($im); break;
            }
            $content = ob_get_clean();
        }
        
        file_put_contents($tmpFile, $content);

        return [
            'name' => $name,
            'type' => $mime,
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile),
        ];
    }
}
