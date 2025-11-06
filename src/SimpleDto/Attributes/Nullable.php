<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;

/**
 * Validation attribute: Field may be null.
 *
 * This explicitly marks a field as nullable for validation purposes.
 * Useful when you want to allow null values even with other validation rules.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Nullable]
 *         #[Email]
 *         public readonly ?string $email = null,
 *
 *         #[Nullable]
 *         #[Url]
 *         public readonly ?string $website = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Nullable implements ValidationRule
{
    /** Convert to Laravel validation rule. */
    public function rule(): string
    {
        return 'nullable';
    }

    /** Get validation error message. */
    public function message(): ?string
    {
        return null;
    }
}
