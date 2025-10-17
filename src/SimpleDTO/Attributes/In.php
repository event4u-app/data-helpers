<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate that a value is in a list of allowed values.
 *
 * Example:
 *   #[In(['admin', 'user', 'guest'])]
 *   public readonly string $role;
 *
 *   #[In([1, 2, 3, 4, 5])]
 *   public readonly int $rating;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class In implements ValidationRule
{
    /** @param array<int|string> $values */
    public function __construct(
        private readonly array $values,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        $values = implode(',', $this->values);

        return 'in:' . $values;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

