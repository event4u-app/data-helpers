<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDTO\DataCollection;
use RuntimeException;

/**
 * Cast attribute to DataCollection (framework-independent).
 *
 * Supports:
 * - DataCollection (event4u\DataHelpers\SimpleDTO\DataCollection)
 * - Typed collections (collections of DTOs)
 * - Nested DTOs
 * - Null values
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection',                    // DataCollection
 *           'users' => 'collection:App\DTOs\UserDTO',   // DataCollection<UserDTO>
 *       ];
 *   }
 */
class CollectionCast implements CastsAttributes
{
    /** @param string|null $dtoClass Optional DTO class for typed collections */
    public function __construct(private readonly ?string $dtoClass = null)
    {
    }

    /**
     * @return DataCollection<\event4u\DataHelpers\SimpleDTO>|null
     */
    public function get(mixed $value, array $attributes): ?DataCollection
    {
        if (null === $value) {
            return null;
        }

        // If already a DataCollection, return it
        if ($value instanceof DataCollection) {
            return $value;
        }

        // Convert to array if needed
        if (!is_array($value)) {
            $value = [$value];
        }

        // If DTO class is specified, create typed DataCollection
        if (null !== $this->dtoClass && class_exists($this->dtoClass)) {
            /** @var class-string<\event4u\DataHelpers\SimpleDTO> $dtoClass */
            $dtoClass = $this->dtoClass;
            return DataCollection::forDto($dtoClass, $value);
        }

        // Create generic DataCollection (without DTO type)
        // This is not ideal, but we need a DTO class for DataCollection
        throw new RuntimeException(
            'CollectionCast requires a DTO class. Use "collection:App\DTOs\UserDTO" instead of "collection".'
        );
    }

    /** @return array<array-key, mixed>|null */
    public function set(mixed $value, array $attributes): ?array
    {
        if (null === $value) {
            return null;
        }

        // Convert DataCollection to array
        if ($value instanceof DataCollection) {
            return $value->toArray();
        }

        // If it's already an array, return it
        if (is_array($value)) {
            // If items are DTOs, convert them to arrays
            return array_map(function(mixed $item): mixed {
                // Check if item has toArray method (duck typing for DTOs)
                if (is_object($item) && method_exists($item, 'toArray')) {
                    return $item->toArray();
                }

                return $item;
            }, $value);
        }

        return null;
    }
}

