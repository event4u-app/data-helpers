<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Enable ultra-fast mode for LiteDto.
 *
 * Ultra-fast mode bypasses most overhead for maximum performance:
 * - No attribute processing by default (can be re-enabled)
 * - No nested DTO support
 * - No collection support
 * - No enum support
 * - Direct property assignment
 *
 * Performance: ~0.3-0.5μs (similar to OtherDto, ~10x faster than normal LiteDto)
 *
 * Example (basic):
 *   #[UltraFast]
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly int $age,
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['name' => 'John', 'age' => 30]);
 *   // Ultra-fast: ~0.3μs
 *
 * Example (with selective attributes):
 *   #[UltraFast(allowMapFrom: true, allowCastWith: true)]
 *   class ProductDto extends LiteDto {
 *       public function __construct(
 *           #[MapFrom('product_name')]
 *           public readonly string $name,
 *
 *           #[CastWith(PriceCaster::class)]
 *           public readonly float $price,
 *       ) {}
 *   }
 *
 * Use when:
 * - Maximum performance is critical
 * - Simple flat DTOs without nesting
 * - Minimal attribute features needed
 *
 * Don't use when:
 * - You need nested DTOs or collections
 * - You need enum support
 * - You need #[Hidden] attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UltraFast
{
    /**
     * Create a new UltraFast attribute.
     *
     * @param bool $allowMapFrom Whether to process #[MapFrom] attributes (default: false)
     * @param bool $allowMapTo Whether to process #[MapTo] attributes (default: false)
     * @param bool $allowCastWith Whether to process #[CastWith] attributes (default: false)
     */
    public function __construct(
        public bool $allowMapFrom = false,
        public bool $allowMapTo = false,
        public bool $allowCastWith = false,
    ) {}
}
