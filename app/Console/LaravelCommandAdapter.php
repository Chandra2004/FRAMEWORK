<?php

namespace TheFramework\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use ReflectionClass;
use TheFramework\App\Core\Container;

/**
 * Bridge antara Laravel/Symfony Command dengan The Framework Artisan Runner.
 * Mendukung "Lazy Loading" agar tidak terjadi Fatal Error saat memindai Command.
 * 
 * @package TheFramework\Console
 * @version 2.0.0
 */
class LaravelCommandAdapter implements CommandInterface
{
    /** @var mixed|string */
    protected $symfonyCommand;

    protected string $lazyName = 'unknown';
    protected string $lazyDescription = '';

    public function __construct($command)
    {
        $this->symfonyCommand = $command;

        // Jika string, extract nama & deskripsi via Reflection (Lazy Load)
        if (is_string($command) && class_exists($command)) {
            $ref = new ReflectionClass($command);
            $defaults = $ref->getDefaultProperties();
            
            $this->lazyName = $defaults['name'] ?? '';
            if (!$this->lazyName && !empty($defaults['signature'])) {
                $this->lazyName = explode(' ', $defaults['signature'])[0];
            }
            if (!$this->lazyName) {
                $this->lazyName = 'unknown:' . basename(str_replace('\\', '/', $command));
            }

            $this->lazyDescription = $defaults['description'] ?? '';
        }
    }

    public function getName(): string
    {
        if (is_string($this->symfonyCommand)) {
            return $this->lazyName;
        }
        return $this->symfonyCommand->getName() ?? 'unknown';
    }

    public function getDescription(): string
    {
        if (is_string($this->symfonyCommand)) {
            return $this->lazyDescription;
        }
        return $this->symfonyCommand->getDescription() ?? '';
    }

    /**
     * Jalankan perintah menggunakan Symfony Input/Output standar.
     */
    public function run(array $args): void
    {
        array_unshift($args, $this->getName());
        
        $argvInput = 'Symfony\Component\Console\Input\ArgvInput';
        $consoleOutput = 'Symfony\Component\Console\Output\ConsoleOutput';

        if (!class_exists($argvInput)) {
            echo "\033[31m ✖ ERROR: Library 'symfony/console' belum terinstall. \033[0m" . PHP_EOL;
            return;
        }

        $input = new $argvInput($args);
        $output = new $consoleOutput();
        
        try {
            $instance = $this->symfonyCommand;
            
            // Lazy Instantiation menggunakan DI Container khusus saat dieksekusi
            if (is_string($instance)) {
                $container = Container::getInstance();
                $instance = $container->make($instance);
            }

            $instance->run($input, $output);
        } catch (\Throwable $e) {
            echo PHP_EOL . "\033[1;41;97m BRIDGE CRITICAL ERROR \033[0m " . $e->getMessage() . PHP_EOL;
            echo "\033[90m File: " . $e->getFile() . ":" . $e->getLine() . "\033[0m" . PHP_EOL;
        }
    }
}
