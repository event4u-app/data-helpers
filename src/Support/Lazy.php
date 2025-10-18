<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

/**
 * Lazy wrapper for deferred value loading.
 *
 * This class wraps a value that should not be loaded/computed until explicitly requested.
 * Lazy properties are not included in toArray() or JSON serialization by default.
 *
 * This is useful for:
 * - Large data fields (e.g., base64-encoded images, large text)
 * - Expensive computations or database queries
 * - Sensitive data that should only be loaded when needed
 * - Performance optimization
 *
 * @template T
 *
 * @example Basic usage
 * ```php
 * // Lazy value
 * $lazy = Lazy::of(fn() => expensiveComputation());
 * $lazy->isLoaded();  // false
 * $lazy->get();       // Executes expensiveComputation() and caches result
 * $lazy->isLoaded();  // true
 *
 * // Already loaded value
 * $lazy = Lazy::value('already loaded');
 * $lazy->isLoaded();  // true
 * $lazy->get();       // 'already loaded'
 * ```
 *
 * @example With DTOs
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly Lazy|string $biography,  // Union type syntax
 *     ) {}
 * }
 *
 * // Biography not loaded by default
 * $user = UserDTO::fromArray(['name' => 'John', 'biography' => 'Long text...']);
 * $user->toArray(); // ['name' => 'John'] - biography excluded
 *
 * // Include lazy property explicitly
 * $user->include(['biography'])->toArray();
 * // ['name' => 'John', 'biography' => 'Long text...']
 * ```
 *
 * @example Combination with Optional
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly Optional|Lazy|string $bio,  // Can be missing AND lazy!
 *     ) {}
 * }
 * ```
 */
final class Lazy
{
    /**
     * @var T|null
     */
    private mixed $value = null;

    /**
     * @var bool
     */
    private bool $loaded = false;

    /**
     * @param callable(): T|null $loader The loader function
     */
    private function __construct(
        private mixed $loader,
    ) {}

    /**
     * Create a Lazy with a loader function.
     *
     * @template U
     * @param callable(): U $loader The loader function
     * @return self<U>
     */
    public static function of(callable $loader): self
    {
        return new self($loader);
    }

    /**
     * Create a Lazy with an already loaded value.
     *
     * @template U
     * @param U $value The value
     * @return self<U>
     */
    public static function value(mixed $value): self
    {
        $lazy = new self(fn() => $value);
        $lazy->value = $value;
        $lazy->loaded = true;

        return $lazy;
    }

    /**
     * Check if the value has been loaded.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get the value, loading it if necessary.
     *
     * @return T
     */
    public function get(): mixed
    {
        if (!$this->loaded) {
            $this->value = ($this->loader)();
            $this->loaded = true;
        }

        return $this->value;
    }

    /**
     * Transform the value if loaded, or the loader if not.
     *
     * @template U
     * @param callable(T): U $callback The transformation function
     * @return self<U>
     */
    public function map(callable $callback): self
    {
        if ($this->loaded) {
            return self::value($callback($this->value));
        }

        return self::of(fn() => $callback($this->get()));
    }

    /**
     * Execute a callback if the value is loaded.
     *
     * @param callable(T): void $callback The callback to execute
     * @return self<T>
     */
    public function ifLoaded(callable $callback): self
    {
        if ($this->loaded) {
            $callback($this->value);
        }

        return $this;
    }

    /**
     * Execute a callback if the value is not loaded.
     *
     * @param callable(): void $callback The callback to execute
     * @return self<T>
     */
    public function ifNotLoaded(callable $callback): self
    {
        if (!$this->loaded) {
            $callback();
        }

        return $this;
    }

    /**
     * Force load the value.
     *
     * @return self<T>
     */
    public function load(): self
    {
        $this->get();

        return $this;
    }

    /**
     * Convert to array representation.
     *
     * @return array{loaded: bool, value: T|null}
     */
    public function toArray(): array
    {
        return [
            'loaded' => $this->loaded,
            'value' => $this->loaded ? $this->value : null,
        ];
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->loaded) {
            return 'Lazy[not loaded]';
        }

        if ($this->value === null) {
            return 'Lazy[null]';
        }

        if (is_scalar($this->value)) {
            return sprintf('Lazy[%s]', $this->value);
        }

        if (is_object($this->value)) {
            return sprintf('Lazy[%s]', get_class($this->value));
        }

        return sprintf('Lazy[%s]', gettype($this->value));
    }
}

