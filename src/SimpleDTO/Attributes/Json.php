<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;
use event4u\DataHelpers\SimpleDTO\Contracts\SymfonyConstraint;
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation attribute: Value must be valid JSON.
 *
 * Example:
 * ```php
 * class ConfigDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         #[Json]
 *         public readonly string $settings,
 *         
 *         #[Json]
 *         public readonly string $metadata,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Json implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    /**
     * Convert to Laravel validation rule.
     *
     * @return string
     */
    public function rule(): string
    {
        return 'json';
    }

    /**
     * Get validation error message.
     *
     * @param string $attribute
     * @return string
     */

    public function constraint(): Constraint|array
    {
        $this->ensureSymfonyValidatorAvailable();

        return new Assert\Json();
    }
    public function message(): ?string
    {
        return "The attribute must be a valid JSON string.";
    }
}

