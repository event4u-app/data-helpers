<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

/**
 * Parses mapping entries into structured format.
 *
 * Separates parsing logic from execution logic for better testability and maintainability.
 */
final class MappingParser
{
    /**
     * Parse a single mapping entry.
     *
     * @param string|array{__static__: mixed} $sourcePathOrStatic Source path or static value marker
     * @param string $staticValueMarker Marker for static values (e.g., '__static__')
     * @return array{
     *     isStatic: bool,
     *     sourcePath: string,
     *     filters: array<int, string>,
     *     defaultValue: mixed,
     *     hasFilters: bool
     * }
     */
    public static function parseEntry(
        string|array $sourcePathOrStatic,
        string $staticValueMarker = '__static__'
    ): array {
        // Simple in-method cache for string paths (most common case)
        static $cache = [];
        if (is_string($sourcePathOrStatic)) {
            $cacheKey = $sourcePathOrStatic . '|' . $staticValueMarker;
            if (isset($cache[$cacheKey])) {
                return $cache[$cacheKey];
            }
        }

        // Check if it's a static value
        $isStatic = is_array($sourcePathOrStatic) && isset($sourcePathOrStatic[$staticValueMarker]);
        $sourcePath = $isStatic ? $sourcePathOrStatic[$staticValueMarker] : $sourcePathOrStatic;

        // For static values, no further parsing needed
        if ($isStatic) {
            return [
                'isStatic' => true,
                'sourcePath' => (string)$sourcePath,
                'filters' => [],
                'defaultValue' => null,
                'hasFilters' => false,
            ];
        }

        // For dynamic paths, extract filters and default value
        if (is_string($sourcePath) && (str_contains($sourcePath, '|') || str_contains($sourcePath, '??'))) {
            $extracted = TemplateExpressionProcessor::extractPathAndFilters($sourcePath);

            $result = [
                'isStatic' => false,
                'sourcePath' => $extracted['path'],
                'filters' => $extracted['filters'],
                'defaultValue' => $extracted['default'],
                'hasFilters' => [] !== $extracted['filters'],
            ];
            if (is_string($sourcePathOrStatic)) {
                $cache[$cacheKey] = $result;
            }
            return $result;
        }

        // Simple dynamic path without filters or default
        $result = [
            'isStatic' => false,
            'sourcePath' => (string)$sourcePath,
            'filters' => [],
            'defaultValue' => null,
            'hasFilters' => false,
        ];
        if (is_string($sourcePathOrStatic)) {
            $cache[$cacheKey] = $result;
        }
        return $result;
    }

    /**
     * Parse all mapping entries.
     *
     * @param array<string, string|array{__static__: mixed}> $mapping
     * @return array<string, array{
     *     isStatic: bool,
     *     sourcePath: string,
     *     filters: array<int, string>,
     *     defaultValue: mixed,
     *     hasFilters: bool
     * }>
     */
    public static function parseMapping(
        array $mapping,
        string $staticValueMarker = '__static__'
    ): array {
        $parsed = [];

        foreach ($mapping as $targetPath => $sourcePathOrStatic) {
            $parsed[$targetPath] = self::parseEntry($sourcePathOrStatic, $staticValueMarker);
        }

        return $parsed;
    }
}

