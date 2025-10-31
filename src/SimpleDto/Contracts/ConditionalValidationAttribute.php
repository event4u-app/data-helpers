<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Contracts;

/**
 * Interface for conditional validation attributes that need access to all data.
 *
 * Conditional validation attributes can check other properties to determine
 * if the current property should be validated.
 */
interface ConditionalValidationAttribute extends ValidationAttribute
{
    /**
     * Validate the value with access to all data.
     *
     * @param mixed $value The value to validate
     * @param string $propertyName The name of the property being validated
     * @param array<string, mixed> $allData All data being validated
     * @return bool True if validation passes, false otherwise
     */
    public function validateConditional(mixed $value, string $propertyName, array $allData): bool;
}
