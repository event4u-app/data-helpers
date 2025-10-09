<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Count;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\DefaultValue;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\First;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Join;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\JsonEncode;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Keys;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Last;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Reverse;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Sort;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Ucfirst;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Ucwords;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Unique;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\UppercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\Values;
use InvalidArgumentException;

/**
 * Registry for transformer aliases used in template expressions.
 *
 * Automatically discovers aliases from TransformerInterface implementations
 * and allows custom transformer registration.
 */
final class TransformerRegistry
{
    /** @var array<string, class-string<TransformerInterface>> */
    private static array $aliasMap = [];

    private static bool $initialized = false;

    /**
     * Register a transformer class and its aliases.
     *
     * @param class-string<TransformerInterface> $transformerClass
     */
    public static function register(string $transformerClass): void
    {
        if (!is_subclass_of($transformerClass, TransformerInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Class %s must implement TransformerInterface', $transformerClass)
            );
        }

        /** @var TransformerInterface $instance */
        $instance = new $transformerClass();
        $aliases = $instance->getAliases();

        foreach ($aliases as $alias) {
            self::$aliasMap[$alias] = $transformerClass;
        }
    }

    /**
     * Register multiple transformer classes at once.
     *
     * @param array<int, class-string<TransformerInterface>> $transformerClasses
     */
    public static function registerMany(array $transformerClasses): void
    {
        foreach ($transformerClasses as $transformerClass) {
            self::register($transformerClass);
        }
    }

    /**
     * Get transformer class for an alias.
     *
     * @return class-string<TransformerInterface>|null
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
     * @return array<string, class-string<TransformerInterface>>
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

    /** Initialize with built-in transformers. */
    private static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }

        // Register built-in transformers (without triggering ensureInitialized recursively)
        $builtInTransformers = [
            // String transformers
            TrimStrings::class,
            LowercaseStrings::class,
            UppercaseStrings::class,
            Ucfirst::class,
            Ucwords::class,

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
        ];

        foreach ($builtInTransformers as $transformerClass) {
            $instance = new $transformerClass();
            $aliases = $instance->getAliases();

            foreach ($aliases as $alias) {
                self::$aliasMap[$alias] = $transformerClass;
            }
        }

        self::$initialized = true;
    }
}

