<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate minimum value/length.
 *
 * For strings: minimum length
 * For numbers: minimum value
 * For arrays: minimum number of items
 *
 * Example:
 *   #[Min(3)]
 *   public readonly string $name;
 *
 *   #[Min(18)]
 *   public readonly int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Min implements ValidationRule
{
    public function __construct(
        private readonly int|float $value,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'min:' . $this->value;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

