<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * NotIn validation attribute.
 *
 * Validates that the value is NOT in the given list of values.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[NotIn(['admin', 'root', 'system'])]
 *         public readonly string $username,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotIn implements ValidationRule
{
    /** @param array<int|string> $values */
    public function __construct(
        private readonly array $values,
    ) {
    }

    public function rule(): string
    {
        return 'not_in:' . implode(',', $this->values);
    }

    public function message(): ?string
    {
        return null;
    }
}

