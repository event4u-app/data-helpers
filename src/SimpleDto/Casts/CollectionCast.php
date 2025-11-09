<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDto\DtoCollection;
use RuntimeException;

/**
 * Cast attribute to DtoCollection (framework-independent).
 *
 * Supports:
 * - DtoCollection (event4u\DataHelpers\SimpleDto\DtoCollection)
 * - Typed collections (collections of Dtos)
 * - Nested Dtos
 * - Null values
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection',                    // DataCollection
 *           'users' => 'collection:App\Dtos\UserDto',   // DataCollection<UserDto>
 *       ];
 *   }
 */
class CollectionCast implements CastsAttributes
{
    /** @param string|null $dtoClass Optional Dto class for typed collections */
    public function __construct(private readonly ?string $dtoClass = null)
    {
    }

    /**
     * @return DtoCollection|null
     * @phpstan-ignore missingType.generics
     */
    public function get(mixed $value, array $attributes): ?DtoCollection
    {
        if (null === $value) {
            return null;
        }

        // If already a DtoCollection, return it
        if ($value instanceof DtoCollection) {
            return $value;
        }

        // Convert to array if needed
        if (!is_array($value)) {
            $value = [$value];
        }

        // If Dto class is specified, create typed DtoCollection
        if (null !== $this->dtoClass && class_exists($this->dtoClass)) {
            /** @var class-string<SimpleDto> $dtoClass */
            $dtoClass = $this->dtoClass;
            return DtoCollection::forDto($dtoClass, $value);
        }

        // Create generic DtoCollection (without Dto type)
        // This is not ideal, but we need a Dto class for DtoCollection
        throw new RuntimeException(
            'CollectionCast requires a Dto class. Use "collection:App\Dtos\UserDto" instead of "collection".'
        );
    }

    /** @return array<array-key, mixed>|null */
    public function set(mixed $value, array $attributes): ?array
    {
        if (null === $value) {
            return null;
        }

        // Convert DtoCollection to array
        if ($value instanceof DtoCollection) {
            return $value->toArray();
        }

        // If it's already an array, return it
        if (is_array($value)) {
            // If items are Dtos, convert them to arrays
            return array_map(function(mixed $item): mixed {
                // Check if item has toArray method (duck typing for Dtos)
                if (is_object($item) && method_exists($item, 'toArray')) {
                    return $item->toArray();
                }

                return $item;
            }, $value);
        }

        return null;
    }
}
