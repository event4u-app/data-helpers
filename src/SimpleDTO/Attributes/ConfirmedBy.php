<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * ConfirmedBy validation attribute.
 *
 * Validates that a specific confirmation field exists and matches the original field.
 * This allows you to specify a custom confirmation field name instead of using
 * the default '_confirmed' suffix.
 *
 * Example:
 * ```php
 * class RegisterDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[ConfirmedBy('passwordVerification')]
 *         public readonly string $password,
 *         public readonly string $passwordVerification,
 *     ) {}
 * }
 * ```
 *
 * This is useful when you want to use a different naming convention
 * or when the confirmation field has a different name.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ConfirmedBy implements ValidationRule
{
    public function __construct(
        private readonly string $confirmationField,
    ) {
    }

    public function rule(): string
    {
        return 'same:' . $this->confirmationField;
    }

    public function message(): ?string
    {
        return null;
    }
}
