<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Skip all type casting for this class.
 *
 * This attribute improves performance by skipping all cast operations
 * including AutoCast and explicit Cast attributes. Use this when you
 * know your input data is already in the correct types.
 *
 * Performance Impact:
 * - Skips all cast operations
 * - No type coercion (strict types only)
 * - Faster DTO instantiation
 *
 * Example:
 * ```php
 * #[NoCasts]
 * class StrictDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *     ) {}
 * }
 *
 * // This will work (correct types)
 * $dto = StrictDto::fromArray(['name' => 'John', 'age' => 30]);
 *
 * // This will throw TypeError (wrong types, no casting)
 * $dto = StrictDto::fromArray(['name' => 'John', 'age' => '30']);
 * ```
 *
 * Note: This will disable ALL casting including:
 * - AutoCast attribute
 * - Explicit Cast attributes
 * - Built-in type coercion
 *
 * Validation and other attributes will still work.
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoCasts
{
}

