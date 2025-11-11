<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\OptionalSymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use ReflectionClass;

/**
 * Validate that a property is a valid URL.
 *
 * Example:
 *   #[Url]
 *   public readonly string $website;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements ValidationAttribute, ValidationRule, SymfonyConstraint
{
    use OptionalSymfonyConstraint;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): bool
    {
        if (null === $value || '' === $value) {
            return true; // Empty values are handled by Required attribute
        }

        if (!is_string($value)) {
            return false;
        }

        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }

    public function getErrorMessage(string $propertyName): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return sprintf('The %s must be a valid URL.', $propertyName);
    }

    public function rule(): string
    {
        return 'url';
    }

    public function constraint(): object
    {
        // Symfony 7+ requires requireTld parameter, Symfony 6 doesn't have it
        static $hasRequireTld = null;

        if (null === $hasRequireTld && class_exists("\\Symfony\\Component\\Validator\\Constraints\\Url")) {
            $reflection = new ReflectionClass("\\Symfony\\Component\\Validator\\Constraints\\Url");
            $constructor = $reflection->getConstructor();
            $hasRequireTld = false;

            if ($constructor) {
                foreach ($constructor->getParameters() as $reflectionParameter) {
                    if ($reflectionParameter->getName() === 'requireTld') {
                        $hasRequireTld = true;
                        break;
                    }
                }
            }
        }

        $options = [];
        if ($hasRequireTld) {
            $options['requireTld'] = true;
        }

        return $this->createConstraint(
            "\\Symfony\\Component\\Validator\\Constraints\\Url",
            $options
        );
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
