<?php

namespace TheFramework\App\Database;

use PDOException;

/**
 * Custom Exception untuk Database Connection Errors
 * Menyediakan informasi detail tentang error koneksi database
 */
class DatabaseException extends PDOException
{
    private array $configErrors = [];
    private array $envErrors = [];
    public bool $isConnectionRequired = false;
    private ?string $sql = null;
    private array $bindings = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $configErrors = [],
        array $envErrors = [],
        bool $isConnectionRequired = false
    ) {
        parent::__construct($message, $code, $previous);
        $this->configErrors = $configErrors;
        $this->envErrors = $envErrors;
        $this->isConnectionRequired = $isConnectionRequired;
    }

    /**
     * Get configuration errors (missing or empty values)
     */
    public function getConfigErrors(): array
    {
        return $this->configErrors;
    }

    /**
     * Get environment variable errors (typos, wrong names)
     */
    public function getEnvErrors(): array
    {
        return $this->envErrors;
    }

    /**
     * Check if database connection is required for this operation
     */
    public function isConnectionRequired(): bool
    {
        return $this->isConnectionRequired;
    }

    // ========================================================
    //  SQL QUERY TRACKING
    // ========================================================

    public function setSql(string $sql): static
    {
        $this->sql = $sql;
        return $this;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function setBindings(array $bindings): static
    {
        $this->bindings = $bindings;
        return $this;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    // ========================================================
    //  ERROR CLASSIFICATION
    // ========================================================

    public function isConnectionError(): bool
    {
        $msg = strtolower($this->getMessage());
        return str_contains($msg, 'connection')
            || str_contains($msg, 'access denied')
            || str_contains($msg, 'unknown database')
            || str_contains($msg, 'could not find driver');
    }

    public function isQueryError(): bool
    {
        return $this->sql !== null;
    }

    public function isDeadlock(): bool
    {
        $msg = strtolower($this->getMessage());
        return str_contains($msg, 'deadlock') || str_contains($msg, 'lock wait timeout');
    }

    public function isUniqueConstraintError(): bool
    {
        $msg = strtolower($this->getMessage());
        return str_contains($msg, 'duplicate entry')
            || str_contains($msg, 'unique constraint');
    }

    public function isSyntaxError(): bool
    {
        $msg = strtolower($this->getMessage());
        return str_contains($msg, 'syntax error')
            || str_contains($msg, 'you have an error in your sql');
    }

    // ========================================================
    //  DETAILED OUTPUT
    // ========================================================

    /**
     * Get detailed error message for display
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->sql) {
            $message .= "\n\nSQL Query:\n  " . $this->sql;
            if (!empty($this->bindings)) {
                $message .= "\n\nBindings:\n  " . json_encode($this->bindings, JSON_PRETTY_PRINT);
            }
        }

        if (!empty($this->configErrors)) {
            $message .= "\n\nConfiguration Errors:\n";
            foreach ($this->configErrors as $error) {
                $message .= "  - " . $error . "\n";
            }
        }

        if (!empty($this->envErrors)) {
            $message .= "\n\nEnvironment Variable Errors (Possible Typos):\n";
            foreach ($this->envErrors as $error) {
                $message .= "  - " . $error . "\n";
            }
        }

        if ($this->isConnectionRequired) {
            $message .= "\n\n⚠️ This page requires a database connection.";
        }

        return $message;
    }

    /**
     * Convert to array — useful for API error responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'configErrors' => $this->configErrors,
            'envErrors' => $this->envErrors,
            'isConnectionError' => $this->isConnectionError(),
            'isQueryError' => $this->isQueryError(),
            'isDeadlock' => $this->isDeadlock(),
        ];
    }

    // ========================================================
    //  STATIC FACTORY METHODS
    // ========================================================

    public static function connectionFailed(string $driver, ?\Throwable $previous = null): static
    {
        return new static(
            "Failed to connect to {$driver} database.",
            0,
            $previous,
            [],
            [],
            true
        );
    }

    public static function queryFailed(string $sql, array $bindings = [], ?\Throwable $previous = null): static
    {
        $e = new static(
            "Query failed: " . ($previous?->getMessage() ?? 'Unknown error'),
            0,
            $previous
        );
        $e->setSql($sql);
        $e->setBindings($bindings);
        return $e;
    }

    public static function tableNotFound(string $table): static
    {
        return new static("Table '{$table}' not found in database.");
    }

    public static function columnNotFound(string $column, string $table): static
    {
        return new static("Column '{$column}' not found in table '{$table}'.");
    }

    public static function modelNotFound(string $model, $id): static
    {
        return new static("No query results for model [{$model}] with ID {$id}.");
    }
}

/**
 * QueryException — for SQL query specific errors
 */
class QueryException extends DatabaseException
{
    public function __construct(
        string $sql,
        array $bindings = [],
        ?\Throwable $previous = null
    ) {
        $message = "Query Exception: " . ($previous?->getMessage() ?? 'Unknown error');
        parent::__construct($message, 0, $previous);
        $this->setSql($sql);
        $this->setBindings($bindings);
    }
}

