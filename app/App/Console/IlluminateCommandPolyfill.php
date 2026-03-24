<?php

namespace Illuminate\Console;

use TheFramework\Console\CommandInterface;
use TheFramework\App\Core\Container;

/**
 * Polyfill untuk class Illuminate\Console\Command.
 * Memastikan perintah dari package Laravel bisa dieksekusi oleh The Framework.
 */
if (!class_exists('Illuminate\Console\Command')) {
    abstract class Command implements CommandInterface
    {
        protected $signature;
        protected $description;

        public function getName(): string
        {
            if (isset($this->signature)) {
                // Ambil command name saja, tanpa parameters
                return explode(' ', $this->signature)[0];
            }
            return 'unknown';
        }

        public function getDescription(): string
        {
            return $this->description ?? '';
        }

        public function run(array $args): void
        {
            $container = Container::getInstance();
            
            // Panggil handle() yang didefinisikan oleh class anak (vendor package)
            if (method_exists($this, 'handle')) {
                $container->call([$this, 'handle']);
                return;
            }

            echo "\033[31mError: Method handle() tidak ditemukan pada " . static::class . "\033[0m" . PHP_EOL;
        }

        // --- Laravel Command Helpers Polyfill ---
        
        protected function info(string $message): void
        {
            echo "  \033[1;44;97m INFO \033[0m " . $message . PHP_EOL;
        }

        protected function error(string $message): void
        {
            echo "  \033[1;41;97m ERROR \033[0m " . $message . PHP_EOL;
        }

        protected function success(string $message): void
        {
            echo "  \033[1;42;30m SUCCESS \033[0m " . $message . PHP_EOL;
        }

        protected function warn(string $message): void
        {
            echo "  \033[1;43;30m WARN \033[0m " . $message . PHP_EOL;
        }

        protected function line(string $message): void
        {
            echo $message . PHP_EOL;
        }
    }
}
