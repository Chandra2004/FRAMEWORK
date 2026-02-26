<?php

namespace TheFramework\App\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;

/**
 * Logging — Full-Featured Logging System
 * 
 * Multi-channel, daily rotation, convenience methods, context, dan monitoring.
 * Terinspirasi dari Laravel Log Facade + fitur ekstra.
 * 
 * @package TheFramework\App\Core
 * @version 5.0.1
 */
class Logging
{
    /**
     * Logger instances per channel
     * @var array<string, Logger>
     */
    private static array $channels = [];

    /**
     * Default channel name
     */
    private static string $defaultChannel = 'app';

    /**
     * Log directory
     */
    private static ?string $logDir = null;

    /**
     * Minimum log level dari config
     */
    private static int $minLevel = Logger::DEBUG;

    /**
     * Channel configurations
     * @var array<string, array>
     */
    private static array $channelConfig = [];

    /**
     * Log history (untuk query/debug)
     */
    private static array $history = [];
    private static int $maxHistory = 100;

    // ========================================================
    //  CONFIGURATION
    // ========================================================

    /**
     * Set log directory
     */
    public static function setLogDir(string $path): void
    {
        static::$logDir = rtrim($path, '/\\');
    }

    /**
     * Get log directory
     */
    public static function getLogDir(): string
    {
        if (static::$logDir === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
            static::$logDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        }

        if (!is_dir(static::$logDir)) {
            mkdir(static::$logDir, 0755, true);
        }

        return static::$logDir;
    }

    /**
     * Set default channel
     */
    public static function setDefaultChannel(string $channel): void
    {
        static::$defaultChannel = $channel;
    }

    /**
     * Set minimum log level
     */
    public static function setMinLevel(int|string $level): void
    {
        if (is_string($level)) {
            $level = static::levelFromName($level);
        }
        static::$minLevel = $level;
    }

    /**
     * Configure a channel
     */
    public static function configureChannel(string $name, array $config): void
    {
        static::$channelConfig[$name] = $config;
        // Reset channel sehingga di-rebuild saat dipakai
        unset(static::$channels[$name]);
    }

    // ========================================================
    //  CHANNEL MANAGEMENT
    // ========================================================

    /**
     * Get or create logger untuk channel tertentu
     */
    public static function channel(string $name = 'app'): Logger
    {
        if (isset(static::$channels[$name])) {
            return static::$channels[$name];
        }

        $logger = new Logger($name);

        // Cek custom config
        if (isset(static::$channelConfig[$name])) {
            static::applyConfig($logger, $name, static::$channelConfig[$name]);
        } else {
            // Default: RotatingFileHandler (daily)
            static::applyDefaultHandlers($logger, $name);
        }

        static::$channels[$name] = $logger;
        return $logger;
    }

    /**
     * Get default channel logger
     */
    public static function getLogger(): Logger
    {
        return static::channel(static::$defaultChannel);
    }

    /**
     * Apply default handlers ke logger
     */
    protected static function applyDefaultHandlers(Logger $logger, string $name): void
    {
        $logDir = static::getLogDir();

        // Daily rotating file handler (max 30 hari)
        $handler = new RotatingFileHandler(
            $logDir . DIRECTORY_SEPARATOR . $name . '.log',
            30,
            static::$minLevel
        );

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        // Slack handler (hanya untuk ERROR+)
        if ($webhook = Config::get('SLACK_WEBHOOK_URL')) {
            try {
                $slackHandler = new SlackWebhookHandler(
                    $webhook,
                    null,
                    null,
                    true,
                    null,
                    false,
                    false,
                    Logger::ERROR
                );
                $logger->pushHandler($slackHandler);
            } catch (\Throwable) {
                // Abaikan jika Slack handler gagal
            }
        }
    }

    /**
     * Apply custom config ke logger
     */
    protected static function applyConfig(Logger $logger, string $name, array $config): void
    {
        $logDir = static::getLogDir();
        $driver = $config['driver'] ?? 'daily';
        $level = static::levelFromName($config['level'] ?? 'debug');
        $maxFiles = $config['max_files'] ?? 30;

        match ($driver) {
            'single' => $logger->pushHandler(
                new StreamHandler($logDir . DIRECTORY_SEPARATOR . ($config['path'] ?? $name . '.log'), $level)
            ),
            'daily' => $logger->pushHandler(
                new RotatingFileHandler($logDir . DIRECTORY_SEPARATOR . ($config['path'] ?? $name . '.log'), $maxFiles, $level)
            ),
            'slack' => (function () use ($logger, $config, $level) {
                    if ($url = ($config['url'] ?? Config::get('SLACK_WEBHOOK_URL'))) {
                        $logger->pushHandler(new SlackWebhookHandler($url, null, null, true, null, false, false, $level));
                    }
                })(),
            default => $logger->pushHandler(
                new StreamHandler($logDir . DIRECTORY_SEPARATOR . $name . '.log', $level)
            ),
        };

        // JSON formatter
        if (($config['format'] ?? null) === 'json') {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof \Monolog\Handler\AbstractProcessingHandler) {
                    $handler->setFormatter(new JsonFormatter());
                }
            }
        }
    }

    /**
     * Convert level name ke Monolog constant
     */
    protected static function levelFromName(string $name): int
    {
        return match (strtolower($name)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
    }

    // ========================================================
    //  CONVENIENCE METHODS (PSR-3 Log Levels)
    // ========================================================

    /**
     * System is unusable.
     */
    public static function emergency(string $message, array $context = []): void
    {
        static::log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public static function alert(string $message, array $context = []): void
    {
        static::log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     */
    public static function critical(string $message, array $context = []): void
    {
        static::log('critical', $message, $context);
    }

    /**
     * Runtime errors.
     */
    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public static function notice(string $message, array $context = []): void
    {
        static::log('notice', $message, $context);
    }

    /**
     * Interesting events.
     */
    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public static function debug(string $message, array $context = []): void
    {
        static::log('debug', $message, $context);
    }

    /**
     * Log with arbitrary level.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logger = static::getLogger();
        $monologLevel = static::levelFromName($level);
        $logger->log($monologLevel, $message, $context);

        // Track history
        static::addToHistory($level, $message, $context);
    }

    /**
     * Log ke channel tertentu
     * 
     * Contoh: Logging::on('query')->info('SELECT * ...');
     */
    public static function on(string $channel): ChannelLogger
    {
        return new ChannelLogger($channel);
    }

    /**
     * Log ke multiple channels (stack)
     * 
     * Contoh: Logging::stack(['app', 'slack'])->error('Server down!');
     */
    public static function stack(array $channels): StackLogger
    {
        return new StackLogger($channels);
    }

    // ========================================================
    //  HISTORY & QUERY
    // ========================================================

    protected static function addToHistory(string $level, string $message, array $context): void
    {
        static::$history[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => date('Y-m-d H:i:s'),
        ];

        // Limit history size
        if (count(static::$history) > static::$maxHistory) {
            array_shift(static::$history);
        }
    }

    /**
     * Get log history (current request)
     */
    public static function getHistory(?string $level = null): array
    {
        if ($level === null)
            return static::$history;

        return array_filter(static::$history, fn($entry) => $entry['level'] === $level);
    }

    /**
     * Clear history
     */
    public static function clearHistory(): void
    {
        static::$history = [];
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Log execution time of a callback
     * 
     * Contoh: Logging::benchmark('Heavy query', fn() => $db->query($sql));
     */
    public static function benchmark(string $label, \Closure $callback, string $level = 'debug'): mixed
    {
        $start = microtime(true);
        $result = $callback();
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        static::log($level, "{$label} — {$elapsed}ms");
        return $result;
    }

    /**
     * Log exception dengan stack trace
     */
    public static function exception(\Throwable $e, string $level = 'error'): void
    {
        static::log($level, $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Conditional logging
     */
    public static function logIf(bool $condition, string $level, string $message, array $context = []): void
    {
        if ($condition) {
            static::log($level, $message, $context);
        }
    }

    /**
     * Log with caller info (auto-detect file & line)
     */
    public static function trace(string $message, string $level = 'debug'): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0] ?? [];

        static::log($level, $message, [
            'file' => ($caller['file'] ?? 'unknown') . ':' . ($caller['line'] ?? 0),
            'function' => ($caller['class'] ?? '') . ($caller['type'] ?? '') . ($caller['function'] ?? ''),
        ]);
    }

    /**
     * Read last N lines from current log file
     */
    public static function tail(int $lines = 50, string $channel = 'app'): array
    {
        $logDir = static::getLogDir();
        $date = date('Y-m-d');
        $file = $logDir . DIRECTORY_SEPARATOR . $channel . '-' . $date . '.log';

        if (!file_exists($file)) {
            // Try non-rotating file
            $file = $logDir . DIRECTORY_SEPARATOR . $channel . '.log';
            if (!file_exists($file))
                return [];
        }

        $allLines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($allLines))
            return [];

        return array_slice($allLines, -$lines);
    }

    /**
     * Get log file size (bytes)
     */
    public static function getLogSize(string $channel = 'app'): int
    {
        $logDir = static::getLogDir();
        $size = 0;

        foreach (glob($logDir . DIRECTORY_SEPARATOR . $channel . '*.log') as $file) {
            $size += filesize($file);
        }

        return $size;
    }

    /**
     * Clear log files for a channel
     */
    public static function clear(string $channel = 'app'): int
    {
        $logDir = static::getLogDir();
        $deleted = 0;

        foreach (glob($logDir . DIRECTORY_SEPARATOR . $channel . '*.log') as $file) {
            @unlink($file);
            $deleted++;
        }

        // Reset channel so it recreates handlers
        unset(static::$channels[$channel]);
        return $deleted;
    }

    /**
     * Formatted summary
     */
    public static function summary(): string
    {
        $logDir = static::getLogDir();
        $channels = [];

        foreach (glob($logDir . DIRECTORY_SEPARATOR . '*.log') as $file) {
            $name = basename($file, '.log');
            $name = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $name);
            if (!isset($channels[$name])) {
                $channels[$name] = ['files' => 0, 'size' => 0];
            }
            $channels[$name]['files']++;
            $channels[$name]['size'] += filesize($file);
        }

        $lines = [];
        $lines[] = "📊 Logging Summary";
        $lines[] = str_repeat('─', 40);
        $lines[] = "   Directory: $logDir";
        $lines[] = "   Default:   " . static::$defaultChannel;

        foreach ($channels as $name => $info) {
            $size = $info['size'] < 1048576
                ? round($info['size'] / 1024, 1) . 'KB'
                : round($info['size'] / 1048576, 1) . 'MB';
            $lines[] = "   📄 {$name}: {$info['files']} file(s), {$size}";
        }

        return implode("\n", $lines);
    }
}

// ========================================================
//  CHANNEL LOGGER — Fluent channel-specific logging
// ========================================================

class ChannelLogger
{
    private string $channel;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function emergency(string $msg, array $ctx = []): void
    {
        $this->log('emergency', $msg, $ctx);
    }
    public function alert(string $msg, array $ctx = []): void
    {
        $this->log('alert', $msg, $ctx);
    }
    public function critical(string $msg, array $ctx = []): void
    {
        $this->log('critical', $msg, $ctx);
    }
    public function error(string $msg, array $ctx = []): void
    {
        $this->log('error', $msg, $ctx);
    }
    public function warning(string $msg, array $ctx = []): void
    {
        $this->log('warning', $msg, $ctx);
    }
    public function notice(string $msg, array $ctx = []): void
    {
        $this->log('notice', $msg, $ctx);
    }
    public function info(string $msg, array $ctx = []): void
    {
        $this->log('info', $msg, $ctx);
    }
    public function debug(string $msg, array $ctx = []): void
    {
        $this->log('debug', $msg, $ctx);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logger = Logging::channel($this->channel);
        $monologLevel = match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
        $logger->log($monologLevel, $message, $context);
    }
}

// ========================================================
//  STACK LOGGER — Log ke multiple channels sekaligus
// ========================================================

class StackLogger
{
    private array $channels;

    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    public function emergency(string $msg, array $ctx = []): void
    {
        $this->log('emergency', $msg, $ctx);
    }
    public function alert(string $msg, array $ctx = []): void
    {
        $this->log('alert', $msg, $ctx);
    }
    public function critical(string $msg, array $ctx = []): void
    {
        $this->log('critical', $msg, $ctx);
    }
    public function error(string $msg, array $ctx = []): void
    {
        $this->log('error', $msg, $ctx);
    }
    public function warning(string $msg, array $ctx = []): void
    {
        $this->log('warning', $msg, $ctx);
    }
    public function notice(string $msg, array $ctx = []): void
    {
        $this->log('notice', $msg, $ctx);
    }
    public function info(string $msg, array $ctx = []): void
    {
        $this->log('info', $msg, $ctx);
    }
    public function debug(string $msg, array $ctx = []): void
    {
        $this->log('debug', $msg, $ctx);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($this->channels as $channel) {
            Logging::on($channel)->log($level, $message, $context);
        }
    }
}
