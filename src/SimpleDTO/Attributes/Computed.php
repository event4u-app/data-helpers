<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Marks a method as a computed property that should be included in serialization.
 *
 * Computed properties are methods that return values based on other properties.
 * They are automatically included in toArray() and JSON serialization.
 *
 * @example
 * ```php
 * class OrderDTO extends SimpleDTO
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
 * $order = OrderDTO::fromArray(['price' => 100.0, 'quantity' => 2]);
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
 *
 * // Include explicitly
 * $order->include(['expensiveCalculation'])->toArray();
 * ```
 *
 * @example With dependencies for cache invalidation
 * ```php
 * #[Computed(depends: ['price', 'quantity'])]
 * public function total(): float
 * {
 *     return $this->price * $this->quantity;
 * }
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
     * @param bool $lazy If true, only compute when explicitly requested via include()
     * @param array<string>|null $depends List of property names this computed property depends on
     * @param string|null $name Custom name for the computed property in output (defaults to method name)
     * @param bool $cache Whether to cache the computed value (default: true)
     */
    public function __construct(
        public bool $lazy = false,
        public ?array $depends = null,
        public ?string $name = null,
        public bool $cache = true,
    ) {}
}

