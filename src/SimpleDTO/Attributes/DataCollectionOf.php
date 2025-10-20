<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Attribute to define a property as a DataCollection of DTOs.
 *
 * This attribute automatically configures the CollectionCast for the property.
 * It's a convenience attribute that combines casting and type information.
 *
 * Example:
 *   class OrderDTO extends SimpleDTO {
 *       #[DataCollectionOf(OrderItemDTO::class)]
 *       public readonly DataCollection $items;
 *   }
 *
 * This is equivalent to:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection:App\DTOs\OrderItemDTO',
 *       ];
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DataCollectionOf
{
    /** @param class-string $dtoClass The DTO class for collection items */
    public function __construct(
        public readonly string $dtoClass,
    ) {}
}

