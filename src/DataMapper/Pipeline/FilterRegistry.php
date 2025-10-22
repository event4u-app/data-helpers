<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Pipeline\Filters\Between;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Callback;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Clamp;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\ConvertEmptyToNull;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Count;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\DecodeHtmlEntities;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\DefaultValue;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\First;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Join;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\JsonEncode;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Keys;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Last;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Replace;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Reverse;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Sort;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Ucfirst;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Ucwords;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Unique;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Values;
use InvalidArgumentException;

/**
 * Registry for filter aliases used in template expressions.
 *
 * Automatically discovers aliases from FilterInterface implementations
 * and allows custom filter registration.
 */
final class FilterRegistry
{
    /** @var array<string, class-string<FilterInterface>> */
    private static array $aliasMap = [];

    private static bool $initialized = false;

    /**
     * Register a filter class and its aliases.
     *
     * @param class-string<FilterInterface> $filterClass
     */
    public static function register(string $filterClass): void
    {
        if (!is_subclass_of($filterClass, FilterInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Class %s must implement FilterInterface', $filterClass)
            );
        }

        /** @var FilterInterface $instance */
        $instance = new $filterClass();
        $aliases = $instance->getAliases();

        foreach ($aliases as $alias) {
            self::$aliasMap[$alias] = $filterClass;
        }
    }

    /**
     * Register multiple filter classes at once.
     *
     * @param array<int, class-string<FilterInterface>> $filterClasses
     */
    public static function registerMany(array $filterClasses): void
    {
        foreach ($filterClasses as $filterClass) {
            self::register($filterClass);
        }
    }

    /**
     * Get filter class for an alias.
     *
     * @return class-string<FilterInterface>|null
     */
    public static function get(string $alias): ?string
    {
        self::ensureInitialized();

        return self::$aliasMap[$alias] ?? null;
    }

    /** Check if an alias is registered. */
    public static function has(string $alias): bool
    {
        self::ensureInitialized();

        return isset(self::$aliasMap[$alias]);
    }

    /**
     * Get all registered aliases.
     *
     * @return array<string, class-string<FilterInterface>>
     */
    public static function all(): array
    {
        self::ensureInitialized();

        return self::$aliasMap;
    }

    /** Clear all registered aliases. */
    public static function clear(): void
    {
        self::$aliasMap = [];
        self::$initialized = false;
    }

    /** Initialize with built-in filters. */
    private static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }

        // Register built-in filters (without triggering ensureInitialized recursively)
        $builtInTransformers = [
            // String transformers
            TrimStrings::class,
            LowercaseStrings::class,
            UppercaseStrings::class,
            Ucfirst::class,
            Ucwords::class,
            DecodeHtmlEntities::class,
            Replace::class,

            // Array transformers
            Count::class,
            First::class,
            Last::class,
            Keys::class,
            Values::class,
            Reverse::class,
            Sort::class,
            Unique::class,
            Join::class,

            // Other transformers
            JsonEncode::class,
            DefaultValue::class,
            Between::class,
            Clamp::class,
            ConvertEmptyToNull::class,

            // Callback transformer
            Callback::class,
        ];

        foreach ($builtInTransformers as $filterClass) {
            $instance = new $filterClass();
            $aliases = $instance->getAliases();

            foreach ($aliases as $alias) {
                self::$aliasMap[$alias] = $filterClass;
            }
        }

        self::$initialized = true;
    }
}
