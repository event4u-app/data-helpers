<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Optional wrapper for distinguishing between null and "not set" values.
 *
 * This class wraps a value that may or may not be present, allowing you to
 * distinguish between:
 * - A value that is explicitly set to null
 * - A value that was not provided at all (missing)
 *
 * This is particularly useful for:
 * - Partial updates (PATCH requests) where you only want to update provided fields
 * - API consistency where null and missing have different meanings
 * - Default values where you want to distinguish between "use null" and "use default"
 *
 * @template T
 *
 * @example Basic usage
 * ```php
 * // Missing value
 * $optional = Optional::empty();
 * $optional->isEmpty();    // true
 * $optional->isPresent();  // false
 *
 * // Present value
 * $optional = Optional::of('value');
 * $optional->isPresent();  // true
 * $optional->get();        // 'value'
 *
 * // Null value (explicitly set)
 * $optional = Optional::of(null);
 * $optional->isPresent();  // true
 * $optional->get();        // null
 * ```
 *
 * @example With DTOs
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly Optional|string $email,  // Can be missing
 *         public readonly ?string $phone,          // Can be null
 *     ) {}
 * }
 *
 * // Missing email
 * $user = UserDTO::fromArray(['name' => 'John', 'phone' => null]);
 * $user->email->isEmpty();     // true
 * $user->email->isPresent();   // false
 * $user->phone;                // null (explicitly set)
 * ```
 *
 * @example Partial updates
 * ```php
 * $updates = UserDTO::fromArray(['email' => 'new@example.com'])->partial();
 * $model->update($updates); // Only updates email, leaves other fields unchanged
 * ```
 */
final readonly class Optional
{
    /**
     * @param T|null $value The wrapped value
     * @param bool $present Whether the value is present
     */
    private function __construct(
        private mixed $value,
        private bool $present,
    ) {}

    /**
     * Create an Optional with a present value.
     *
     * @template U
     * @param U $value The value to wrap (can be null)
     * @return self<U>
     */
    public static function of(mixed $value): self
    {
        return new self($value, true);
    }

    /**
     * Create an empty Optional (value not present).
     *
     * @return self<null>
     */
    public static function empty(): self
    {
        return new self(null, false);
    }

    /**
     * Check if the value is present.
     *
     * Returns true even if the value is null, as long as it was explicitly set.
     *
     * @return bool
     */
    public function isPresent(): bool
    {
        return $this->present;
    }

    /**
     * Check if the value is empty (not present).
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->present;
    }

    /**
     * Get the value if present, otherwise return the default.
     *
     * @param T|null $default The default value to return if empty
     * @return T|null
     */
    public function get(mixed $default = null): mixed
    {
        return $this->present ? $this->value : $default;
    }

    /**
     * Get the value or else return the provided default.
     *
     * Alias for get() for better readability.
     *
     * @param T|null $default The default value to return if empty
     * @return T|null
     */
    public function orElse(mixed $default): mixed
    {
        return $this->get($default);
    }

    /**
     * Transform the value if present.
     *
     * @template U
     * @param callable(T): U $callback The transformation function
     * @return self<U>
     */
    public function map(callable $callback): self
    {
        if (!$this->present || null === $this->value) {
            // @phpstan-ignore return.type (Generic type limitation)
            return self::empty();
        }

        // @phpstan-ignore return.type (Generic type limitation)
        return self::of($callback($this->value));
    }

    /**
     * Execute a callback if the value is present.
     *
     * @param callable(T): void $callback The callback to execute
     * @return self<T>
     */
    public function ifPresent(callable $callback): self
    {
        if ($this->present && null !== $this->value) {
            $callback($this->value);
        }

        return $this;
    }

    /**
     * Execute a callback if the value is empty.
     *
     * @param callable(): void $callback The callback to execute
     * @return self<T>
     */
    public function ifEmpty(callable $callback): self
    {
        if (!$this->present) {
            $callback();
        }

        return $this;
    }

    /**
     * Filter the value based on a predicate.
     *
     * Returns empty Optional if the value doesn't match the predicate.
     *
     * @param callable(T): bool $predicate The predicate function
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        if (!$this->present || null === $this->value) {
            return $this;
        }

        // @phpstan-ignore return.type (Generic type limitation)
        return $predicate($this->value) ? $this : self::empty();
    }

    /**
     * Get the value or throw an exception if empty.
     *
     * @param string $message The exception message
     * @return T
     * @throws \RuntimeException If the value is empty
     */
    public function orThrow(string $message = 'No value present'): mixed
    {
        if (!$this->present || null === $this->value) {
            throw new \RuntimeException($message);
        }

        return $this->value;
    }

    /**
     * Convert to array representation.
     *
     * @return array{present: bool, value: T|null}
     */
    public function toArray(): array
    {
        return [
            'present' => $this->present,
            'value' => $this->value,
        ];
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->present) {
            return 'Optional.empty';
        }

        if ($this->value === null) {
            return 'Optional[null]';
        }

        if (is_scalar($this->value)) {
            return 'Optional[' . $this->value . ']';
        }

        if (is_object($this->value)) {
            return 'Optional[' . get_class($this->value) . ']';
        }

        return 'Optional[' . gettype($this->value) . ']';
    }
}

