<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

/**
 * Helper class to reverse mapping arrays and templates.
 *
 * Reverses the direction of mappings so that what was the target becomes the source
 * and vice versa. This allows using the same mapping/template for bidirectional mapping.
 *
 * Example:
 *   Original: ['full_name' => '{{ firstName }}']
 *   Reversed: ['firstName' => '{{ full_name }}']
 */
final class MappingReverser
{
    /**
     * Reverse a mapping array.
     *
     * Converts a mapping from source->target to target->source.
     * Handles nested mappings by flattening them with dot-notation.
     *
     * Example:
     *   Input:  ['full_name' => '{{ firstName }}', 'email' => '{{ contact.email }}']
     *   Output: ['firstName' => '{{ full_name }}', 'contact.email' => '{{ email }}']
     *
     * Nested example:
     *   Input:  ['profile' => ['name' => '{{ user.name }}']]
     *   Output: ['user.name' => '{{ profile.name }}']
     *
     * @param array<string|int, mixed> $mapping The mapping to reverse
     * @param string $prefix Internal use for nested mappings
     * @return array<string, string> The reversed mapping
     */
    public static function reverseMapping(array $mapping, string $prefix = ''): array
    {
        $reversed = [];

        foreach ($mapping as $targetPath => $sourcePath) {
            // Skip non-string keys (numeric indices, etc.)
            if (!is_string($targetPath)) {
                continue;
            }

            // Build full target path with prefix
            $fullTargetPath = '' !== $prefix ? $prefix . '.' . $targetPath : $targetPath;

            // Handle nested mappings recursively
            if (is_array($sourcePath)) {
                $nestedReversed = self::reverseMapping($sourcePath, $fullTargetPath);
                $reversed = array_merge($reversed, $nestedReversed);
                continue;
            }

            // Handle string mappings (template syntax or plain paths)
            if (is_string($sourcePath)) {
                // Extract path from {{ }} syntax if present
                $extractedSourcePath = self::extractPathFromTemplate($sourcePath);

                // Swap: what was target becomes source, what was source becomes target
                // Wrap in {{ }} syntax
                $reversed[$extractedSourcePath] = '{{ ' . $fullTargetPath . ' }}';
                continue;
            }

            // Skip callbacks, closures, and other non-reversible values
            // These cannot be automatically reversed
        }

        return $reversed;
    }

    /**
     * Reverse a template array.
     *
     * Templates use a different structure than mappings:
     * - Template: ['profile' => ['name' => 'user.name']]
     * - Reversed: ['user' => ['name' => 'profile.name']]
     *
     * @param array<string|int, mixed> $template The template to reverse
     * @return array<string, mixed> The reversed template
     */
    public static function reverseTemplate(array $template): array
    {
        // First, convert template to mapping format
        $mapping = self::templateToMapping($template);

        // Reverse the mapping
        $reversedMapping = self::reverseMapping($mapping);

        // Convert back to template format
        return self::mappingToTemplate($reversedMapping);
    }

    /**
     * Extract path from template syntax.
     *
     * Removes {{ }} wrapper if present, otherwise returns the string as-is.
     *
     * Examples:
     *   '{{ user.name }}' -> 'user.name'
     *   'user.name'       -> 'user.name'
     *   '{{user.name}}'   -> 'user.name'
     *
     * @param string $template The template string
     * @return string The extracted path
     */
    private static function extractPathFromTemplate(string $template): string
    {
        $trimmed = trim($template);

        // Check if wrapped in {{ }}
        if (str_starts_with($trimmed, '{{') && str_ends_with($trimmed, '}}')) {
            // Remove {{ and }} and trim whitespace
            return trim(substr($trimmed, 2, -2));
        }

        return $trimmed;
    }

    /**
     * Convert template format to mapping format.
     *
     * Template: ['profile' => ['name' => 'user.name']]
     * Mapping:  ['profile.name' => '{{ user.name }}']
     *
     * @param array<string|int, mixed> $template The template
     * @param string $prefix Internal use for nested templates
     * @return array<string, string> The mapping
     */
    private static function templateToMapping(array $template, string $prefix = ''): array
    {
        $mapping = [];

        foreach ($template as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $fullKey = '' !== $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                // Nested template
                $nestedMapping = self::templateToMapping($value, $fullKey);
                $mapping = array_merge($mapping, $nestedMapping);
            } elseif (is_string($value)) {
                // Leaf value - wrap in {{ }} if not already
                if (!str_contains($value, '{{')) {
                    $value = '{{ ' . $value . ' }}';
                }
                $mapping[$fullKey] = $value;
            }
        }

        return $mapping;
    }

    /**
     * Convert mapping format to template format.
     *
     * Mapping:  ['profile.name' => '{{ user.name }}', 'profile.email' => '{{ user.email }}']
     * Template: ['profile' => ['name' => 'user.name', 'email' => 'user.email']]
     *
     * @param array<string, string> $mapping The mapping
     * @return array<string, mixed> The template
     */
    private static function mappingToTemplate(array $mapping): array
    {
        $template = [];

        foreach ($mapping as $targetPath => $sourcePath) {
            // Extract source path from {{ }} syntax
            $extractedSourcePath = self::extractPathFromTemplate($sourcePath);

            // Split target path into parts
            $parts = explode('.', $targetPath);

            // Build nested structure using array path
            $template = self::setNestedValue($template, $parts, $extractedSourcePath);
        }

        return $template;
    }

    /**
     * Set a nested value in an array using a path.
     *
     * @param array<string, mixed> $array The array to modify
     * @param array<int, string> $path The path parts
     * @param string $value The value to set
     * @return array<string, mixed> The modified array
     */
    private static function setNestedValue(array $array, array $path, string $value): array
    {
        if ($path === []) {
            return $array;
        }

        $key = array_shift($path);

        if ($path === []) {
            // Last part - set the value
            $array[$key] = $value;
        } else {
            // Intermediate part - recurse
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array[$key] = self::setNestedValue($array[$key], $path, $value);
        }

        return $array;
    }
}

