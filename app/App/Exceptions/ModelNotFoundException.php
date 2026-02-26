<?php

namespace TheFramework\App\Exceptions;

/**
 * ModelNotFoundException — thrown ketika model tidak ditemukan
 * Auto-resolves ke HTTP 404
 */
class ModelNotFoundException extends \RuntimeException
{
    protected string $model = '';
    protected array $ids = [];

    public function __construct(string $model = '', $ids = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->model = $model;
        $this->ids = (array) $ids;

        $message = "No query results for model [{$model}]";
        if (!empty($this->ids)) {
            $message .= " " . implode(', ', $this->ids);
        }
        $message .= ".";

        parent::__construct($message, $code ?: 404, $previous);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model, $ids = []): static
    {
        $this->model = $model;
        $this->ids = (array) $ids;
        $this->message = "No query results for model [{$model}]";
        if (!empty($this->ids)) {
            $this->message .= " " . implode(', ', $this->ids);
        }
        $this->message .= ".";
        return $this;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Get HTTP status code for this exception
     */
    public function getStatusCode(): int
    {
        return 404;
    }
}
