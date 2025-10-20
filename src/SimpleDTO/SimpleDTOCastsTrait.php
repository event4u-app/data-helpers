<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\Casts\ArrayCast;
use event4u\DataHelpers\SimpleDTO\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDTO\Casts\CollectionCast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDTO\Casts\DecimalCast;
use event4u\DataHelpers\SimpleDTO\Casts\DTOCast;
use event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast;
use event4u\DataHelpers\SimpleDTO\Casts\EnumCast;
use event4u\DataHelpers\SimpleDTO\Casts\FloatCast;
use event4u\DataHelpers\SimpleDTO\Casts\HashedCast;
use event4u\DataHelpers\SimpleDTO\Casts\IntegerCast;
use event4u\DataHelpers\SimpleDTO\Casts\JsonCast;
use event4u\DataHelpers\SimpleDTO\Casts\StringCast;
use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDTO\Casts\TimestampCast;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/**
 * Trait providing cast functionality for SimpleDTOs.
 *
 * This trait handles all casting logic including:
 * - Built-in cast aliases (boolean, integer, float, string, array, datetime, decimal, json, dto)
 * - Custom cast classes
 * - Cast parameters
 * - Cast instance caching
 * - Auto-detection of nested DTOs
 *
 * Responsibilities:
 * - Define available casts via casts() method
 * - Apply casts to data arrays
 * - Resolve and cache cast instances
 * - Parse cast strings with parameters
 */
trait SimpleDTOCastsTrait
{
    /** @var array<string, object> Cache for cast instances */
    private static array $castCache = [];

    /**
     * Get the casts for the DTO.
     *
     * Override this method to define custom casts for your DTO attributes.
     * Supports Laravel-compatible cast syntax:
     *
     * Built-in types:
     * - 'array' - Cast JSON strings to arrays
     * - 'boolean' / 'bool' - Cast to boolean
     * - 'collection:UserDTO' - Cast to DataCollection of UserDTOs (framework-independent)
     * - 'datetime' / 'datetime:Y-m-d' - Cast to DateTimeImmutable with optional format
     * - 'dto:AddressDTO' - Cast to nested DTO (auto-detected for DTO properties)
     * - 'integer' / 'int' - Cast to integer
     * - 'float' / 'double' - Cast to float
     * - 'string' - Cast to string
     * - 'decimal:2' - Cast to decimal string with precision
     * - 'json' - Cast to/from JSON
     * - 'enum:EnumClass' - Cast to PHP 8.1+ Enum
     * - 'encrypted' - Encrypt/decrypt values using Laravel encryption
     * - 'timestamp' - Cast to/from Unix timestamp
     * - 'hashed' / 'hashed:argon2id' - One-way hash (for passwords)
     *
     * Custom cast classes:
     * - DateTimeCast::class
     * - ArrayCast::class
     * - BooleanCast::class
     * - CollectionCast::class
     * - etc.
     *
     * Cast with parameters:
     * - DateTimeCast::class.':Y-m-d H:i:s'
     * - DecimalCast::class.':2'
     * - CollectionCast::class.':UserDTO'
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the casts for the DTO class.
     *
     * Uses reflection to call the protected casts() method without requiring an instance.
     * Also collects casts from DataCollectionOf attributes and auto-detects nested DTOs.
     *
     * @return array<string, string>
     */
    protected static function getCasts(): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('casts');

            // Create a temporary instance to call the method
            $instance = $reflection->newInstanceWithoutConstructor();

            $casts = $method->invoke($instance);
            if (!is_array($casts)) {
                $casts = [];
            }

            // Merge with casts from DataCollectionOf attributes
            $casts = array_merge($casts, static::getCastsFromAttributes());

            // Merge with auto-detected nested DTOs
            $casts = array_merge($casts, static::getNestedDTOCasts());

            /** @var array<string, string> $casts */
            return $casts;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get casts from DataCollectionOf attributes.
     *
     * @return array<string, string>
     */
    protected static function getCastsFromAttributes(): array
    {
        $casts = [];

        try {
            $reflection = new ReflectionClass(static::class);

            foreach ($reflection->getProperties() as $property) {
                $attributes = $property->getAttributes(
                    DataCollectionOf::class
                );

                foreach ($attributes as $attribute) {
                    /** @var DataCollectionOf $instance */
                    $instance = $attribute->newInstance();

                    // Build cast string: collection:dtoClass
                    $castString = 'collection:' . $instance->dtoClass;

                    $casts[$property->getName()] = $castString;
                }
            }
        } catch (Throwable) {
            // Ignore errors
        }

        return $casts;
    }

    /**
     * Auto-detect nested DTOs from constructor parameters.
     *
     * @return array<string, string>
     */
    protected static function getNestedDTOCasts(): array
    {
        $casts = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (null === $constructor) {
                return [];
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // Check if type is a class and extends SimpleDTO
                if (class_exists($typeName)) {
                    try {
                        $typeReflection = new ReflectionClass($typeName);

                        // Check if it has fromArray method (indicates it's a DTO)
                        if ($typeReflection->hasMethod('fromArray')) {
                            $casts[$parameter->getName()] = 'dto:' . $typeName;
                        }
                    } catch (Throwable) {
                        // Not a DTO, skip
                    }
                }
            }
        } catch (Throwable) {
            // Ignore errors
        }

        return $casts;
    }

    /**
     * Apply casts to the data array.
     *
     * Iterates through all defined casts and applies them to matching keys in the data array.
     * Uses lazy cast resolution to skip properties that are not present or are null.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $casts
     * @return array<string, mixed>
     */
    protected static function applyCasts(array $data, array $casts): array
    {
        foreach ($casts as $key => $cast) {
            // Skip if property is not present in data (lazy resolution)
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // Skip if value is null (optimization)
            // Note: Some casts might need to handle null, but most don't
            if (null === $data[$key]) {
                continue;
            }

            $data[$key] = static::castAttribute($key, $data[$key], $cast, $data);
        }

        return $data;
    }

    /**
     * Cast a single attribute.
     *
     * Resolves built-in cast aliases, parses cast parameters, and applies the cast.
     *
     * @param string $key The attribute key
     * @param mixed $value The value to cast
     * @param string $cast The cast definition
     * @param array<string, mixed> $attributes All attributes (for context)
     * @return mixed The casted value
     */
    protected static function castAttribute(string $key, mixed $value, string $cast, array $attributes): mixed
    {
        // Resolve built-in cast aliases
        $cast = static::resolveBuiltInCast($cast);

        // Parse cast parameters (e.g., "DateTimeCast:Y-m-d")
        [$castClass, $parameters] = static::parseCast($cast);

        // Get or create cast instance
        $caster = static::resolveCaster($castClass, $parameters);

        // Apply the cast
        return $caster->get($value, $attributes);
    }

    /**
     * Apply output casts to the data array (for toArray/jsonSerialize).
     *
     * Uses the set() method of casters to convert values back to their serializable form.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function applyOutputCasts(array $data): array
    {
        $casts = $this->casts();

        foreach ($casts as $key => $cast) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // Resolve built-in cast aliases
            $cast = static::resolveBuiltInCast($cast);

            // Parse cast parameters
            [$castClass, $parameters] = static::parseCast($cast);

            // Get or create cast instance
            $caster = static::resolveCaster($castClass, $parameters);

            // Apply the set() method
            $data[$key] = $caster->set($data[$key], $data);
        }

        return $data;
    }

    /**
     * Resolve built-in cast aliases to their class names.
     *
     * Maps short aliases like 'boolean' to their full class names.
     *
     * @param string $cast The cast definition
     * @return string The resolved cast class name
     */
    protected static function resolveBuiltInCast(string $cast): string
    {
        $builtInCasts = [
            'array' => ArrayCast::class,
            'boolean' => BooleanCast::class,
            'bool' => BooleanCast::class,
            'collection' => CollectionCast::class,
            'datetime' => DateTimeCast::class,
            'decimal' => DecimalCast::class,
            'dto' => DTOCast::class,
            'encrypted' => EncryptedCast::class,
            'enum' => EnumCast::class,
            'float' => FloatCast::class,
            'double' => FloatCast::class,
            'hashed' => HashedCast::class,
            'integer' => IntegerCast::class,
            'int' => IntegerCast::class,
            'json' => JsonCast::class,
            'string' => StringCast::class,
            'timestamp' => TimestampCast::class,
        ];

        // Check if it's a built-in cast
        foreach ($builtInCasts as $alias => $className) {
            if (str_starts_with($cast, $alias)) {
                return str_replace($alias, $className, $cast);
            }
        }

        return $cast;
    }

    /**
     * Parse cast string into class and parameters.
     *
     * Supports formats like:
     * - "DateTimeCast" → ["DateTimeCast", []]
     * - "DateTimeCast:Y-m-d" → ["DateTimeCast", ["Y-m-d"]]
     * - "DecimalCast:2" → ["DecimalCast", ["2"]]
     *
     * @param string $cast The cast definition
     * @return array{0: string, 1: array<int, string>}
     */
    protected static function parseCast(string $cast): array
    {
        if (!str_contains($cast, ':')) {
            return [$cast, []];
        }

        $parts = explode(':', $cast, 2);
        $parameters = explode(',', $parts[1]);

        return [$parts[0], $parameters];
    }

    /**
     * Resolve a caster instance.
     *
     * Creates a new cast instance or returns a cached one.
     * Caching improves performance by avoiding repeated instantiation.
     *
     * @param string $castClass The cast class name
     * @param array<int, string> $parameters Cast parameters
     * @return CastsAttributes The cast instance
     */
    protected static function resolveCaster(string $castClass, array $parameters): CastsAttributes
    {
        $cacheKey = $castClass . ':' . implode(',', $parameters);

        if (!isset(self::$castCache[$cacheKey])) {
            $instance = [] === $parameters
                ? new $castClass()
                : new $castClass(...$parameters);

            if (!$instance instanceof CastsAttributes) {
                throw new \InvalidArgumentException("Cast class {$castClass} must implement CastsAttributes");
            }

            self::$castCache[$cacheKey] = $instance;
        }

        $caster = self::$castCache[$cacheKey];

        if (!$caster instanceof CastsAttributes) {
            throw new \InvalidArgumentException("Cached cast class must implement CastsAttributes");
        }

        return $caster;
    }

    /**
     * Clear the cast cache.
     *
     * Useful for testing or when you need to reset cast instances.
     */
    public static function clearCastCache(): void
    {
        self::$castCache = [];
    }

    /**
     * Get all registered built-in casts.
     *
     * Returns a map of cast aliases to their class names.
     *
     * @return array<string, string>
     */
    public static function getBuiltInCasts(): array
    {
        return [
            'array' => ArrayCast::class,
            'boolean' => BooleanCast::class,
            'bool' => BooleanCast::class,
            'datetime' => DateTimeCast::class,
            'integer' => IntegerCast::class,
            'int' => IntegerCast::class,
            'float' => FloatCast::class,
            'double' => FloatCast::class,
            'string' => StringCast::class,
            'decimal' => DecimalCast::class,
            'json' => JsonCast::class,
        ];
    }
}

