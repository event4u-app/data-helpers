<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Exceptions;

use RuntimeException;

/**
 * Base exception for all SimpleDTO exceptions.
 *
 * Provides enhanced error messages with context and suggestions.
 */
class DTOException extends RuntimeException
{
    /** Create exception for type mismatch. */
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
     * @param array<string> $availableKeys
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

    /** Create exception for invalid cast. */
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
            $message .= '
  Reason: ' . $reason;
        }

        return new self($message);
    }

    /** Create exception for nested DTO error. */
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

    /** Format value for display in error messages. */
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

            return sprintf("'%s'", $truncated);
        }

        if (is_array($value)) {
            $count = count($value);

            return sprintf('array(%d items)', $count);
        }

        if (is_object($value)) {
            return $value::class . ' object';
        }

        return (string)$value;
    }

    /**
     * Get suggestions for type mismatch.
     *
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
                $suggestions[] = sprintf(
                    "Cast the string to %s: (int) '%s' or use a cast in casts() method",
                    $expectedType,
                    $actualValue
                );
            } else {
                $suggestions[] = sprintf(
                    "The string '%s' is not numeric. Provide a valid number.",
                    (string)$actualValue
                );
            }
        }

        // Int to string
        if ('int' === $actualType && 'string' === $expectedType) {
            $suggestions[] = sprintf(
                "Cast the integer to string: (string) %s or use 'string' cast in casts() method",
                (string)$actualValue
            );
        }

        // Array to object
        if ('array' === $actualType && str_contains($expectedType, '\\')) {
            $suggestions[] = sprintf('Convert array to %s using %s::fromArray($value)', $expectedType, $expectedType);
        }

        // Null to non-nullable
        if ('null' === $actualType && !str_contains($expectedType, '?')) {
            $suggestions[] = sprintf('Make the property nullable: ?%s ${property}', $expectedType);
            $suggestions[] = "Or provide a non-null value in the data array";
        }

        return $suggestions;
    }

    /**
     * Get similar keys using Levenshtein distance.
     *
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
            if (3 >= $distance) { // Max distance of 3 characters
                $similar[$key] = $distance;
            }
        }

        // Sort by distance
        asort($similar);

        // Return top 3 suggestions
        return array_slice(array_keys($similar), 0, 3);
    }
}

