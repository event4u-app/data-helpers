<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use ArrayAccess;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Traversable;

/**
 * Generic type-safe collection for any type of items.
 *
 * Framework-independent collection that works with plain PHP, Laravel and Symfony.
 * Similar to Laravel Collection but without framework dependencies.
 *
 * Example:
 *   $numbers = DataCollection::make([1, 2, 3, 4, 5]);
 *   $filtered = $numbers->filter(fn($n) => $n > 2);
 *   $mapped = $numbers->map(fn($n) => $n * 2);
 *
 * @template TValue
 * @implements IteratorAggregate<int|string, TValue>
 * @implements ArrayAccess<int|string, TValue>
 */
class DataCollection implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{
    /** @param array<int|string, TValue> $items */
    public function __construct(protected array $items = [])
    {
    }

    /**
     * Create a new collection instance.
     *
     * @template TMakeValue
     * @param array<int|string, TMakeValue> $items
     * @return static<TMakeValue>
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TWrapValue
     * @param array<int|string, TWrapValue>|static<TWrapValue> $items
     * @return static<TWrapValue>
     */
    public static function wrap(mixed $items = []): static
    {
        if ($items instanceof static) {
            return $items;
        }

        return new static(is_array($items) ? $items : [$items]);
    }

    /**
     * Filter items by a given callback.
     *
     * @param callable(TValue, int|string): bool|null $callback
     * @return static<TValue>
     */
    public function filter(?callable $callback = null): static
    {
        $filtered = [];

        if (null === $callback) {
            foreach ($this->items as $key => $item) {
                if ($item) {
                    $filtered[$key] = $item;
                }
            }
        } else {
            foreach ($this->items as $key => $item) {
                if ($callback($item, $key)) {
                    $filtered[$key] = $item;
                }
            }
        }

        return new static($filtered);
    }

    /**
     * Map over each item in the collection.
     *
     * @template TMapValue
     * @param callable(TValue, int|string): TMapValue $callback
     * @return static<TMapValue>
     */
    public function map(callable $callback): static
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[$key] = $callback($value, $key);
        }

        return new static($items);
    }

    /**
     * Get the first item from the collection.
     *
     * @param (callable(TValue, int|string): bool)|null $callback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if (null === $callback) {
            foreach ($this->items as $item) {
                return $item;
            }
            return $default;
        }

        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Get the last item from the collection.
     *
     * @param (callable(TValue, int|string): bool)|null $callback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if (null === $callback) {
            $items = array_reverse($this->items, true);
            foreach ($items as $item) {
                return $item;
            }
            return $default;
        }

        $items = array_reverse($this->items, true);
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Reduce the collection to a single value.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     * @param callable(TReduceInitial|TReduceReturnType, TValue, int|string): TReduceReturnType $callback
     * @param TReduceInitial $initial
     * @return TReduceInitial|TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;

        foreach ($this->items as $key => $item) {
            $carry = $callback($carry, $item, $key);
        }

        return $carry;
    }

    /**
     * Get all items.
     *
     * @return array<int|string, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the values as a plain array (alias for all()).
     *
     * @return array<int|string, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /** Convert the collection to JSON. */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);
        if (false === $json) {
            throw new RuntimeException('Failed to encode collection to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert the collection to its JSON representation.
     *
     * @return array<int|string, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @param TValue ...$values
     * @return $this
     */
    public function push(...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Prepend one or more items to the beginning of the collection.
     *
     * @param TValue $value
     * @return $this
     */
    public function prepend(mixed $value): static
    {
        array_unshift($this->items, $value);

        return $this;
    }

    /**
     * Get an item by key.
     *
     * @param TValue|null $default
     * @return TValue|null
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<int|string, TValue>
     */
    public function items(): array
    {
        return $this->items;
    }

    /** Count the number of items in the collection. */
    public function count(): int
    {
        return count($this->items);
    }

    /** Determine if the collection is empty or not. */
    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /** Determine if the collection is not empty. */
    public function isNotEmpty(): bool
    {
        return [] !== $this->items;
    }

    /**
     * Lazy iteration using Generator for memory efficiency.
     *
     * Use this for large datasets (10k+ items) to avoid loading all items into memory.
     *
     * Example:
     *   foreach ($collection->lazy() as $item) {
     *       // Process one item at a time
     *   }
     *
     * @return Generator<int|string, TValue>
     */
    public function lazy(): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * Lazy filter using Generator for memory efficiency.
     *
     * @param callable(TValue, int|string): bool $callback
     * @return Generator<int|string, TValue>
     */
    public function lazyFilter(callable $callback): Generator
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                yield $key => $item;
            }
        }
    }

    /**
     * Lazy map using Generator for memory efficiency.
     *
     * @template TMapValue
     * @param callable(TValue, int|string): TMapValue $callback
     * @return Generator<int|string, TMapValue>
     */
    public function lazyMap(callable $callback): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $callback($item, $key);
        }
    }

    /**
     * Get the values of the collection.
     *
     * @return static<TValue>
     */
    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /**
     * Get the keys of the collection.
     *
     * @return static<int|string>
     */
    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    /** Determine if a key exists in the collection. */
    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an iterator for the items.
     *
     * @return Traversable<int|string, TValue>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param int|string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param int|string $offset
     * @return TValue|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the item at a given offset.
     *
     * @param int|string|null $offset
     * @param TValue $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param int|string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
