<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Exception thrown when validation fails.
 *
 * This is a framework-independent validation exception that can be used
 * with any validator (Laravel, Symfony, or framework-independent).
 *
 * Example:
 * ```php
 * try {
 *     $dto = UserDTO::validateAndCreate($data);
 * } catch (ValidationException $e) {
 *     echo $e->getMessage(); // "Validation failed"
 *     print_r($e->errors()); // ['email' => ['Email is invalid']]
 * }
 * ```
 */
class ValidationException extends RuntimeException
{
    /**
     * @param array<string, array<string>> $errors
     * @param array<string, mixed> $data Original data that failed validation
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        private readonly array $data = [],
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Create a new validation exception with error messages.
     *
     * @param array<string, array<int, string>|string> $errors
     */
    public static function withMessages(array $errors): static
    {
        // Normalize errors to array format
        $normalizedErrors = [];
        foreach ($errors as $field => $messages) {
            $normalizedErrors[$field] = is_array($messages) ? $messages : [$messages];
        }

        return new static('Validation failed', $normalizedErrors);
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
     * Alias for getErrors() to match Laravel's ValidationException API.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Check if a specific field has errors. */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
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

    /**
     * Alias for getFieldErrors().
     *
     * @return array<string>
     */
    public function errorsFor(string $field): array
    {
        return $this->getFieldErrors($field);
    }

    /**
     * Get the first error message for a field.
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /** Alias for first(). */
    public function firstError(string $field): ?string
    {
        return $this->first($field);
    }

    /**
     * Get all error messages as a flat array.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }

    /**
     * Alias for all().
     *
     * @return array<string>
     */
    public function allErrors(): array
    {
        return $this->all();
    }

    /**
     * Get original data that failed validation.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /** Get error count. */
    public function errorCount(): int
    {
        return array_sum(array_map('count', $this->errors));
    }

    /**
     * Convert to array representation.
     *
     * @return array{message: string, errors: array<string, array<string>>}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }

    /** Convert to JSON representation. */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

