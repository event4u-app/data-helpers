<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Validation;

/**
 * Result of a validation operation.
 *
 * Contains validation status, errors, and validated data.
 *
 * Example:
 * ```php
 * $result = UserDTO::validateData($data);
 *
 * if ($result->isValid()) {
 *     $dto = UserDTO::fromArray($result->validated());
 * } else {
 *     foreach ($result->errors() as $field => $messages) {
 *         echo "$field: " . implode(', ', $messages);
 *     }
 * }
 * ```
 */
final readonly class ValidationResult
{
    /**
     * @param bool $valid Whether validation passed
     * @param array<string, array<string>> $errors Validation errors by field
     * @param array<string, mixed> $validated Validated data
     */
    public function __construct(
        private bool $valid,
        private array $errors,
        private array $validated,
    ) {}

    /**
     * Create a successful validation result.
     *
     * @param array<string, mixed> $validated
     */
    public static function success(array $validated): self
    {
        return new self(true, [], $validated);
    }

    /**
     * Create a failed validation result.
     *
     * @param array<string, array<string>> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors, []);
    }

    /** Check if validation passed. */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /** Check if validation failed. */
    public function isFailed(): bool
    {
        return !$this->valid;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @return array<string>
     */
    public function errorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /** Get first error for a specific field. */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all error messages as flat array.
     *
     * @return array<string>
     */
    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $messages) {
            $all = array_merge($all, $messages);
        }
        return $all;
    }

    /**
     * Get validated data.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /** Check if a specific field has errors. */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /** Get error count. */
    public function errorCount(): int
    {
        return array_sum(array_map('count', $this->errors));
    }

    /**
     * Convert to array representation.
     *
     * @return array{valid: bool, errors: array<string, array<string>>, validated: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'validated' => $this->validated,
        ];
    }
}

