<?php
    // Cegah akses langsung tanpa parameter
    if (!isset($_GET['file'])) {
        die("Access denied.");
    }
    
    $filename = basename($_GET['file']);
    $subfolder = dirname($_GET['file']);
    $allowedFolders = ['dummy', 'user-pictures'];
    
    // Tolak jika folder tidak diizinkan
    if ($subfolder === "." || !in_array($subfolder, $allowedFolders)) {
        die("Access denied: Invalid folder.");
    }
    
    $baseUploadDir = __DIR__ . '/private-uploads/';
    $filepath = $baseUploadDir . $_GET['file'];
    $defaultDummyFile = $baseUploadDir . 'dummy/dummy.jpg';
    
    // Validasi path dengan realpath (hindari path traversal)
    $realBase = realpath($baseUploadDir);
    $realPath = realpath($filepath);
    if ($realPath === false || strpos($realPath, $realBase) !== 0) {
        die("Access denied: Path traversal detected.");
    }
    
    // Logging (opsional)
    $logDir = __DIR__ . '/app/Storage/logs/';
    $logFile = $logDir . 'file_access.log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    function safeLog($message, $logFile) {
        if (file_exists(dirname($logFile)) && is_writable(dirname($logFile))) {
            file_put_contents($logFile, $message, FILE_APPEND);
        }
    }
    safeLog(date('Y-m-d H:i:s') . " - Requested: $filepath\n", $logFile);
    
    // Cek file ada
    if (!file_exists($filepath)) {
        safeLog(date('Y-m-d H:i:s') . " - File not found, fallback to dummy\n", $logFile);
        $filepath = file_exists($defaultDummyFile) ? $defaultDummyFile : die("File not found.");
    }
    
    // Deteksi MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    if (!$mime) {
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ][$ext] ?? 'application/octet-stream';
    }
    
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimeTypes)) {
        safeLog(date('Y-m-d H:i:s') . " - Invalid MIME type: $mime\n", $logFile);
        die("Invalid file type.");
    }
    
    // Tampilkan gambar
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($filepath));
    readfile($filepath);
    exit;
?>