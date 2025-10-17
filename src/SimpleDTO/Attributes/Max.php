<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate maximum value/length.
 *
 * For strings: maximum length
 * For numbers: maximum value
 * For arrays: maximum number of items
 *
 * Example:
 *   #[Max(255)]
 *   public readonly string $name;
 *
 *   #[Max(120)]
 *   public readonly int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Max implements ValidationRule
{
    public function __construct(
        private readonly int|float $value,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'max:' . $this->value;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

