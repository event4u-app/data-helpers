<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Enable automatic type casting for native PHP types in SimpleDto.
 *
 * This attribute controls whether automatic casting to native PHP types (int, string, float, bool, array)
 * should be performed. It can be applied at class level (affects all properties) or property level
 * (affects only that property).
 *
 * **Important**: This attribute only controls AUTOMATIC casting to native PHP types.
 * Explicit casts (via #[CastWith], #[DataCollectionOf], or nested DTOs) are ALWAYS applied
 * regardless of this attribute.
 *
 * **Casting Priority:**
 * 1. #[NoCasts] - Disables ALL casting (highest priority)
 * 2. Explicit Cast attributes (#[CastWith], #[DataCollectionOf]) - ALWAYS applied (unless NoCasts)
 * 3. Nested DTOs - ALWAYS applied (unless NoCasts)
 * 4. #[AutoCast] + Native PHP Types - ONLY when #[AutoCast] is present
 * 5. No casting - When none of the above apply
 *
 * **Use Cases:**
 * - CSV/XML imports where strings need to be converted to proper types
 * - Legacy APIs that return everything as strings
 * - Data sources without proper type information
 * - Form data that comes as strings but needs to be typed
 *
 * **Performance Note:**
 * DTOs without #[AutoCast] skip automatic type casting entirely, improving performance
 * by avoiding unnecessary type checking. Zero overhead when not used (feature-flag system).
 *
 * Example (Class-level):
 * ```php
 * use event4u\DataHelpers\SimpleDto\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
 *
 * #[AutoCast]
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly int $id,        // "123" → 123 ✅
 *         public readonly string $name,   // 123 → "123" ✅
 *         public readonly float $score,   // "9.5" → 9.5 ✅
 *         public readonly bool $active,   // "1" → true ✅
 *     ) {}
 * }
 *
 * $user = UserDto::from(['id' => '123', 'name' => 456, 'score' => '9.5', 'active' => '1']);
 * // $user->id === 123 (int)
 * // $user->name === "456" (string)
 * // $user->score === 9.5 (float)
 * // $user->active === true (bool)
 * ```
 *
 * Example (Property-level):
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[AutoCast]
 *         public readonly int $quantity,     // "10" → 10 ✅
 *
 *         public readonly string $name,      // No auto-casting
 *
 *         // Explicit casts ALWAYS work regardless of AutoCast
 *         #[CastWith(DateTimeCast::class)]
 *         public readonly Carbon $createdAt, // ✅ Always casted
 *     ) {}
 * }
 * ```
 *
 * Example (Without AutoCast - Strict typing):
 * ```php
 * class StrictDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly int $id,        // "123" → Type Error ❌
 *
 *         // But explicit casts still work!
 *         #[CastWith(IntCast::class)]
 *         public readonly int $amount,    // "456" → 456 ✅
 *     ) {}
 * }
 * ```
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class AutoCast
{
}
