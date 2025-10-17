<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate that a property is a valid UUID.
 *
 * Example:
 *   #[Uuid]
 *   public readonly string $id;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Uuid implements ValidationRule
{
    public function __construct(
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'uuid';
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

