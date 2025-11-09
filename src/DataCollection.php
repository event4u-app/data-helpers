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
 * This class acts as a container and delegates data access operations to DataAccessor.
 * This allows using dot-notation and all DataAccessor features within collections.
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
    protected DataAccessor $accessor;

    /** @param array<int|string, TValue> $items */
    public function __construct(protected array $items = [])
    {
        $this->accessor = new DataAccessor($items);
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
     * Get an item from the collection using dot notation or direct key access.
     *
     * First checks if the key exists directly in the collection.
     * If not, delegates to DataAccessor for dot-notation access.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'Alice', 'age' => 30]],
     *       ['user' => ['name' => 'Bob', 'age' => 25]],
     *   ]);
     *   $name = $collection->get('0.user.name'); // 'Alice'
     *
     * @param string|int $path Dot-notation path or direct key
     * @param mixed $default Default value if path not found
     * @return mixed
     */
    public function get(string|int $path, mixed $default = null): mixed
    {
        // First check if key exists directly (handles keys with dots like 'key.with.dots')
        if (array_key_exists($path, $this->items)) {
            return $this->items[$path];
        }

        // Otherwise use DataAccessor for dot-notation
        return $this->accessor->get((string)$path, $default);
    }

    /**
     * Filter items by a given callback.
     *
     * Delegates to DataAccessor for filtering logic.
     *
     * @param callable(TValue, int|string): bool|null $callback
     * @return static<TValue>
     */
    public function filter(?callable $callback = null): static
    {
        $filtered = $this->accessor->filter($callback);
        return new static($filtered);
    }

    /**
     * Map over each item in the collection.
     *
     * Delegates to DataAccessor for mapping logic.
     *
     * @template TMapValue
     * @param callable(TValue, int|string): TMapValue $callback
     * @return static<TMapValue>
     */
    public function map(callable $callback): static
    {
        $mapped = $this->accessor->map($callback);
        return new static($mapped);
    }

    /**
     * Get the first item from the collection.
     *
     * Delegates to DataAccessor for first() logic.
     *
     * @param (callable(TValue, int|string): bool)|null $callback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return $this->accessor->first($callback, $default);
    }

    /**
     * Get the last item from the collection.
     *
     * Delegates to DataAccessor for last() logic.
     *
     * @param (callable(TValue, int|string): bool)|null $callback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return $this->accessor->last($callback, $default);
    }

    /**
     * Reduce the collection to a single value.
     *
     * Delegates to DataAccessor for reduce logic.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     * @param callable(TReduceInitial|TReduceReturnType, TValue, int|string): TReduceReturnType $callback
     * @param TReduceInitial $initial
     * @return TReduceInitial|TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return $this->accessor->reduce($callback, $initial);
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

        // Update accessor with new items
        $this->accessor = new DataAccessor($this->items);

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

        // Update accessor with new items
        $this->accessor = new DataAccessor($this->items);

        return $this;
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
     * Delegates to DataAccessor for lazy iteration.
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
        return $this->accessor->lazy();
    }

    /**
     * Lazy filter using Generator for memory efficiency.
     *
     * Delegates to DataAccessor for lazy filtering.
     *
     * @param callable(TValue, int|string): bool $callback
     * @return Generator<int|string, TValue>
     */
    public function lazyFilter(callable $callback): Generator
    {
        return $this->accessor->lazyFilter($callback);
    }

    /**
     * Lazy map using Generator for memory efficiency.
     *
     * Delegates to DataAccessor for lazy mapping.
     *
     * @template TMapValue
     * @param callable(TValue, int|string): TMapValue $callback
     * @return Generator<int|string, TMapValue>
     */
    public function lazyMap(callable $callback): Generator
    {
        return $this->accessor->lazyMap($callback);
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

        // Update accessor with new items
        $this->accessor = new DataAccessor($this->items);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param int|string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);

        // Update accessor with new items
        $this->accessor = new DataAccessor($this->items);
    }
}
