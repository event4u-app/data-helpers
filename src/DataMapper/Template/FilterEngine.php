<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

final class FilterEngine
{
    /** @var array<string, callable(mixed): mixed> */
    private static array $customFilters = [];

    /**
     * Apply filters to a value.
     *
     * @param array<int, string> $filters
     */
    public static function apply(mixed $value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            $value = self::applyFilter($value, $filter);
        }

        return $value;
    }

    /**
     * Register a custom filter.
     *
     * @param callable(mixed): mixed $callback
     */
    public static function registerFilter(string $name, callable $callback): void
    {
        self::$customFilters[$name] = $callback;
    }

    /** Apply a single filter. */
    private static function applyFilter(mixed $value, string $filter): mixed
    {
        $filter = trim($filter);

        // Custom filters
        if (isset(self::$customFilters[$filter])) {
            return (self::$customFilters[$filter])($value);
        }

        // Built-in filters
        return match ($filter) {
            'lower', 'lowercase' => is_string($value) ? strtolower($value) : $value,
            'upper', 'uppercase' => is_string($value) ? strtoupper($value) : $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'ucfirst' => is_string($value) ? ucfirst($value) : $value,
            'ucwords' => is_string($value) ? ucwords($value) : $value,
            'json' => json_encode($value),
            'count' => is_countable($value) ? count($value) : 0,
            'first' => is_array($value) ? reset($value) : $value,
            'last' => is_array($value) ? end($value) : $value,
            'keys' => is_array($value) ? array_keys($value) : [],
            'values' => is_array($value) ? array_values($value) : [],
            'reverse' => is_array($value) ? array_reverse($value) : $value,
            'sort' => is_array($value) ? (function() use ($value) {
                sort($value);
                return $value;
            })() : $value,
            'unique' => is_array($value) ? array_unique($value) : $value,
            'join' => is_array($value) ? implode(', ', $value) : $value,
            'default' => $value ?? '',
            default => $value,
        };
    }
}
