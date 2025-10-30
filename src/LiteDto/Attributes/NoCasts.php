<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Skip all type casting for this LiteDto class.
 *
 * This attribute improves performance by skipping all cast operations
 * including nested DTO casting and collection casting. Use this when you
 * know your input data is already in the correct types.
 *
 * Performance Impact:
 * - Skips nested DTO casting
 * - Skips collection casting (#[DataCollectionOf])
 * - No type coercion (strict types only)
 * - Faster DTO instantiation
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\LiteDto\LiteDto;
 * use event4u\DataHelpers\LiteDto\Attributes\NoCasts;
 *
 * #[NoCasts]
 * class StrictDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *     ) {}
 * }
 *
 * // This will work (correct types)
 * $dto = StrictDto::from(['name' => 'John', 'age' => 30]);
 *
 * // This will throw TypeError (wrong types, no casting)
 * $dto = StrictDto::from(['name' => 'John', 'age' => '30']);
 * ```
 *
 * Note: This will disable ALL casting including:
 * - Nested DTO casting (AddressDto, UserDto, etc.)
 * - Collection casting (#[DataCollectionOf])
 * - Built-in type coercion
 *
 * Validation and other attributes will still work:
 * - Validation attributes (#[Required], #[Email], etc.)
 * - Visibility attributes (#[Hidden], #[Visible])
 * - Mapping attributes (#[MapFrom], #[MapTo])
 *
 * @package event4u\DataHelpers\LiteDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoCasts
{
}
