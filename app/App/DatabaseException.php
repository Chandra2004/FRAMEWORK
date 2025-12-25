<?php

namespace TheFramework\App;

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

    /**
     * Get detailed error message for display
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();
        
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
}
