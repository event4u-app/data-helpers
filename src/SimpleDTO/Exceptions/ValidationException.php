<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Exceptions;

use RuntimeException;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends RuntimeException
{
    /**
     * @param string $message
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if a specific field has errors.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get errors for a specific field.
     *
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}

