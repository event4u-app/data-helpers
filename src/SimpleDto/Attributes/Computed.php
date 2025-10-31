<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Marks a method as a computed property that should be included in serialization.
 *
 * Computed properties are methods that return values based on other properties.
 * They are automatically included in toArray() and JSON serialization.
 *
 * @example
 * ```php
 * class OrderDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly float $price,
 *         public readonly int $quantity,
 *     ) {}
 *
 *     #[Computed]
 *     public function total(): float
 *     {
 *         return $this->price * $this->quantity;
 *     }
 * }
 *
 * $order = OrderDto::from(['price' => 100.0, 'quantity' => 2]);
 * $order->toArray(); // ['price' => 100.0, 'quantity' => 2, 'total' => 200.0]
 * ```
 *
 * @example Lazy computed properties (only computed when explicitly requested)
 * ```php
 * #[Computed(lazy: true)]
 * public function expensiveCalculation(): array
 * {
 *     // Heavy computation
 *     return [...];
 * }
 *
 * // Not included by default
 * $order->toArray(); // ['price' => 100.0, 'quantity' => 2]
 * ```
 *
 * @example Custom output name
 * ```php
 * #[Computed(name: 'orderTotal')]
 * public function total(): float
 * {
 *     return $this->price * $this->quantity;
 * }
 *
 * $order->toArray(); // ['price' => 100.0, 'quantity' => 2, 'orderTotal' => 200.0]
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Computed
{
    /**
     * @param bool $lazy If true, only compute when explicitly requested
     * @param string|null $name Custom name for the computed property in output (defaults to method name)
     */
    public function __construct(
        public bool $lazy = false,
        public ?string $name = null,
    ) {}
}
