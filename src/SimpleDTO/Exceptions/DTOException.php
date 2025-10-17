<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Exceptions;

use RuntimeException;

/**
 * Base exception for all SimpleDTO exceptions.
 *
 * Provides enhanced error messages with context and suggestions.
 */
class DTOException extends RuntimeException
{
    /**
     * Create exception for type mismatch.
     *
     * @param string $dtoClass
     * @param string $property
     * @param string $expectedType
     * @param mixed $actualValue
     * @param string $propertyPath
     * @return self
     */
    public static function typeMismatch(
        string $dtoClass,
        string $property,
        string $expectedType,
        mixed $actualValue,
        string $propertyPath = ''
    ): self {
        $actualType = get_debug_type($actualValue);
        $path = '' !== $propertyPath ? $propertyPath : $property;

        $message = sprintf(
            "Type mismatch in %s::\$%s\n" .
            "  Property path: %s\n" .
            "  Expected type: %s\n" .
            "  Actual type: %s\n" .
            "  Actual value: %s",
            $dtoClass,
            $property,
            $path,
            $expectedType,
            $actualType,
            self::formatValue($actualValue)
        );

        // Add suggestions
        $suggestions = self::getSuggestionsForTypeMismatch($expectedType, $actualType, $actualValue);
        if ([] !== $suggestions) {
            $message .= "\n\nSuggestions:\n  - " . implode("\n  - ", $suggestions);
        }

        return new self($message);
    }

    /**
     * Create exception for missing required property.
     *
     * @param string $dtoClass
     * @param string $property
     * @param array<string> $availableKeys
     * @return self
     */
    public static function missingProperty(
        string $dtoClass,
        string $property,
        array $availableKeys = []
    ): self {
        $message = sprintf(
            "Missing required property in %s::\$%s",
            $dtoClass,
            $property
        );

        // Add available keys
        if ([] !== $availableKeys) {
            $message .= "\n\nAvailable keys in data:\n  - " . implode("\n  - ", $availableKeys);
        }

        // Add suggestions for similar keys
        $suggestions = self::getSimilarKeys($property, $availableKeys);
        if ([] !== $suggestions) {
            $message .= "\n\nDid you mean:\n  - " . implode("\n  - ", $suggestions);
        }

        return new self($message);
    }

    /**
     * Create exception for invalid cast.
     *
     * @param string $dtoClass
     * @param string $property
     * @param string $castType
     * @param mixed $value
     * @param string $reason
     * @return self
     */
    public static function invalidCast(
        string $dtoClass,
        string $property,
        string $castType,
        mixed $value,
        string $reason = ''
    ): self {
        $message = sprintf(
            "Cast failed in %s::\$%s\n" .
            "  Cast type: %s\n" .
            "  Value: %s\n" .
            "  Value type: %s",
            $dtoClass,
            $property,
            $castType,
            self::formatValue($value),
            get_debug_type($value)
        );

        if ('' !== $reason) {
            $message .= "\n  Reason: {$reason}";
        }

        return new self($message);
    }

    /**
     * Create exception for nested DTO error.
     *
     * @param string $dtoClass
     * @param string $property
     * @param string $nestedDtoClass
     * @param string $nestedProperty
     * @param string $originalMessage
     * @return self
     */
    public static function nestedError(
        string $dtoClass,
        string $property,
        string $nestedDtoClass,
        string $nestedProperty,
        string $originalMessage
    ): self {
        $message = sprintf(
            "Error in nested DTO %s::\$%s\n" .
            "  Nested DTO: %s\n" .
            "  Nested property: %s\n" .
            "  Property path: %s.%s\n\n" .
            "Original error:\n%s",
            $dtoClass,
            $property,
            $nestedDtoClass,
            $nestedProperty,
            $property,
            $nestedProperty,
            $originalMessage
        );

        return new self($message);
    }

    /**
     * Format value for display in error messages.
     *
     * @param mixed $value
     * @return string
     */
    private static function formatValue(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            $truncated = mb_strlen($value) > 50 ? mb_substr($value, 0, 50) . '...' : $value;

            return "'{$truncated}'";
        }

        if (is_array($value)) {
            $count = count($value);

            return "array({$count} items)";
        }

        if (is_object($value)) {
            return get_class($value) . ' object';
        }

        return (string) $value;
    }

    /**
     * Get suggestions for type mismatch.
     *
     * @param string $expectedType
     * @param string $actualType
     * @param mixed $actualValue
     * @return array<string>
     */
    private static function getSuggestionsForTypeMismatch(
        string $expectedType,
        string $actualType,
        mixed $actualValue
    ): array {
        $suggestions = [];

        // String to int/float
        if ('string' === $actualType && in_array($expectedType, ['int', 'float'], true)) {
            if (is_numeric($actualValue)) {
                $suggestions[] = "Cast the string to {$expectedType}: (int) '{$actualValue}' or use a cast in casts() method";
            } else {
                $suggestions[] = "The string '{$actualValue}' is not numeric. Provide a valid number.";
            }
        }

        // Int to string
        if ('int' === $actualType && 'string' === $expectedType) {
            $suggestions[] = "Cast the integer to string: (string) {$actualValue} or use 'string' cast in casts() method";
        }

        // Array to object
        if ('array' === $actualType && str_contains($expectedType, '\\')) {
            $suggestions[] = "Convert array to {$expectedType} using {$expectedType}::fromArray(\$value)";
        }

        // Null to non-nullable
        if ('null' === $actualType && !str_contains($expectedType, '?')) {
            $suggestions[] = "Make the property nullable: ?{$expectedType} \${property}";
            $suggestions[] = "Or provide a non-null value in the data array";
        }

        return $suggestions;
    }

    /**
     * Get similar keys using Levenshtein distance.
     *
     * @param string $needle
     * @param array<string> $haystack
     * @return array<string>
     */
    private static function getSimilarKeys(string $needle, array $haystack): array
    {
        if ([] === $haystack) {
            return [];
        }

        $similar = [];
        foreach ($haystack as $key) {
            $distance = levenshtein(strtolower($needle), strtolower($key));
            if ($distance <= 3) { // Max distance of 3 characters
                $similar[$key] = $distance;
            }
        }

        // Sort by distance
        asort($similar);

        // Return top 3 suggestions
        return array_slice(array_keys($similar), 0, 3);
    }
}

