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
            $files = glob($modelsDir . '/**/*.php');
            foreach ($files as $file) {
                $className = basename($file, '.php');
                // Guess namespace from path
                $relPath = str_replace([$modelsDir, '.php', '/'], ['', '', '\\'], $file);
                $fullClassName = "TheFramework\\Models" . $relPath;
                
                if (class_exists($fullClassName) && !class_exists($className)) {
                    class_alias($fullClassName, $className);
                }
            }
        }

        while (true) {
            $input = $this->readline(self::COLOR_GREEN . ">>> " . self::COLOR_RESET);

            if ($input === false || in_array(trim($input), ['exit', 'quit', 'bye'])) {
                $this->info("Keluar dari Tinker. Sampai jumpa!");
                break;
            }

            $code = trim($input);
            if ($code === '') continue;

            if (substr($code, -1) === ';') $code = substr($code, 0, -1);

            // Determine if we should capture return value
            $isEcho = preg_match('/^(echo|print|var_dump|print_r|dump|die|exit)\b/', $code);
            $isAssignment = preg_match('/^\$[a-zA-Z0-9_]+\s*=/', $code);
            
            $evalCode = (!$isEcho && !$isAssignment) ? "return $code;" : "$code;";

            try {
                ob_start();
                $result = eval($evalCode);
                $buffer = ob_get_clean();

                if (!empty($buffer)) echo $buffer . PHP_EOL;

                if (!$isEcho && $result !== null) {
                    echo self::COLOR_CYAN . "=> " . self::COLOR_RESET;
                    if (function_exists('dump')) {
                        dump($result);
                    } else {
                        var_export($result);
                        echo PHP_EOL;
                    }
                }
            } catch (Throwable $e) {
                if (ob_get_level() > 0) ob_end_clean();
                $this->error(get_class($e) . ": " . $e->getMessage());
            }
        }
    }

    private function readline($prompt)
    {
        if (function_exists('readline')) {
            $line = readline($prompt);
            if (!empty($line)) readline_add_history($line);
            return $line;
        }
        echo $prompt;
        return rtrim(fgets(STDIN), "\r\n");
    }
}
