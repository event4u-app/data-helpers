<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Enable ultra-fast mode for LiteDto.
 *
 * Ultra-fast mode bypasses most overhead for maximum performance:
 * - No attribute processing (From, To, Hidden, etc.)
 * - No nested DTO support
 * - No collection support
 * - No enum support
 * - No custom casters
 * - Direct property assignment
 *
 * Performance: ~0.3-0.5μs (similar to Carapace, ~10x faster than normal LiteDto)
 *
 * Example:
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
 * Use when:
 * - Maximum performance is critical
 * - Simple flat DTOs without nesting
 * - No special attribute features needed
 *
 * Don't use when:
 * - You need #[MapFrom], #[MapTo], #[Hidden], etc.
 * - You need nested DTOs or collections
 * - You need enum support or custom casters
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UltraFast
{
}
