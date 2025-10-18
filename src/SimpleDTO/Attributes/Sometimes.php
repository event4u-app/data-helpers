<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Conditional validation attribute: Field is only validated if it is present in the input.
 *
 * This is useful for optional fields that should be validated only when provided.
 *
 * Example:
 * ```php
 * class UpdateUserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Sometimes]
 *         #[Email]
 *         public readonly ?string $email = null,
 *         
 *         #[Sometimes]
 *         #[Min(8)]
 *         public readonly ?string $password = null,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Sometimes implements ValidationRule
{
    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return 'sometimes';
    }

    /**
     * Get validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        return null;
    }
}

