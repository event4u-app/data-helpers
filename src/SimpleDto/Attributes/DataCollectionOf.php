<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to define a property as a collection of DTOs.
 *
 * This attribute automatically converts an array of arrays to an array of DTOs.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
 *
 * class OrderDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $orderId,
 *         #[DataCollectionOf(OrderItemDto::class)]
 *         public readonly array $items,
 *     ) {}
 * }
 *
 * $order = OrderDto::from([
 *     'orderId' => 'ORD-123',
 *     'items' => [
 *         ['name' => 'Item 1', 'price' => 10.0],
 *         ['name' => 'Item 2', 'price' => 20.0],
 *     ],
 * ]);
 *
 * // $order->items is now an array of OrderItemDto instances
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class DataCollectionOf
{
    /** @param class-string $dtoClass The Dto class for collection items */
    public function __construct(
        public string $dtoClass,
    ) {}
}
