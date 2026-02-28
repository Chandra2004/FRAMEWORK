<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class AssetPublishCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'asset:publish';
    }

    public function getDescription(): string
    {
        return 'Salin asset dari resources ke public untuk serving via web server';
    }

    public function run(array $args): void
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        echo "\n  \033[1;44;97m INFO \033[0m Publishing assets...\n";

        $source = ROOT_DIR . '/resources';
        $dest = ROOT_DIR . '/public/assets';

        // Folder yang mau disalin
        $folders = ['css', 'js', 'fonts', 'images'];

        foreach ($folders as $folder) {
            $srcPath = "$source/$folder";
            $destPath = "$dest/$folder";

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath);
                echo "  ✓ Copied: $folder\n";
            }
        }

        echo "\n  \033[1;42;30m SUCCESS \033[0m Assets published to public/assets/\n";
    }

    private function copyDirectory($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir("$dest/$subPath")) {
                    mkdir("$dest/$subPath");
                }
            } else {
                copy($item, "$dest/$subPath");
            }
        }
    }
}
