<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to define a property as a DataCollection of Dtos.
 *
 * This attribute automatically configures the CollectionCast for the property.
 * It's a convenience attribute that combines casting and type information.
 *
 * Example:
 *   class OrderDto extends SimpleDto {
 *       #[DataCollectionOf(OrderItemDto::class)]
 *       public readonly DataCollection $items;
 *   }
 *
 * This is equivalent to:
 *   protected function casts(): array {
 *       return [
 *           'items' => 'collection:App\Dtos\OrderItemDto',
 *       ];
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DataCollectionOf
{
    /** @param class-string $dtoClass The Dto class for collection items */
    public function __construct(
        public readonly string $dtoClass,
    ) {}
}
