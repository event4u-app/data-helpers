<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\SimpleDto;
use InvalidArgumentException;

/**
 * Type-safe collection for Dtos.
 *
 * Framework-independent collection that works with plain PHP, Laravel and Symfony.
 * All methods are type-safe and return Dtos of the specified type.
 *
 * Extends the generic DataCollection class with DTO-specific functionality.
 *
 * Example:
 *   $users = DtoCollection::forDto(UserDto::class, [
 *       ['name' => 'John', 'age' => 30],
 *       ['name' => 'Jane', 'age' => 25],
 *   ]);
 *
 *   $adults = $users->filter(fn(UserDto $user) => $user->age >= 18);
 *   $names = $users->map(fn(UserDto $user) => $user->name);
 *
 * @template TDto of SimpleDto
 * @extends DataCollection<TDto>
 */
final class DtoCollection extends DataCollection
{
    /**
     * @param class-string<TDto>|array<int|string, mixed> $dtoClass
     * @param array<int|string, mixed> $items
     * @phpstan-ignore method.childParameterType, parameter.notOptional
     */
    public function __construct(
        private readonly string|array $dtoClass,
        array $items = [],
    ) {
        // Handle backward compatibility: new static($items) from parent class
        if (is_array($dtoClass)) {
            $items = $dtoClass;
            // Try to infer DTO class from first item
            if ([] !== $items) {
                $firstItem = reset($items);
                if ($firstItem instanceof SimpleDto) {
                    $dtoClass = $firstItem::class;
                } else {
                    throw new InvalidArgumentException(
                        'Cannot create DtoCollection without DTO class. Use DtoCollection::forDto() or DtoCollection::make() instead.'
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    'Cannot create empty DtoCollection without DTO class. Use DtoCollection::forDto() or DtoCollection::make() instead.'
                );
            }
        }

        $dtoItems = [];
        foreach ($items as $key => $item) {
            if (!is_array($item) && !($item instanceof SimpleDto)) {
                throw new InvalidArgumentException(
                    sprintf('Item must be an array or instance of %s, %s given', $dtoClass, get_debug_type($item))
                );
            }

            /** @var array<string, mixed>|TDto $item */
            $dtoItems[$key] = $this->ensureDto($item);
        }

        parent::__construct($dtoItems);
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
     * @param class-string<TDto>|null $dtoClass
     * @return static<TDto>
     */
    public static function make(
        array $items = [],
        ?string $dtoClass = null
    ): static // @phpstan-ignore argument.type
    {
        return new self($dtoClass, $items); // @phpstan-ignore argument.type
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
     * Overrides parent to maintain DTO class information.
     *
     * @param callable(TDto, int|string): bool|null $callback
     * @return static<TDto>
     */
    public function filter(?callable $callback = null): static
    {
        $filtered = $this->accessor->filter($callback);
        return new self($this->dtoClass, $filtered);
    }

    /**
     * Map over each item in the collection.
     *
     * Note: map() on DtoCollection returns the same DTO type.
     * If you need to transform to different types, convert to DataCollection first.
     *
     * @param callable(TDto, int|string): TDto $callback
     * @return static<TDto>
     * @phpstan-ignore return.type
     */
    public function map(callable $callback): static
    {
        $mapped = $this->accessor->map($callback);
        return new self($this->dtoClass, $mapped);
    }

    /**
     * Get the values of the collection.
     *
     * Overrides parent to maintain DTO class information.
     *
     * @return static<TDto>
     */
    public function values(): static
    {
        return new self($this->dtoClass, array_values($this->items));
    }

    /**
     * Convert all Dtos to arrays.
     *
     * Overrides parent to convert DTOs to arrays.
     *
     * @return array<int|string, array<string, mixed>>
     * @phpstan-ignore method.childReturnType
     */
    public function toArray(): array
    {
        // Phase 6 Optimization #5: Use foreach instead of array_map (faster, less memory)
        $result = [];
        foreach ($this->items as $key => $dto) {
            $result[$key] = $dto->toArray();
        }
        return $result;
    }

    /**
     * Convert the collection to its JSON representation.
     *
     * Overrides parent to convert DTOs to arrays first.
     *
     * @return array<int|string, array<string, mixed>>
     * @phpstan-ignore method.childReturnType
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * Overrides parent to ensure items are DTOs.
     *
     * @param TDto|array<string, mixed> ...$values
     * @return $this
     */
    public function push(...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $this->ensureDto($value);
        }

        // Update accessor to reflect changes
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Prepend one or more items to the beginning of the collection.
     *
     * Overrides parent to ensure value is a DTO.
     *
     * @param TDto|array<string, mixed> $value
     * @return $this
     */
    public function prepend(mixed $value): static
    {
        array_unshift($this->items, $this->ensureDto($value));

        // Update accessor to reflect changes
        $this->accessor = new DataAccessor($this->items);

        return $this;
    }

    /**
     * Set the item at a given offset.
     *
     * Overrides parent to ensure value is a DTO.
     *
     * @param int|string|null $offset
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

        // Update accessor to reflect changes
        $this->accessor = new DataAccessor($this->items);
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
}
