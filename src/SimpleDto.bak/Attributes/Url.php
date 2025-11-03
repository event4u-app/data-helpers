<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Concerns\RequiresSymfonyValidator;
use event4u\DataHelpers\SimpleDto\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationRule;
use ReflectionClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validate that a property is a valid URL.
 *
 * Example:
 *   #[Url]
 *   public readonly string $website;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function rule(): string
    {
        return 'url';
    }

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        // Symfony 7+ requires requireTld parameter, Symfony 6 doesn't have it
        static $hasRequireTld = null;

        if (null === $hasRequireTld) {
            $reflection = new ReflectionClass(Assert\Url::class);
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

        return $hasRequireTld
            ? new Assert\Url(requireTld: true)
            : new Assert\Url();
    }
    public function message(): ?string
    {
        return $this->message;
    }
}
