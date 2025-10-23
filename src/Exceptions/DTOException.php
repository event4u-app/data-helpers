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

        $message = 'Type mismatch in ' . $dtoClass . '::$' . $property . PHP_EOL .
            '  Property path: ' . $path . PHP_EOL .
            '  Expected type: ' . $expectedType . PHP_EOL .
            '  Actual type: ' . $actualType . PHP_EOL .
            '  Actual value: ' . self::formatValue($actualValue);

        // Add suggestions
        $suggestions = self::getSuggestionsForTypeMismatch($expectedType, $actualType, $actualValue);
        if ([] !== $suggestions) {
            $message .= PHP_EOL . PHP_EOL . 'Suggestions:' . PHP_EOL . '  - ' . implode(PHP_EOL . '  - ', $suggestions);
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
        $message = 'Missing required property in ' . $dtoClass . '::$' . $property;

        // Add available keys
        if ([] !== $availableKeys) {
            $message .= PHP_EOL . PHP_EOL . 'Available keys in data:' . PHP_EOL . '  - ' . implode(
                PHP_EOL . '  - ',
                $availableKeys
            );
        }

        // Add suggestions for similar keys
        $suggestions = self::getSimilarKeys($property, $availableKeys);
        if ([] !== $suggestions) {
            $message .= PHP_EOL . PHP_EOL . 'Did you mean:' . PHP_EOL . '  - ' . implode(
                PHP_EOL . '  - ',
                $suggestions
            );
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
        $message = 'Cast failed in ' . $dtoClass . '::$' . $property . PHP_EOL .
            '  Cast type: ' . $castType . PHP_EOL .
            '  Value: ' . self::formatValue($value) . PHP_EOL .
            '  Value type: ' . get_debug_type($value);

        if ('' !== $reason) {
            $message .= PHP_EOL . '  Reason: ' . $reason;
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
        $message = 'Error in nested DTO ' . $dtoClass . '::$' . $property . PHP_EOL .
            '  Nested DTO: ' . $nestedDtoClass . PHP_EOL .
            '  Nested property: ' . $nestedProperty . PHP_EOL .
            '  Property path: ' . $property . '.' . $nestedProperty . PHP_EOL . PHP_EOL .
            'Original error:' . PHP_EOL . $originalMessage;

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

            return '"' . $truncated . '"';
        }

        if (is_array($value)) {
            $count = count($value);

            return 'array(' . $count . ' items)';
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
                $suggestions[] = 'Cast the string to ' . $expectedType . ': (int) "' . $actualValue . '" or use a cast in casts() method';
            } else {
                /** @phpstan-ignore-next-line */
                $suggestions[] = 'The string "' . $actualValue . '" is not numeric. Provide a valid number.';
            }
        }

        // Int to string
        if ('int' === $actualType && 'string' === $expectedType) {
            /** @phpstan-ignore-next-line */
            $suggestions[] = 'Cast the integer to string: (string) ' . $actualValue . ' or use "string" cast in casts() method';
        }

        // Array to object
        if ('array' === $actualType && str_contains($expectedType, '\\')) {
            $suggestions[] = 'Convert array to ' . $expectedType . ' using ' . $expectedType . '::fromArray($value)';
        }

        // Null to non-nullable
        if ('null' === $actualType && !str_contains($expectedType, '?')) {
            $suggestions[] = 'Make the property nullable: ?' . $expectedType . ' ${property}';
            $suggestions[] = 'Or provide a non-null value in the data array';
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
