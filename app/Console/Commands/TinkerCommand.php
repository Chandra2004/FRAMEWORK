<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class TinkerCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'tinker';
    }

    public function getDescription(): string
    {
        return 'Interact with your application';
    }

    public function run(array $args): void
    {
        echo "The Framework Tinker (v1.0.0)\n";
        echo "Type 'exit' or press Ctrl+C to quit.\n\n";

        // 1. Auto-Alias Models
        // Scan app/Models dan buat alias agar bisa dipanggil tanpa namespace
        $modelsDir = __DIR__ . '/../../Models';
        if (is_dir($modelsDir)) {
            $files = glob($modelsDir . '/*.php');
            foreach ($files as $file) {
                $className = basename($file, '.php');
                $fullClassName = "\\TheFramework\\Models\\$className";
                if (class_exists($fullClassName) && !class_exists($className)) {
                    class_alias($fullClassName, $className);
                }
            }
        }

        // Loop REPL (Read-Eval-Print Loop)
        while (true) {
            // Read
            $line = $this->readline(">>> ");

            // Cek kondisi keluar
            if ($line === false || trim($line) === 'exit' || trim($line) === 'quit') {
                echo "\nGoodbye!\n";
                break;
            }

            // Skip baris kosong
            if (trim($line) === '') {
                continue;
            }

            // Clean input
            $code = trim($line);

            // Hapus semicolon di akhir untuk diproses (nanti kita tambah sendiri jika perlu)
            if (substr($code, -1) === ';') {
                $code = substr($code, 0, -1);
            }

            // Cek apakah ini assignment atau echo statement manual
            $isEcho = preg_match('/^(echo|print|var_dump|print_r)\s/', $code);
            $isAssignment = preg_match('/^\$[a-zA-Z0-9_]+\s*=/', $code);

            // Jika bukan echo dan bukan assignment, kita coba tambahkan 'return' 
            // agar eval mengembalikan nilainya untuk di-dump
            if (!$isEcho && !$isAssignment) {
                $evalCode = "return $code;";
            } else {
                $evalCode = "$code;";
            }

            // Eval code
            try {
                // Buffer output (untuk menangkap echo dari user)
                ob_start();

                // Eksekusi
                $result = eval ($evalCode);

                $output = ob_get_clean();

                // 1. Tampilkan output buffer (jika user pakai echo)
                if (!empty($output)) {
                    echo $output . "\n";
                }

                // 2. Tampilkan return value (jika ada, dan bukan echo manual dari user)
                // Jika user mengetik ekspresi seperti '1+1' atau 'User::all()', result akan berisi nilainya.
                if (!$isEcho && $result !== null) {
                    // Gunakan print_r atau var_dump yang rapi
                    // Jika ada dump() helper (misal dari symfony/var-dumper), gunakan itu
                    if (function_exists('dump')) {
                        dump($result);
                    } else {
                        // Fallback simple dump
                        echo "=> ";
                        var_dump($result);
                    }
                }

            } catch (\Throwable $e) {
                ob_end_clean(); // bersihkan buffer
                echo "\033[31m" . get_class($e) . ": " . $e->getMessage() . "\033[0m\n";
            }
        }
    }

    /**
     * Wrapper untuk readline dengan fallback
     */
    private function readline($prompt)
    {
        if (function_exists('readline')) {
            $line = readline($prompt);
            if (!empty($line)) {
                readline_add_history($line);
            }
            return $line;
        } else {
            echo $prompt;
            $line = fgets(STDIN);
            if ($line === false)
                return false;
            return rtrim($line, "\r\n");
        }
    }
}
