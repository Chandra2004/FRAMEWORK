<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class TinkerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'tinker';
    }

    public function getDescription(): string
    {
        return 'REPL (Read-Eval-Print Loop) Interaktif Premium';
    }

    public function handle(array $args): void
    {
        $this->clear();
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        $this->line("  THE FRAMEWORK TINKER ENGINE v5.0", self::STYLE_BOLD . self::COLOR_CYAN);
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        $this->comment("Ketik 'exit' atau tekan Ctrl+C untuk keluar.");
        echo PHP_EOL;

        // Auto-Alias Models
        $modelsDir = BASE_PATH . '/app/Models';
        if (is_dir($modelsDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelsDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = $file->getBasename('.php');
                    $relPath = str_replace([$modelsDir, '.php', '/'], ['', '', '\\'], $file->getPathname());
                    $fullClassName = "TheFramework\\Models" . $relPath;

                    if (class_exists($fullClassName) && !class_exists($className)) {
                        class_alias($fullClassName, $className);
                    }
                }
            }
        }

        while (true) {
            // Windows ext-readline bug: ANSI code at prompt causes visual cursor jumps (phantom space bug)
            $input = $this->readline(">>> ");

            if ($input === false || in_array(trim($input), ['exit', 'quit', 'bye'])) {
                $this->info("Keluar dari Tinker. Sampai jumpa!");
                break;
            }

            $code = trim($input);
            if ($code === '')
                continue;

            if (substr($code, -1) === ';')
                $code = substr($code, 0, -1);

            // Determine if we should capture return value
            $isEcho = preg_match('/^(echo|print|var_dump|print_r|dump|die|exit)\b/', $code);
            $isAssignment = preg_match('/^\$[a-zA-Z0-9_]+\s*=/', $code);

            $evalCode = (!$isEcho && !$isAssignment) ? "return $code;" : "$code;";

            try {
                ob_start();
                $result = eval ($evalCode);
                $buffer = ob_get_clean();

                if (!empty($buffer))
                    echo $buffer . PHP_EOL;

                if (!$isEcho && $result !== null) {
                    echo "\033[38;5;51m=> \033[0m";
                    if (is_array($result) || (is_object($result) && method_exists($result, 'toArray'))) {
                        $mapped = is_array($result) ? array_map(function ($item) {
                            return (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : $item;
                        }, $result) : $result->toArray();

                        echo json_encode($mapped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    } elseif (is_object($result)) {
                        echo "Object: " . get_class($result) . PHP_EOL;
                    } else {
                        var_export($result);
                        echo PHP_EOL;
                    }
                }
            } catch (Throwable $e) {
                if (ob_get_level() > 0)
                    ob_end_clean();
                echo "\n\033[1;41;97m " . get_class($e) . " \033[0m " . $e->getMessage() . "\n";
            }
        }
    }

    private function readline($prompt)
    {
        if (function_exists('readline')) {
            // Register dummy autocompletion to prevent default file expansion tabs
            if (function_exists('readline_completion_function')) {
                readline_completion_function(function () {
                    return [];
                });
            }
            $line = readline($prompt);
            if (!empty($line))
                readline_add_history($line);
            return $line;
        }
        echo $prompt;
        return rtrim(fgets(STDIN), "\r\n");
    }
}
