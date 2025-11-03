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
 * Mark a property as required.
 *
 * Example:
 *   #[Required]
 *   public readonly string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Required implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        // Check if value is present and not empty
        if (null === $value) {
            return false;
        }

        if (is_string($value) && '' === trim($value)) {
            return false;
        }
        return !(is_array($value) && [] === $value);
    }

    public function getErrorMessage(string $propertyName): string
    {
        return $this->message ?? sprintf('The %s field is required.', $propertyName);
    }

    public function rule(): string
    {
        return 'required';
    }

    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\NotBlank(message: $this->message);
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
