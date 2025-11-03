<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Skip loading and processing of all attributes for this class.
 *
 * This attribute improves performance by skipping attribute reflection
 * and processing. Use this when you don't need any attributes on your
 * DTO properties (validation, visibility, casts, etc.).
 *
 * Performance Impact:
 * - Skips reflection of all property attributes
 * - Reduces memory usage
 * - Faster DTO instantiation
 *
 * Example:
 * ```php
 * #[NoAttributes]
 * class FastDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly int $age,
 *     ) {}
 * }
 * ```
 *
 * Note: This will disable ALL attribute processing including:
 * - Validation attributes (#[Required], #[Email], etc.)
 * - Visibility attributes (#[Visible], #[Hidden], etc.)
 * - Cast attributes (#[Cast], #[AutoCast], etc.)
 * - Conditional attributes (#[WhenValue], #[WhenContext], etc.)
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoAttributes
{
}
