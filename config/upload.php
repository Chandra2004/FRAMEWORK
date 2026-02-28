<?php

/**
 * Upload Configuration — The Framework v5.0.1
 * All values driven by .env
 */

return [
    'default_dir'  => $_ENV['UPLOAD_DIR'] ?? '/private-uploads',
    'max_size'     => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10240) * 1024, // Convert KB to Bytes
    'auto_webp'    => filter_var($_ENV['UPLOAD_AUTO_WEBP'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'webp_quality' => (int) ($_ENV['UPLOAD_WEBP_QUALITY'] ?? 80),

    'allowed_categories' => [
        'images'    => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
        'archives'  => ['zip', 'rar', '7z', 'tar', 'gz'],
        'videos'    => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'audio'     => ['mp3', 'wav', 'ogg', 'm4a', 'aac'],
    ],
];
