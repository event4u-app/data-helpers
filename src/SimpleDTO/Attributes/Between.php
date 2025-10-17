<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate that a value is between min and max.
 *
 * For strings: length between min and max
 * For numbers: value between min and max
 * For arrays: number of items between min and max
 *
 * Example:
 *   #[Between(18, 120)]
 *   public readonly int $age;
 *
 *   #[Between(3, 50)]
 *   public readonly string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Between implements ValidationRule
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max,
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'between:' . $this->min . ',' . $this->max;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

