<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Skip all validation for this SimpleDto class.
 *
 * This attribute improves performance by skipping all validation logic.
 * Use this when you trust your data source and don't need validation.
 *
 * Performance Impact:
 * - Skips all validation attribute processing
 * - Skips validation rule extraction
 * - Faster DTO instantiation
 * - Zero overhead when validation is not needed
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\SimpleDto;
 * use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;
 *
 * #[NoValidation]
 * class TrustedDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $email,
 *         public readonly int $age,
 *     ) {}
 * }
 *
 * // No validation will be performed
 * $dto = TrustedDto::from(['email' => 'john@example.com', 'age' => 30]);
 * ```
 *
 * Note: This will disable ALL validation including:
 * - Validation attributes (#[Required], #[Email], #[Min], etc.)
 * - Custom validation rules
 * - Conditional validation (#[RequiredIf], #[RequiredUnless], etc.)
 *
 * Other attributes still work:
 * - Visibility attributes (#[Hidden], #[Visible])
 * - Mapping attributes (#[MapFrom], #[MapTo])
 * - Conditional properties (#[WhenValue], #[WhenContext], etc.)
 * - Computed properties (#[Computed])
 *
 * @package event4u\DataHelpers\SimpleDto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class NoValidation
{
}
