<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Enable ultra-fast mode for LiteDto.
 *
 * Ultra-fast mode automatically detects which attributes are used and processes them on-the-fly.
 * No manual configuration needed - just add the attribute and use any features you need!
 *
 * Supported attributes (auto-detected):
 * - #[MapFrom] - Input property mapping
 * - #[MapTo] - Output property mapping
 * - #[CastWith] - Custom type casting
 * - #[ConvertEmptyToNull] - Convert empty strings/arrays to null
 * - #[EnumSerialize] - Enum serialization mode
 * - #[DataCollectionOf] - Array of DTOs
 * - #[Hidden] - Hide from output
 * - #[HiddenFromArray] - Hide from toArray()
 * - #[HiddenFromJson] - Hide from toJson()
 * - #[Computed] - Computed properties
 * - #[Lazy] - Lazy-loaded properties
 *
 * Performance: ~2.1μs (still 15x faster than SimpleDto!)
 *
 * Example:
 *   #[UltraFast]
 *   class ProductDto extends LiteDto {
 *       public function __construct(
 *           #[MapFrom('product_name')]
 *           public readonly string $name,
 *
 *           #[CastWith(PriceCaster::class)]
 *           public readonly float $price,
 *
 *           #[ConvertEmptyToNull]
 *           public readonly ?string $description,
 *       ) {}
 *   }
 *
 * Use when:
 * - You want maximum performance with full features
 * - You don't need validation
 * - You want automatic attribute detection
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UltraFast
{
}
