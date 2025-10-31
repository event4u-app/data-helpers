<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Skip all validation for this class.
 *
 * This attribute improves performance by skipping all validation logic.
 * Use this when you trust your data source and don't need validation.
 *
 * Performance Impact:
 * - Skips all validation attribute processing
 * - Skips validation rule extraction
 * - Faster DTO instantiation
 *
 * Example:
 * ```php
 * #[NoValidation]
 * class TrustedDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $email,
 *         public readonly int $age,
 *     ) {}
 * }
 * ```
 *
 * Note: This will disable ALL validation including:
 * - Validation attributes (#[Required], #[Email], #[Min], etc.)
 * - Auto-inferred validation rules
 * - Custom validation rules
 *
 * Other attributes still work:
 * - Cast attributes (#[Cast], #[AutoCast])
 * - Visibility attributes (#[Hidden], #[Visible])
 * - Mapping attributes (#[MapFrom], #[MapTo])
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoValidation
{
}
