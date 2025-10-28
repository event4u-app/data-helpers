<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use ArrayAccess;
use Countable;
use event4u\DataHelpers\SimpleDto;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Traversable;

/**
 * Type-safe collection for Dtos.
 *
 * Framework-independent collection that works with plain PHP, Laravel, and Symfony.
 * All methods are type-safe and return Dtos of the specified type.
 *
 * Example:
 *   $users = DataCollection::forDto(UserDto::class, [
 *       ['name' => 'John', 'age' => 30],
 *       ['name' => 'Jane', 'age' => 25],
 *   ]);
 *
 *   $adults = $users->filter(fn(UserDto $user) => $user->age >= 18);
 *   $names = $users->map(fn(UserDto $user) => $user->name);
 *
 * @template TDto of SimpleDto
 * @implements IteratorAggregate<int, TDto>
 * @implements ArrayAccess<int, TDto>
 */
final class DataCollection implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{
    /** @var array<int, TDto> */
    private array $items = [];

    /**
     * @param class-string<TDto> $dtoClass
     * @param array<int|string, mixed> $items
     */
    public function __construct(
        private readonly string $dtoClass,
        array $items = [],
    ) {
        foreach ($items as $item) {
            if (!is_array($item) && !($item instanceof SimpleDto)) {
                throw new InvalidArgumentException(
                    sprintf('Item must be an array or instance of %s, %s given', $dtoClass, get_debug_type($item))
                );
            }

            /** @var array<string, mixed>|TDto $item */
            $this->items[] = $this->ensureDto($item);
        }
    }

    /**
     * Create a new collection instance for a specific Dto class.
     *
     * @param class-string<TDto> $dtoClass
     * @param array<int|string, mixed> $items
     * @return static<TDto>
     */
    public static function forDto(string $dtoClass, array $items = []): static
    {
        return new self($dtoClass, $items);
    }

    /**
     * Create a new collection instance (alias for forDto).
     *
     * This method provides a more intuitive API for creating collections.
     *
     * @param array<int|string, mixed> $items
     * @param class-string<TDto> $dtoClass
     * @return static<TDto>
     */
    public static function make(array $items, string $dtoClass): static
    {
        return new self($dtoClass, $items);
    }

    /**
     * Get the Dto class for this collection.
     *
     * @return class-string<TDto>
     */
    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }

    /**
     * Filter items by a given callback.
     *
     * @param callable(TDto, int): bool|null $callback
     * @return static<TDto>
     */
    public function filter(?callable $callback = null): static
    {
        $filtered = [];

        if (null === $callback) {
            foreach ($this->items as $item) {
                if ($item) {
                    $filtered[] = $item;
                }
            }
        } else {
            foreach ($this->items as $key => $item) {
                if ($callback($item, $key)) {
                    $filtered[] = $item;
                }
            }
        }

        return new self($this->dtoClass, $filtered);
    }

    /**
     * Map over each item in the collection.
     *
     * @template TMapValue
     * @param callable(TDto, int): TMapValue $callback
     * @return array<int, TMapValue>
     */
    public function map(callable $callback): array
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[] = $callback($value, $key);
        }

        return $items;
    }

    /**
     * Get the first item from the collection.
     *
     * @param (callable(TDto, int): bool)|null $callback
     * @param TDto|null $default
     * @return TDto|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if (null === $callback) {
            return $this->items[0] ?? $default;
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
     * @param (callable(TDto, int): bool)|null $callback
     * @param TDto|null $default
     * @return TDto|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if (null === $callback) {
            return $this->items[count($this->items) - 1] ?? $default;
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
     * @param callable(TReduceInitial|TReduceReturnType, TDto, int): TReduceReturnType $callback
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
     * Get all items as Dtos.
     *
     * @return array<int, TDto>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Phase 6: Lazy iteration using Generator for memory efficiency.
     *
     * Use this for large datasets (10k+ items) to avoid loading all items into memory.
     *
     * Example:
     *   foreach ($collection->lazy() as $dto) {
     *       // Process one item at a time
     *   }
     *
     * @return Generator<int, TDto>
     */
    public function lazy(): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * Phase 6: Lazy filter using Generator for memory efficiency.
     *
     * @param callable(TDto, int): bool $callback
     * @return Generator<int, TDto>
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
     * Phase 6: Lazy map using Generator for memory efficiency.
     *
     * @template TMapValue
     * @param callable(TDto, int): TMapValue $callback
     * @return Generator<int, TMapValue>
     */
    public function lazyMap(callable $callback): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $callback($item, $key);
        }
    }

    /**
     * Convert all Dtos to arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        // Phase 6 Optimization #5: Use foreach instead of array_map (faster, less memory)
        $result = [];
        foreach ($this->items as $dto) {
            $result[] = $dto->toArray();
        }
        return $result;
    }

    /** Convert all Dtos to JSON. */
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
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @param TDto|array<string, mixed> ...$values
     * @return $this
     */
    public function push(...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $this->ensureDto($value);
        }

        return $this;
    }

    /**
     * Prepend one or more items to the beginning of the collection.
     *
     * @param TDto|array<string, mixed> $value
     * @return $this
     */
    public function prepend(mixed $value): static
    {
        array_unshift($this->items, $this->ensureDto($value));

        return $this;
    }

    /**
     * Get an item by index.
     *
     * @return TDto|null
     */
    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Ensure the value is a Dto instance.
     *
     * @param TDto|array<string, mixed> $value
     * @return TDto
     */
    private function ensureDto(mixed $value): mixed
    {
        if ($value instanceof $this->dtoClass) {
            return $value;
        }

        if (is_array($value)) {
            return $this->dtoClass::fromArray($value);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Value must be an instance of %s or an array, %s given',
                $this->dtoClass,
                get_debug_type($value)
            )
        );
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param class-string<TDto> $dtoClass
     * @param array<int|string, mixed>|static<TDto> $items
     * @return static<TDto>
     */
    public static function wrapDto(string $dtoClass, mixed $items = []): static
    {
        if ($items instanceof static && $items->getDtoClass() === $dtoClass) {
            return $items;
        }

        return new self($dtoClass, is_array($items) ? $items : [$items]);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<int, TDto>
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
     * Get an iterator for the items.
     *
     * @return Traversable<int, TDto>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $item) {
            yield $item;
        }
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param int $offset
     * @return TDto|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the item at a given offset.
     *
     * @param int|null $offset
     * @param TDto|array<string, mixed> $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $dto = $this->ensureDto($value);

        if (null === $offset) {
            $this->items[] = $dto;
        } else {
            $this->items[$offset] = $dto;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
