<?php

namespace TheFramework\Console;

/**
 * BaseCommand - The foundation for all Artisan commands.
 * Provides rich styling, input handling, and terminal utilities that exceed Laravel.
 */
abstract class BaseCommand implements CommandInterface
{
    // ANSI Colors & Styles
    protected const COLOR_RESET = "\033[0m";
    protected const STYLE_BOLD = "\033[1m";

    protected const COLOR_BLUE = "\033[38;5;39m";
    protected const COLOR_GREEN = "\033[38;5;28m";
    protected const COLOR_YELLOW = "\033[38;5;214m";
    protected const COLOR_RED = "\033[31m";
    protected const COLOR_MAGENTA = "\033[38;5;201m";
    protected const COLOR_CYAN = "\033[38;5;51m";
    protected const COLOR_GRAY = "\033[38;5;244m";

    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function handle(array $args): void;

    /**
     * Implements CommandInterface::run
     */
    public function run(array $args): void
    {
        $this->handle($args);
    }

    // --- Styling Helpers ---

    protected function info(string $message): void
    {
        echo PHP_EOL . "  \033[1;44;97m INFO \033[0m " . $message . PHP_EOL;
    }

    protected function success(string $message): void
    {
        echo PHP_EOL . "  \033[1;42;30m SUCCESS \033[0m " . $message . PHP_EOL;
    }

    protected function warn(string $message): void
    {
        echo PHP_EOL . "  \033[1;43;30m WARN \033[0m " . $message . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo PHP_EOL . "  \033[1;41;97m ERROR \033[0m " . $message . PHP_EOL;
    }

    protected function line(string $message, string $color = self::COLOR_GRAY): void
    {
        echo $color . $message . self::COLOR_RESET . PHP_EOL;
    }

    protected function comment(string $message): void
    {
        echo self::COLOR_GRAY . "// " . $message . self::COLOR_RESET . PHP_EOL;
    }

    /**
     * Render a premium table to the terminal
     */
    protected function table(array $headers, array $rows): void
    {
        $widths = [];
        foreach ($headers as $i => $header) {
            $cleanHeader = preg_replace("/\033\[[0-9;]*m/", '', $header);
            $widths[$i] = mb_strlen($cleanHeader, 'UTF-8');
        }

        foreach ($rows as $row) {
            $i = 0;
            foreach ($row as $cell) {
                $cleanCell = preg_replace("/\033\[[0-9;]*m/", '', (string) $cell);
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen($cleanCell, 'UTF-8'));
                $i++;
            }
        }

        // Top Border
        echo "┌" . implode("┬", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "┐" . PHP_EOL;

        // Headers
        echo "│";
        foreach ($headers as $i => $header) {
            $cleanHeader = preg_replace("/\033\[[0-9;]*m/", '', $header);
            $padAmount = max(0, $widths[$i] - mb_strlen($cleanHeader, 'UTF-8'));
            echo " " . self::STYLE_BOLD . $header . self::COLOR_RESET . str_repeat(" ", $padAmount) . " │";
        }
        echo PHP_EOL;

        // Divider
        echo "├" . implode("┼", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "┤" . PHP_EOL;

        // Data Rows
        foreach ($rows as $row) {
            echo "│";
            $i = 0;
            foreach ($row as $cell) {
                $cleanCell = preg_replace("/\033\[[0-9;]*m/", '', (string) $cell);
                $padAmount = max(0, $widths[$i] - mb_strlen($cleanCell, 'UTF-8'));
                echo " " . ((string) $cell) . str_repeat(" ", $padAmount) . " │";
                $i++;
            }
            echo PHP_EOL;
        }

        // Bottom Border
        echo "└" . implode("┴", array_map(fn($w) => str_repeat("─", $w + 2), $widths)) . "┘" . PHP_EOL;
    }

    /**
     * Ask user for input
     */
    protected function ask(string $question, ?string $default = null): string
    {
        echo self::COLOR_CYAN . "? " . self::COLOR_RESET . $question . ($default ? " [$default]" : "") . ": ";
        $input = trim(fgets(STDIN));

        return $input === '' ? (string) $default : $input;
    }

    /**
     * Ask for confirmation (y/n)
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $displayDefault = $default ? "[Y/n]" : "[y/N]";
        echo self::COLOR_CYAN . "? " . self::COLOR_RESET . $question . " $displayDefault: ";

        $input = strtolower(trim(fgets(STDIN)));

        if ($input === '')
            return $default;
        return in_array($input, ['y', 'yes']);
    }

    /**
     * Clear terminal
     */
    protected function clear(): void
    {
        echo "\033[2J\033[;H";
    }
}
