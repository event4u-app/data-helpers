<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Stringable;
use Traversable;

/**
 * Collection for validation errors.
 *
 * Provides a convenient API for working with validation errors.
 *
 * Example:
 *   $errors = $dto->validationErrors();
 *   $errors->has('email');
 *   $errors->get('email');
 *   $errors->first('email');
 *   $errors->all();
 *
 * @implements IteratorAggregate<string, array<string>>
 * @implements ArrayAccess<string, array<string>>
 */
final readonly class ValidationErrorCollection implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable, Stringable
{
    /** @param array<string, array<string>> $errors */
    public function __construct(
        private array $errors = [],
    ) {
    }

    /** Check if errors exist for a field. */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]) && [] !== $this->errors[$field];
    }

    /**
     * Get all errors for a field.
     *
     * @return array<string>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /** Get the first error for a field. */
    public function first(string $field): ?string
    {
        $errors = $this->get($field);
        return $errors[0] ?? null;
    }

    /**
     * Get all errors.
     *
     * @return array<string, array<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /** Check if there are any errors. */
    public function isEmpty(): bool
    {
        return [] === $this->errors;
    }

    /** Check if there are any errors. */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get all error messages as a flat array.
     *
     * @return array<string>
     */
    public function messages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /** Get the first error message across all fields. */
    public function firstMessage(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if ([] !== $fieldErrors) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * Get all field names that have errors.
     *
     * @return array<string>
     */
    public function fields(): array
    {
        return array_keys($this->errors);
    }

    /** Count the number of fields with errors. */
    public function count(): int
    {
        return count($this->errors);
    }

    /** Count the total number of error messages. */
    public function countMessages(): int
    {
        $count = 0;
        foreach ($this->errors as $fieldErrors) {
            $count += count($fieldErrors);
        }
        return $count;
    }

    /**
     * Get an iterator for the errors.
     *
     * @return Traversable<string, array<string>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->errors;
    }

    /**
     * Check if a field exists.
     *
     * @param string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Get errors for a field.
     *
     * @param string $offset
     * @return array<string>
     */
    public function offsetGet(mixed $offset): array
    {
        return $this->get($offset);
    }

    /**
     * Not supported - collection is immutable.
     *
     * @param string $offset
     * @param array<string> $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('ValidationErrorCollection is immutable');
    }

    /**
     * Not supported - collection is immutable.
     *
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('ValidationErrorCollection is immutable');
    }

    /**
     * Convert to JSON representation.
     *
     * @return array<string, array<string>>
     */
    public function jsonSerialize(): array
    {
        return $this->errors;
    }

    /** Convert to JSON string. */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);
        if (false === $json) {
            throw new RuntimeException('Failed to encode errors to JSON: ' . json_last_error_msg());
        }
        return $json;
    }

    /** Convert to string (returns first error message). */
    public function __toString(): string
    {
        return $this->firstMessage() ?? '';
    }
}
