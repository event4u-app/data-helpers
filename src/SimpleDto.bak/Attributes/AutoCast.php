<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Enable automatic type casting for native PHP types.
 *
 * This attribute controls whether automatic casting to native PHP types (int, string, float, bool)
 * should be performed. It can be applied at class level (affects all properties) or property level
 * (affects only that property).
 *
 * **Important**: This attribute only controls AUTOMATIC casting to native PHP types.
 * Explicit casts (via #[Cast], #[DataCollectionOf], or casts() method) are ALWAYS applied
 * regardless of this attribute.
 *
 * **Casting Priority:**
 * 1. Explicit Cast attributes (#[Cast], #[DataCollectionOf]) - ALWAYS applied
 * 2. casts() method - ALWAYS applied
 * 3. #[AutoCast] + Native PHP Types - ONLY when #[AutoCast] is present
 * 4. No casting - When none of the above apply
 *
 * **Use Cases:**
 * - CSV/XML imports where strings need to be converted to proper types
 * - Legacy APIs that return everything as strings
 * - Data sources without proper type information
 *
 * **Performance Note:**
 * DTOs without #[AutoCast] skip automatic type casting entirely, improving performance
 * by avoiding unnecessary reflection and type checking.
 *
 * Example (Class-level):
 * ```php
 * #[AutoCast]
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public int $id,        // "123" → 123 ✅
 *         public string $name,   // 123 → "123" ✅
 *         public float $score,   // "9.5" → 9.5 ✅
 *     ) {}
 * }
 * ```
 *
 * Example (Property-level):
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[AutoCast]
 *         public int $quantity,     // "10" → 10 ✅
 *
 *         public string $name,      // No auto-casting
 *
 *         // Explicit casts ALWAYS work regardless of AutoCast
 *         #[Cast(DateTimeCast::class)]
 *         public Carbon $createdAt, // ✅ Always casted
 *     ) {}
 * }
 * ```
 *
 * Example (Without AutoCast - Strict typing):
 * ```php
 * class StrictDto extends SimpleDto
 * {
 *     public function __construct(
 *         public int $id,        // "123" → Type Error ❌
 *
 *         // But explicit casts still work!
 *         #[Cast(IntCast::class)]
 *         public int $amount,    // "456" → 456 ✅
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class AutoCast
{
}
