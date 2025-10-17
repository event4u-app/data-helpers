<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Confirmed validation attribute.
 *
 * Validates that a confirmation field exists and matches the original field.
 * Laravel automatically looks for a field with the suffix '_confirmed'.
 *
 * Example:
 * ```php
 * class RegisterDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Confirmed]
 *         public readonly string $password,
 *         public readonly string $password_confirmed,
 *     ) {}
 * }
 * ```
 *
 * For the property 'password', Laravel will look for 'password_confirmed'.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Confirmed implements ValidationRule
{
    public function rule(): string
    {
        return 'confirmed';
    }

    public function message(): ?string
    {
        return null;
    }
}

