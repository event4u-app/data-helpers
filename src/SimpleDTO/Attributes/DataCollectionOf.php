<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to define a property as a collection of DTOs.
 *
 * This attribute automatically configures the CollectionCast for the property.
 * It's a convenience attribute that combines casting and type information.
 *
 * Example:
 *   class OrderDTO extends SimpleDTO {
 *       #[DataCollectionOf(OrderItemDTO::class)]
 *       public readonly Collection $items;
 *
 *       #[DataCollectionOf(TagDTO::class, collectionType: 'doctrine')]
 *       public readonly DoctrineCollection $tags;
 *   }
 *
 * This is equivalent to:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection:App\DTOs\OrderItemDTO',
 *           'tags' => 'collection:doctrine,App\DTOs\TagDTO',
 *       ];
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DataCollectionOf
{
    /**
     * @param class-string $dtoClass The DTO class for collection items
     * @param string $collectionType Collection type: 'laravel' or 'doctrine' (default: 'laravel')
     */
    public function __construct(
        public readonly string $dtoClass,
        public readonly string $collectionType = 'laravel',
    ) {}
}

