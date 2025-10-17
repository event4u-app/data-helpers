<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
use Illuminate\Support\Collection as LaravelCollection;
use RuntimeException;

/**
 * Cast attribute to Collection (Laravel or Doctrine).
 *
 * Supports:
 * - Laravel Collections (Illuminate\Support\Collection)
 * - Doctrine Collections (Doctrine\Common\Collections\Collection)
 * - Typed collections (collections of DTOs)
 * - Nested DTOs
 * - Null values
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection',                           // Laravel Collection
 *           'users' => 'collection:App\DTOs\UserDTO',          // Collection of UserDTOs
 *           'tags' => 'collection:doctrine',                   // Doctrine Collection
 *           'posts' => 'collection:doctrine,App\DTOs\PostDTO', // Doctrine Collection of PostDTOs
 *       ];
 *   }
 */
class CollectionCast implements CastsAttributes
{
    private readonly string $collectionType;

    private readonly ?string $dtoClass;

    /**
     * @param string $collectionType Collection type: 'laravel' or 'doctrine' (default: 'laravel')
     * @param string|null $dtoClass Optional DTO class for typed collections
     */
    public function __construct(
        string $collectionType = 'laravel',
        ?string $dtoClass = null,
    ) {
        $this->collectionType = strtolower($collectionType);
        $this->dtoClass = $dtoClass;
    }

    /** @return LaravelCollection<array-key, mixed>|DoctrineCollection<array-key, mixed>|null */
    public function get(mixed $value, array $attributes): LaravelCollection|DoctrineCollection|null
    {
        if (null === $value) {
            return null;
        }

        // If already a collection, return it
        if ($value instanceof LaravelCollection || $value instanceof DoctrineCollection) {
            return $value;
        }

        // Convert to array if needed
        if (!is_array($value)) {
            $value = [$value];
        }

        // If DTO class is specified, convert each item to DTO
        if (null !== $this->dtoClass && class_exists($this->dtoClass)) {
            $value = $this->convertItemsToDTOs($value);
        }

        // Create collection based on type
        return $this->createCollection($value);
    }

    /** @return array<array-key, mixed>|null */
    public function set(mixed $value, array $attributes): ?array
    {
        if (null === $value) {
            return null;
        }

        // Convert collection to array
        if ($value instanceof LaravelCollection) {
            $array = $value->toArray();
        } elseif ($value instanceof DoctrineCollection) {
            $array = $value->toArray();
        } elseif (is_array($value)) {
            $array = $value;
        } else {
            return null;
        }

        // If items are DTOs, convert them to arrays
        return array_map(function(mixed $item): mixed {
            // Check if item has toArray method (duck typing for DTOs)
            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }

            return $item;
        }, $array);
    }

    /**
     * Convert array items to DTOs.
     *
     * @param array<array-key, mixed> $items
     * @return array<array-key, mixed>
     */
    private function convertItemsToDTOs(array $items): array
    {
        if (null === $this->dtoClass) {
            return $items;
        }

        return array_map(function(mixed $item): mixed {
            // If already an object with fromArray method, return it
            if (is_object($item)) {
                return $item;
            }

            // If it's an array, create DTO from array
            if (is_array($item) && null !== $this->dtoClass && class_exists($this->dtoClass)) {
                $dtoClass = $this->dtoClass;

                // Call fromArray statically
                return $dtoClass::fromArray($item);
            }

            // Otherwise, return as-is
            return $item;
        }, $items);
    }

    /**
     * Create collection based on type.
     *
     * @param array<array-key, mixed> $items
     * @return LaravelCollection<array-key, mixed>|DoctrineCollection<array-key, mixed>
     */
    private function createCollection(array $items): LaravelCollection|DoctrineCollection
    {
        if ('doctrine' === $this->collectionType) {
            // Check if Doctrine Collections is available
            if (class_exists(ArrayCollection::class)) {
                return new ArrayCollection($items);
            }

            // Fallback to Laravel Collection if Doctrine is not available
        }

        // Default to Laravel Collection
        if (class_exists(LaravelCollection::class)) {
            return new LaravelCollection($items);
        }

        // If neither is available, throw exception
        throw new RuntimeException(
            'No collection library available. Install illuminate/support or doctrine/collections.'
        );
    }
}

