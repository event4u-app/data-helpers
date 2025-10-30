<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Contracts;

/**
 * Interface for validation attributes.
 *
 * All validation attributes must implement this interface to provide
 * validation logic that can be cached and executed efficiently.
 *
 * Example:
 * ```php
 * #[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
 * class Email implements ValidationAttribute
 * {
 *     public function validate(mixed $value, string $propertyName): bool
 *     {
 *         return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
 *     }
 *
 *     public function getErrorMessage(string $propertyName): string
 *     {
 *         return "The {$propertyName} must be a valid email address.";
 *     }
 * }
 * ```
 */
interface ValidationAttribute
{
    /**
     * Validate the given value.
     *
     * @param mixed $value The value to validate
     * @param string $propertyName The name of the property being validated
     * @return bool True if validation passes, false otherwise
     */
    public function validate(mixed $value, string $propertyName): bool;

    /**
     * Get the error message for validation failure.
     *
     * @param string $propertyName The name of the property that failed validation
     * @return string The error message
     */
    public function getErrorMessage(string $propertyName): string;
}
