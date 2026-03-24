<?php

namespace TheFramework\App\Console;

use TheFramework\App\Core\Container;
use TheFramework\Console\LaravelCommandAdapter;

/**
 * HybridCommandScanner - Mesin Intelegensi untuk mendeteksi perintah Vendor.
 * Memastikan 'TheFramework' mendukung ekosistem Laravel secara otomatis.
 * 
 * @package TheFramework\App\Console
 * @version 2.2.0
 */
class HybridCommandScanner
{
    public static function scan(array &$existingCommands): void
    {
        // Muat File Polyfill agar package Laravel tidak mengalami Fatal Error
        require_once __DIR__ . '/IlluminateCommandPolyfill.php';

        $installedPath = BASE_PATH . '/vendor/composer/installed.json';
        if (!file_exists($installedPath)) return;

        $json = json_decode(file_get_contents($installedPath), true);
        $packages = $json['packages'] ?? [];

        foreach ($packages as $pkg) {
            $installPath = $pkg['install-path'] ?? '';
            $pkgBaseDir = realpath(BASE_PATH . '/vendor/composer/' . $installPath);
            
            if (!$pkgBaseDir || !is_dir($pkgBaseDir)) continue;

            $scanDirs = [
                $pkgBaseDir . '/src/Console', 
                $pkgBaseDir . '/src/Commands',
                $pkgBaseDir . '/src/Features/SupportConsoleCommands/Commands'
            ];
            
            foreach ($scanDirs as $dir) {
                if (!is_dir($dir)) continue;

                // --- DEEP RECURSIVE SCAN ---
                $it = new \RecursiveDirectoryIterator($dir);
                $it = new \RecursiveIteratorIterator($it);
                $files = new \RegexIterator($it, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

                foreach ($files as $fileMatch) {
                    $file = $fileMatch[0];
                    $baseName = basename($file, '.php');
                    
                    foreach ($pkg['autoload']['psr-4'] ?? [] as $ns => $src) {
                        $dirNormalized = str_replace('\\', '/', dirname($file));
                        $pkgBaseDirNormalized = str_replace('\\', '/', $pkgBaseDir);
                        $srcNormalized = trim(str_replace('\\', '/', $src), '/');

                        $relativePath = str_replace($pkgBaseDirNormalized . '/', '', $dirNormalized);
                        
                        if ($srcNormalized !== '' && strpos($relativePath, $srcNormalized) === 0) {
                            $subNsDir = substr($relativePath, strlen($srcNormalized));
                            $subNsDir = trim($subNsDir, '/');
                        } else {
                            $subNsDir = trim($relativePath, '/');
                        }
                        
                        $subNs = str_replace('/', '\\', $subNsDir);
                        $fullClass = rtrim($ns, '\\') . '\\' . ($subNs ? $subNs . '\\' : '') . $baseName;

                        if (class_exists($fullClass)) {
                            try {
                                $ref = new \ReflectionClass($fullClass);
                                if ($ref->isInstantiable() && 
                                    ($ref->implementsInterface('TheFramework\Console\CommandInterface') || 
                                     $ref->isSubclassOf('Symfony\Component\Console\Command\Command'))) {
                                    
                                    $adapter = new LaravelCommandAdapter($fullClass);
                                    $existingCommands[$adapter->getName()] = $adapter;
                                }
                            } catch (\Throwable) { continue; }
                        }
                    }
                }
            }
        }
    }
}
