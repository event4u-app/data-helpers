<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Support;

/**
 * Utility class for transforming property names between different naming conventions.
 *
 * Supports transformations between:
 * - camelCase (default for PHP properties)
 * - snake_case (common in databases and APIs)
 * - kebab-case (common in URLs and HTML attributes)
 * - PascalCase (common in class names)
 */
class NameTransformer
{
    /**
     * Convert a string to camelCase.
     *
     * Examples:
     * - user_name → userName
     * - user-name → userName
     * - UserName → userName
     * - user name → userName
     *
     * @param string $name The string to convert
     *
     * @return string The camelCase string
     */
    public static function toCamelCase(string $name): string
    {
        // Handle empty string
        if ('' === $name) {
            return '';
        }

        // Already camelCase
        if (ctype_lower($name[0]) && !str_contains($name, '_') && !str_contains($name, '-') && !str_contains(
            $name,
            ' '
        )) {
            return $name;
        }

        // Convert to words
        $words = self::toWords($name);

        // Handle empty words array
        if ([] === $words) {
            return $name;
        }

        // First word lowercase, rest capitalized
        $result = strtolower($words[0]);
        for ($i = 1; count($words) > $i; $i++) {
            $result .= ucfirst(strtolower($words[$i]));
        }

        return $result;
    }

    /**
     * Convert a string to snake_case.
     *
     * Examples:
     * - userName → user_name
     * - user-name → user_name
     * - UserName → user_name
     * - user name → user_name
     *
     * @param string $name The string to convert
     *
     * @return string The snake_case string
     */
    public static function toSnakeCase(string $name): string
    {
        // Handle empty string
        if ('' === $name) {
            return '';
        }

        // Already snake_case
        if (ctype_lower($name) || (ctype_lower($name[0]) && str_contains($name, '_'))) {
            return strtolower($name);
        }

        // Convert to words
        $words = self::toWords($name);

        // Join with underscores
        return strtolower(implode('_', $words));
    }

    /**
     * Convert a string to kebab-case.
     *
     * Examples:
     * - userName → user-name
     * - user_name → user-name
     * - UserName → user-name
     * - user name → user-name
     *
     * @param string $name The string to convert
     *
     * @return string The kebab-case string
     */
    public static function toKebabCase(string $name): string
    {
        // Handle empty string
        if ('' === $name) {
            return '';
        }

        // Already kebab-case
        if (ctype_lower($name) || (ctype_lower($name[0]) && str_contains($name, '-'))) {
            return strtolower($name);
        }

        // Convert to words
        $words = self::toWords($name);

        // Join with hyphens
        return strtolower(implode('-', $words));
    }

    /**
     * Convert a string to PascalCase.
     *
     * Examples:
     * - userName → UserName
     * - user_name → UserName
     * - user-name → UserName
     * - user name → UserName
     *
     * @param string $name The string to convert
     *
     * @return string The PascalCase string
     */
    public static function toPascalCase(string $name): string
    {
        // Handle empty string
        if ('' === $name) {
            return '';
        }

        // Convert to words
        $words = self::toWords($name);

        // Capitalize all words
        $result = '';
        foreach ($words as $word) {
            $result .= ucfirst(strtolower($word));
        }

        return $result;
    }

    /**
     * Split a string into words.
     *
     * Handles:
     * - snake_case (user_name → [user, name])
     * - kebab-case (user-name → [user, name])
     * - camelCase (userName → [user, Name])
     * - PascalCase (UserName → [User, Name])
     * - spaces (user name → [user, name])
     *
     * @param string $name The string to split
     *
     * @return array<int, string> Array of words
     */
    private static function toWords(string $name): array
    {
        // Replace underscores, hyphens, and spaces with a delimiter
        $name = str_replace(['_', '-', ' '], '|', $name);

        // Split on delimiter
        $parts = explode('|', $name);

        $words = [];
        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            // Split camelCase/PascalCase
            $words = array_merge($words, self::splitCamelCase($part));
        }

        return array_filter($words);
    }

    /**
     * Split a camelCase or PascalCase string into words.
     *
     * Examples:
     * - userName → [user, Name]
     * - UserName → [User, Name]
     * - HTTPResponse → [HTTP, Response]
     *
     * @param string $name The string to split
     *
     * @return array<int, string> Array of words
     */
    private static function splitCamelCase(string $name): array
    {
        // Insert a space before uppercase letters
        $spaced = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);

        if (null === $spaced) {
            return [$name];
        }

        // Handle consecutive uppercase letters (e.g., HTTPResponse)
        $spaced = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $spaced);

        if (null === $spaced) {
            return [$name];
        }

        // Split on spaces
        return explode(' ', $spaced);
    }

    /**
     * Transform a name based on the specified format.
     *
     * @param string $name   The name to transform
     * @param string $format The target format: 'snake_case', 'camelCase', 'kebab-case', 'PascalCase'
     *
     * @return string The transformed name
     */
    public static function transform(string $name, string $format): string
    {
        return match ($format) {
            'snake_case' => self::toSnakeCase($name),
            'camelCase' => self::toCamelCase($name),
            'kebab-case' => self::toKebabCase($name),
            'PascalCase' => self::toPascalCase($name),
            default => $name,
        };
    }
}

