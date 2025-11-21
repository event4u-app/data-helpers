<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use ArrayAccess;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Traversable;
use event4u\DataHelpers\Helpers\DotPathHelper;


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
 * @phpstan-consistent-constructor
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
     */
    public function filter(?callable $callback = null): static
    {
        /** @var array<int|string, TValue> $filtered */
        $filtered = $this->accessor->filter($callback);

        return new static($filtered); // @phpstan-ignore return.type
    }

    /**
     * Map over each item in the collection.
     *
     * Delegates to DataAccessor for mapping logic.
     *
     * @template TMapValue
     * @param callable(TValue, int|string): TMapValue $callback
     * @phpstan-ignore argument.type, return.type
     */
    public function map(callable $callback): static
    {
        /** @var array<int|string, TMapValue> $mapped */
        $mapped = $this->accessor->map($callback); // @phpstan-ignore argument.type

        return new static($mapped); // @phpstan-ignore return.type
    }

    /**
     * Pluck a value from each item in the collection.
     *
     * Similar to Laravel's pluck(): supports dot notation and optional key path.
     *
     * @template TPluckValue
     * @param string|int $valuePath Dot-notation path or key to extract from each item
     * @param string|int|null $keyPath Optional dot-notation path or key to use for result keys
     * @return static<TPluckValue>
     * @phpstan-ignore return.type
     */
    public function pluck(string|int $valuePath, string|int|null $keyPath = null): static
    {
        $valuePathString = (string) $valuePath;
        $keyPathString = null !== $keyPath ? (string) $keyPath : null;

        $result = [];

        foreach ($this->items as $itemKey => $item) {
            $value = DataAccessor::make($item)->get($valuePathString);

            if (null === $keyPathString) {
                $result[] = $value;

                continue;
            }

            $key = DataAccessor::make($item)->get($keyPathString);

            if (is_int($key) || is_string($key)) {
                $result[$key] = $value;

                continue;
            }

            $result[$itemKey] = $value;
        }

        return new static($result); // @phpstan-ignore return.type
    }

    /**
     * Key the collection by the given key or callback.
     *
     * Similar to Laravel's keyBy(): supports dot-notation strings or a callback.
     * When multiple items have the same key, the last one wins.
     *
     * @param (callable(TValue, int|string): int|string|null)|string|int $key
     * @return static<TValue>
     * @phpstan-ignore return.type
     */
    public function keyBy(callable|string|int $key): static
    {
        $result = [];

        foreach ($this->items as $itemKey => $item) {
            if (is_callable($key)) {
                $resolvedKey = $key($item, $itemKey);
            } else {
                $resolvedKey = DataAccessor::make($item)->get((string) $key);
            }

            if (!is_int($resolvedKey) && !is_string($resolvedKey)) {
                $resolvedKey = $itemKey;
            }

            $result[$resolvedKey] = $item;
        }

        return new static($result); // @phpstan-ignore return.type
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
     * Get the item after the given item in the collection.
     *
     * Similar to Laravel's after(): finds the next item after a given value or predicate.
     *
     * @param (callable(TValue, int|string): bool)|TValue $valueOrCallback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function after(mixed $valueOrCallback, mixed $default = null, bool $strict = false): mixed
    {
        $found = false;

        foreach ($this->items as $key => $item) {
            if ($found) {
                return $item;
            }

            if ($valueOrCallback instanceof Closure) {
                if ($valueOrCallback($item, $key)) {
                    $found = true;
                }

                continue;
            }

            if ($strict ? $item === $valueOrCallback : $item == $valueOrCallback) { // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
                $found = true;
            }
        }

        return $default;
    }

    /**
     * Get the item before the given item in the collection.
     *
     * Similar to Laravel's before(): finds the previous item before a given value or predicate.
     *
     * @param (callable(TValue, int|string): bool)|TValue $valueOrCallback
     * @param TValue|null $default
     * @return TValue|null
     */
    public function before(mixed $valueOrCallback, mixed $default = null, bool $strict = false): mixed
    {
        $previous = null;
        $hasPrevious = false;

        foreach ($this->items as $key => $item) {
            if ($valueOrCallback instanceof Closure) {
                if ($valueOrCallback($item, $key)) {
                    return $hasPrevious ? $previous : $default;
                }

                $previous = $item;
                $hasPrevious = true;

                continue;
            }

            if ($strict ? $item === $valueOrCallback : $item == $valueOrCallback) { // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
                return $hasPrevious ? $previous : $default;
            }

            $previous = $item;
            $hasPrevious = true;
        }

        return $default;
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
        // @phpstan-ignore argument.type (DataAccessor accepts mixed, we provide TValue)
        return $this->accessor->reduce($callback, $initial);
    }

    /**
     * Calculate the average (mean) of the collection values.
     *
     * If a string is given, it is treated as a path and resolved via DataAccessor.
     * If a callable is given, it receives the item and key and should return a numeric value.
     * Non-numeric values are ignored.
     *
     * @param callable(TValue, int|string): (int|float|string)|string|null $callbackOrPath
     */
    public function average(callable|string|null $callbackOrPath = null): ?float
    {
        $sum = 0.0;
        $count = 0;

        foreach ($this->items as $key => $item) {
            $value = $item;

            if (is_string($callbackOrPath)) {
                $value = DataAccessor::make($item)->get($callbackOrPath);
            } elseif (null !== $callbackOrPath) {
                $value = $callbackOrPath($item, $key);
            }

            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $sum += (float) $value;
                ++$count;
            }
        }

        if (0 === $count) {
            return null;
        }

        return $sum / $count;
    }

    public function avg(callable|string|null $callbackOrPath = null): ?float
    {
        return $this->average($callbackOrPath);
    }

    /**
     * Get the maximum value of the given items.
     *
     * Works similar to Laravel's max():
     * - Without argument: compares the raw items directly.
     * - With string path: uses DataAccessor and supports dot-notation.
     * - With callback: compares the return value of the callback.
     *
     * Non-comparable (null) values are ignored. If no comparable
     * values are found, null is returned.
     *
     * @param (callable(TValue, int|string): mixed)|string|null $callbackOrPath
     * @return TValue|mixed|null
     */
    public function max(callable|string|null $callbackOrPath = null): mixed
    {
        $hasValue = false;
        $maxValue = null;
        $maxItem = null;

        foreach ($this->items as $key => $item) {
            $value = $item;

            if (is_string($callbackOrPath)) {
                $value = DataAccessor::make($item)->get($callbackOrPath);
            } elseif (null !== $callbackOrPath) {
                $value = $callbackOrPath($item, $key);
            }

            if (null === $value) {
                continue;
            }

            if (!$hasValue || $value > $maxValue) {
                $hasValue = true;
                $maxValue = $value;
                $maxItem = $item;
            }
        }

        return $hasValue ? $maxItem : null;
    }
    /**
     * Get the minimum value of the given items.
     *
     * Works similar to Laravel's min():
     * - Without argument: compares the raw items directly.
     * - With string path: uses DataAccessor and supports dot-notation.
     * - With callback: compares the return value of the callback.
     *
     * Non-comparable (null) values are ignored. If no comparable
     * values are found, null is returned.
     *
     * @param (callable(TValue, int|string): mixed)|string|null $callbackOrPath
     * @return TValue|mixed|null
     */
    public function min(callable|string|null $callbackOrPath = null): mixed
    {
        $hasValue = false;
        $minValue = null;
        $minItem = null;

        foreach ($this->items as $key => $item) {
            $value = $item;

            if (is_string($callbackOrPath)) {
                $value = DataAccessor::make($item)->get($callbackOrPath);
            } elseif (null !== $callbackOrPath) {
                $value = $callbackOrPath($item, $key);
            }

            if (null === $value) {
                continue;
            }

            if (!$hasValue || $value < $minValue) {
                $hasValue = true;
                $minValue = $value;
                $minItem = $item;
            }
        }

        return $hasValue ? $minItem : null;
    }



    /**
     * Calculate the median of the collection values.
     *
     * Follows the same resolution rules as average():
     * - Without argument: uses the raw items.
     * - With string path: uses DataAccessor and supports dot-notation.
     * - With callback: uses the callback return value.
     *
     * Non-numeric values are ignored. If no numeric values are found,
     * null is returned.
     *
     * @param callable(TValue, int|string): (int|float|string)|string|null $callbackOrPath
     */
    public function median(callable|string|null $callbackOrPath = null): ?float
    {
        $values = [];

        foreach ($this->items as $key => $item) {
            $value = $item;

            if (is_string($callbackOrPath)) {
                $value = DataAccessor::make($item)->get($callbackOrPath);
            } elseif (null !== $callbackOrPath) {
                $value = $callbackOrPath($item, $key);
            }

            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $values[] = (float) $value;
            }
        }

        $count = count($values);

        if (0 === $count) {
            return null;
        }

        sort($values);

        $middle = intdiv($count, 2);

        if (1 === $count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
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

    /**
     * Collapse a collection of arrays/Traversables into a single flat array (depth 1).
     *
     * Non-array/non-Traversable items are kept as-is.
     *
     * @return static<mixed>
     * @phpstan-ignore return.type
     */
    public function collapse(): static
    {
        $results = [];

        foreach ($this->items as $item) {
            if (is_array($item) || $item instanceof Traversable) {
                foreach ($item as $value) {
                    $results[] = $value;
                }

                continue;
            }

            $results[] = $item;
        }

        return new static($results); // @phpstan-ignore return.type
    }

    /**
     * Flatten a nested array or collection into a single level using dot-notation keys.
     *
     * This behaves similar to Laravel's Arr::dot():
     *
     *   DataCollection::make([
     *       'user' => [
     *           'name' => 'Alice',
     *           'address' => ['city' => 'Berlin'],
     *       ],
     *   ])->flatten();
     *
     * produces:
     *
     *   [
     *       'user.name' => 'Alice',
     *       'user.address.city' => 'Berlin',
     *   ]
     *
     * @return static<mixed>
     * @phpstan-ignore return.type
     */
    public function flatten(): static
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $this->flattenItem($result, (string) $key, $value);
        }

        return new static($result); // @phpstan-ignore return.type
    }

    /**
     * Recursively flatten an item into the result array using dot-notation keys.
     *
     * @param array<string, mixed> $result
     */
    private function flattenItem(array &$result, string $prefix, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $nested) {
                $path = DotPathHelper::buildPrefix($prefix, $key);
                $this->flattenItem($result, $path, $nested);
            }

            return;
        }

        if ($value instanceof self) {
            foreach ($value->items as $key => $nested) {
                $path = DotPathHelper::buildPrefix($prefix, $key);
                $this->flattenItem($result, $path, $nested);
            }

            return;
        }

        $result[$prefix] = $value;
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * Similar to Laravel's diff(): compares values using loose comparison and
     * preserves the original keys from the collection.
     *
     * @param iterable<int|string, TValue> $items
     * @return static<TValue>
     * @phpstan-ignore return.type
     */
    public function diff(iterable $items): static
    {
        $otherItems = [];

        foreach ($items as $item) {
            $otherItems[] = $item;
        }

        $result = [];

        foreach ($this->items as $key => $value) {
            if (!in_array($value, $otherItems, false)) { // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
                $result[$key] = $value;
            }
        }

        return new static($result); // @phpstan-ignore return.type
    }


    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * Mirrors Laravel's diffKeys(): compares keys and preserves the original
     * values from the collection.
     *
     * @param iterable<int|string, mixed> $items
     * @return static<TValue>
     * @phpstan-ignore return.type
     */
    public function diffKeys(iterable $items): static
    {
        $otherKeys = [];

        foreach ($items as $key => $_value) {
            $otherKeys[] = $key;
        }

        $result = [];

        foreach ($this->items as $key => $value) {
            if (!in_array($key, $otherKeys, true)) {
                $result[$key] = $value;
            }
        }

        return new static($result); // @phpstan-ignore return.type
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
     * Set a value in the collection using dot notation.
     *
     * Modifies the collection in-place and returns $this for chaining.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'Alice']],
     *   ]);
     *   $collection->set('0.user.age', 30);
     *   // Collection now contains: [['user' => ['name' => 'Alice', 'age' => 30]]]
     *
     * @param string $path Dot-notation path
     * @param mixed $value Value to set
     * @return $this
     */
    public function set(string $path, mixed $value): static
    {
        DataMutator::make($this->items)->set($path, $value); // @phpstan-ignore assign.propertyType
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Merge values into the collection using dot notation.
     *
     * Modifies the collection in-place and returns $this for chaining.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'Alice']],
     *   ]);
     *   $collection->merge('0.user', ['age' => 30, 'city' => 'Berlin']);
     *   // Collection now contains: [['user' => ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin']]]
     *
     * @param array<string, mixed>|string $pathOrValues Path or array of path => value pairs
     * @param array<int|string, mixed>|null $value Value to merge (if path is string)
     * @return $this
     */
    public function merge(array|string $pathOrValues, ?array $value = null): static
    {
        if (is_array($pathOrValues)) {
            DataMutator::make($this->items)->merge($pathOrValues); // @phpstan-ignore assign.propertyType
        } else {
            DataMutator::make($this->items)->merge($pathOrValues, $value); // @phpstan-ignore assign.propertyType
        }
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Remove a value from the collection using dot notation.
     *
     * Modifies the collection in-place and returns $this for chaining.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'Alice', 'age' => 30]],
     *   ]);
     *   $collection->forget('0.user.age');
     *   // Collection now contains: [['user' => ['name' => 'Alice']]]
     *
     * @param string $path Dot-notation path
     * @return $this
     */
    public function forget(string $path): static
    {
        DataMutator::make($this->items)->unset($path); // @phpstan-ignore assign.propertyType
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Transform a value at the given path using a callback.
     *
     * Modifies the collection in-place and returns $this for chaining.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'alice']],
     *   ]);
     *   $collection->transform('0.user.name', fn($name) => strtoupper($name));
     *   // Collection now contains: [['user' => ['name' => 'ALICE']]]
     *
     * @param string $path Dot-notation path
     * @param callable(mixed): mixed $callback Transformation callback
     * @return $this
     */
    public function transform(string $path, callable $callback): static
    {
        DataMutator::make($this->items)->transform($path, $callback); // @phpstan-ignore assign.propertyType
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Push a value onto an array at the given path.
     *
     * Modifies the collection in-place and returns $this for chaining.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['tags' => ['php']]],
     *   ]);
     *   $collection->pushTo('0.user.tags', 'laravel');
     *   // Collection now contains: [['user' => ['tags' => ['php', 'laravel']]]]
     *
     * @param string $path Dot-notation path to array
     * @param mixed $value Value to push
     * @return $this
     */
    public function pushTo(string $path, mixed $value): static
    {
        DataMutator::make($this->items)->push($path, $value); // @phpstan-ignore assign.propertyType
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Remove and return a value from the collection using dot notation.
     *
     * Modifies the collection in-place and returns the removed value.
     * Uses DataMutator internally for dot-notation support.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['user' => ['name' => 'Alice', 'age' => 30]],
     *   ]);
     *   $age = $collection->pull('0.user.age');  // 30
     *   // Collection now contains: [['user' => ['name' => 'Alice']]]
     *
     * @param string $path Dot-notation path
     * @param mixed $default Default value if path doesn't exist
     */
    public function pull(string $path, mixed $default = null): mixed
    {
        $value = DataMutator::make($this->items)->pull($path, $default); // @phpstan-ignore assign.propertyType
        $this->accessor = new DataAccessor($this->items);

        return $value;
    }

    /**
     * Create a DataFilter query for SQL-like filtering.
     *
     * Returns a wrapper that allows chaining filter operations and returns a new DataCollection.
     *
     * Example:
     *   $collection = DataCollection::make([
     *       ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'],
     *       ['name' => 'Bob', 'age' => 25, 'city' => 'Munich'],
     *       ['name' => 'Charlie', 'age' => 35, 'city' => 'Berlin'],
     *   ]);
     *
     *   $filtered = $collection
     *       ->query()
     *       ->where('age', '>', 25)
     *       ->where('city', 'Berlin')
     *       ->orderBy('age', 'DESC')
     *       ->get();
     *   // Returns new DataCollection with filtered items
     *
     * @return DataFilterWrapper<TValue>
     * @phpstan-ignore return.type
     */
    public function query(): DataFilterWrapper
    {
        return new DataFilterWrapper($this->items); // @phpstan-ignore return.type
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
     * Determine if an item exists in the collection.
     *
     * Supports:
     * - contains(value)
     * - contains(callback(TValue, int|string): bool)
     * - contains(key, value)
     */
    public function contains(mixed $keyOrCallback, mixed $value = null): bool
    {
        // contains(callback)
        if ($keyOrCallback instanceof Closure) {
            foreach ($this->items as $key => $item) {
                if ($keyOrCallback($item, $key)) {
                    return true;
                }
            }

            return false;
        }

        // contains(key, value)
        if (null !== $value) {
            foreach ($this->items as $item) {
                if (!is_array($item) && !$item instanceof ArrayAccess && !is_object($item)) {
                    continue;
                }

                $current = DataAccessor::make($item)->get((string) $keyOrCallback);

                if ($current == $value) { // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
                    return true;
                }
            }

            return false;
        }

        // contains(value)
        foreach ($this->items as $item) {
            if ($item == $keyOrCallback) { // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
                return true;
            }
        }

        return false;
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
     * @phpstan-ignore return.type
     */
    public function lazy(): Generator
    {
        return $this->accessor->lazy(); // @phpstan-ignore return.type
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
        // @phpstan-ignore return.type, argument.type (DataAccessor accepts mixed, we provide TValue)
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
        // @phpstan-ignore return.type, argument.type (DataAccessor accepts mixed, we provide TValue)
        return $this->accessor->lazyMap($callback);
    }

    /**
     * Get the values of the collection.
     *
     * @phpstan-ignore return.type
     */
    public function values(): static
    {
        return new static(array_values($this->items)); // @phpstan-ignore return.type
    }

    /**
     * Get the keys of the collection.
     *
     * @phpstan-ignore return.type
     */
    public function keys(): static
    {
        return new static(array_keys($this->items)); // @phpstan-ignore return.type
    }

    /** Determine if a key exists in the collection. */
    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Determine if at least one of the given keys exists in the collection.
     */
    public function hasAny(int|string ...$keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
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
