<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Skip loading and processing of all attributes for this SimpleDto class.
 *
 * This attribute improves performance by skipping attribute reflection
 * and processing. Use this when you don't need any attributes on your
 * DTO properties (validation, visibility, mapping, etc.).
 *
 * Performance Impact:
 * - Skips reflection of all property attributes
 * - Reduces memory usage
 * - Faster DTO instantiation
 * - Maximum performance for simple DTOs
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
 *
 * #[NoAttributes]
 * class FastDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *     ) {}
 * }
 *
 * // Maximum performance - no attribute processing
 * $dto = FastDto::from(['name' => 'John', 'age' => 30]);
 * ```
 *
 * Note: This will disable ALL attribute processing including:
 * - Validation attributes (#[Required], #[Email], etc.)
 * - Visibility attributes (#[Visible], #[Hidden], etc.)
 * - Mapping attributes (#[MapFrom], #[MapTo], etc.)
 * - Conditional attributes (#[WhenValue], #[WhenContext], etc.)
 * - Computed properties (#[Computed])
 * - All other property attributes
 *
 * Use this for:
 * - Simple DTOs with just properties
 * - Maximum performance scenarios
 * - When you don't need any attribute features
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoAttributes
{
}
