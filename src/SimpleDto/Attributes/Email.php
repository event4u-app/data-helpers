<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validate that a property is a valid email address.
 *
 * Example:
 *   #[Email]
 *   public readonly string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value || '' === $value) {
            return true; // Empty values are handled by Required attribute
        }

        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s must be a valid email address.', $propertyName);
    }

    public function rule(): string
    {
        return 'email';
    }

    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Email(message: $this->message);
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
