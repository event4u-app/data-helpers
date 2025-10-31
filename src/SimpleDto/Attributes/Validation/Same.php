<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes\Validation;

use Attribute;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalValidationAttribute;

/**
 * Validation attribute: Value must be the same as another field.
 *
 * Example:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $password,
 *
 *         #[Same('password')]
 *         public readonly string $passwordConfirmation,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Same implements ConditionalValidationAttribute
{
    /** @param string $field Field name to compare with */
    public function __construct(
        public readonly string $field,
    ) {}

    public function validate(mixed $value, string $propertyName): bool
    {
        // This method is not used for conditional validation
        // We need access to all data, so validateConditional() is used instead
        return true;
    }

    public function validateConditional(mixed $value, string $propertyName, array $allData): bool
    {
        // Skip validation if value is null
        if (null === $value) {
            return true;
        }

        // Check if comparison field exists
        if (!isset($allData[$this->field])) {
            return false;
        }

        // Check if values match
        return $value === $allData[$this->field];
    }

    public function getErrorMessage(string $propertyName): string
    {
        return sprintf('The %s and %s must match.', $propertyName, $this->field);
    }
}
