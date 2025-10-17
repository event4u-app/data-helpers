<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

/**
 * Validate that a property is a valid email address.
 *
 * Example:
 *   #[Email]
 *   public readonly string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements ValidationRule
{
    public function __construct(
        private readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'email';
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

