<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;
use event4u\DataHelpers\SimpleDto\Casts\ArrayCast;
use event4u\DataHelpers\SimpleDto\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDto\Casts\CollectionCast;
use event4u\DataHelpers\SimpleDto\Casts\ConvertEmptyToNullCast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDto\Casts\DecimalCast;
use event4u\DataHelpers\SimpleDto\Casts\DtoCast;
use event4u\DataHelpers\SimpleDto\Casts\EncryptedCast;
use event4u\DataHelpers\SimpleDto\Casts\EnumCast;
use event4u\DataHelpers\SimpleDto\Casts\FloatCast;
use event4u\DataHelpers\SimpleDto\Casts\HashedCast;
use event4u\DataHelpers\SimpleDto\Casts\IntegerCast;
use event4u\DataHelpers\SimpleDto\Casts\JsonCast;
use event4u\DataHelpers\SimpleDto\Casts\StringCast;
use event4u\DataHelpers\SimpleDto\Casts\TimestampCast;
use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;
use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;
use InvalidArgumentException;
use ReflectionClass;
use Throwable;

/**
 * Trait providing cast functionality for SimpleDtos.
 *
 * This trait handles all casting logic including:
 * - Built-in cast aliases (boolean, integer, float, string, array, datetime, decimal, json, dto)
 * - Custom cast classes
 * - Cast parameters
 * - Cast instance caching
 * - Auto-detection of nested Dtos
 *
 * Responsibilities:
 * - Define available casts via casts() method
 * - Apply casts to data arrays
 * - Resolve and cache cast instances
 * - Parse cast strings with parameters
 */
trait SimpleDtoCastsTrait
{
    /** @var array<string, object> Cache for cast instances */
    private static array $castCache = [];

    /** @var array<string, array<string, string>> Cache for auto-casts per class */
    private static array $autoCastCache = [];

    /**
     * Get the casts for the Dto.
     *
     * Override this method to define custom casts for your Dto attributes.
     * Supports Laravel-compatible cast syntax:
     *
     * Built-in types:
     * - 'array' - Cast JSON strings to arrays
     * - 'boolean' / 'bool' - Cast to boolean
     * - 'collection:UserDto' - Cast to DataCollection of UserDtos (framework-independent)
     * - 'datetime' / 'datetime:Y-m-d' - Cast to DateTimeImmutable with optional format
     * - 'dto:AddressDto' - Cast to nested Dto (auto-detected for Dto properties)
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
     * - CollectionCast::class.':UserDto'
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the casts for the Dto class.
     *
     * Uses reflection to call the protected casts() method without requiring an instance.
     * Collects casts from multiple sources with the following priority (highest last):
     * 1. Automatic native type casts (only if #[AutoCast] is present) - LOWEST PRIORITY
     * 2. Auto-detected nested Dtos (always applied)
     * 3. Casts from attributes (#[DataCollectionOf], #[ConvertEmptyToNull])
     * 4. Casts from casts() method - HIGHEST PRIORITY
     *
     * @return array<string, string>
     */
    protected static function getCasts(): array
    {
        try {
            // Check if NoCasts attribute is present - skip all casting
            $metadata = ConstructorMetadata::get(static::class);
            if (isset($metadata['classAttributes'][NoCasts::class])) {
                return [];
            }

            $reflection = new ReflectionClass(static::class);
            $method = $reflection->getMethod('casts');

            // Create a temporary instance to call the method
            $instance = $reflection->newInstanceWithoutConstructor();

            $casts = $method->invoke($instance);
            if (!is_array($casts)) {
                $casts = [];
            }

            // Start with automatic native type casts (lowest priority)
            // These are only added if #[AutoCast] is present
            $allCasts = static::getAutoCasts();

            // Merge with auto-detected nested Dtos (medium priority)
            // Nested DTOs should always work regardless of AutoCast
            // Performance: Use + operator instead of array_merge (10-20% faster)
            // Note: Order matters! $b + $a means $b has priority (like array_merge($a, $b))
            $allCasts = static::getNestedDtoCasts() + $allCasts;

            // Merge with casts from attributes (high priority)
            // DataCollectionOf and ConvertEmptyToNull are explicit casts
            // Performance: Use + operator instead of array_merge (10-20% faster)
            $allCasts = static::getCastsFromAttributes() + $allCasts;

            // Merge with casts() method (highest priority)
            // Explicit casts from casts() method always override everything
            // Performance: Use + operator instead of array_merge (10-20% faster)
            $allCasts = $casts + $allCasts;

            /** @var array<string, string> $allCasts */
            return $allCasts;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get casts from attributes (DataCollectionOf, ConvertEmptyToNull, etc.).
     *
     * @return array<string, string>
     */
    protected static function getCastsFromAttributes(): array
    {
        $casts = [];

        try {
            // Use centralized metadata cache
            $metadata = ConstructorMetadata::get(static::class);

            // Check for class-level ConvertEmptyToNull attribute
            $classConvertEmpty = $metadata['classAttributes'][ConvertEmptyToNull::class] ?? null;
            $hasClassLevelConvertEmptyToNull = null !== $classConvertEmpty;

            foreach ($metadata['parameters'] as $param) {
                $propertyName = $param['name'];

                // Check for DataCollectionOf attribute
                if (isset($param['attributes'][DataCollectionOf::class])) {
                    /** @var DataCollectionOf $instance */
                    $instance = $param['attributes'][DataCollectionOf::class];

                    // Build cast string: collection:dtoClass
                    $castString = 'collection:' . $instance->dtoClass;

                    $casts[$propertyName] = $castString;
                }

                // Check for property-level ConvertEmptyToNull attribute
                if (isset($param['attributes'][ConvertEmptyToNull::class])) {
                    // Property-level attribute takes precedence
                    /** @var ConvertEmptyToNull $instance */
                    $instance = $param['attributes'][ConvertEmptyToNull::class];

                    // Build cast string with parameters
                    $castString = ConvertEmptyToNullCast::class;
                    if ($instance->convertZero || $instance->convertStringZero || $instance->convertFalse) {
                        $params = [];
                        if ($instance->convertZero) {
                            $params[] = 'convertZero=1';
                        }
                        if ($instance->convertStringZero) {
                            $params[] = 'convertStringZero=1';
                        }
                        if ($instance->convertFalse) {
                            $params[] = 'convertFalse=1';
                        }
                        $castString .= ':' . implode(',', $params);
                    }

                    $casts[$propertyName] = $castString;
                } elseif ($hasClassLevelConvertEmptyToNull) {
                    // Use class-level attribute settings
                    /** @var ConvertEmptyToNull $classInstance */
                    $classInstance = $classConvertEmpty;

                    // Build cast string with parameters
                    $castString = ConvertEmptyToNullCast::class;
                    if ($classInstance->convertZero || $classInstance->convertStringZero || $classInstance->convertFalse) {
                        $params = [];
                        if ($classInstance->convertZero) {
                            $params[] = 'convertZero=1';
                        }
                        if ($classInstance->convertStringZero) {
                            $params[] = 'convertStringZero=1';
                        }
                        if ($classInstance->convertFalse) {
                            $params[] = 'convertFalse=1';
                        }
                        $castString .= ':' . implode(',', $params);
                    }

                    $casts[$propertyName] = $castString;
                }
            }
        } catch (Throwable) {
            // Ignore errors
        }

        return $casts;
    }

    /**
     * Auto-detect nested Dtos from constructor parameters.
     *
     * @return array<string, string>
     */
    protected static function getNestedDtoCasts(): array
    {
        $casts = [];

        try {
            // Use centralized metadata cache
            $metadata = ConstructorMetadata::get(static::class);

            foreach ($metadata['parameters'] as $param) {
                $typeName = $param['type'];

                // Skip if no type or is builtin
                if (null === $typeName || $param['isBuiltin']) {
                    continue;
                }

                // Check if type is a class and extends SimpleDto
                if (class_exists($typeName)) {
                    try {
                        $typeReflection = new ReflectionClass($typeName);

                        // Check if it has fromArray method (indicates it's a Dto)
                        if ($typeReflection->hasMethod('fromArray')) {
                            $casts[$param['name']] = 'dto:' . $typeName;
                        }
                    } catch (Throwable) {
                        // Not a Dto, skip
                    }
                }
            }
        } catch (Throwable) {
            // Ignore errors
        }

        return $casts;
    }

    /**
     * Cache for AutoCast detection to avoid repeated reflection.
     *
     * @var array<string, array<string, string>>
     */
    private static array $autoCastsCache = [];

    /**
     * Get automatic native type casts based on #[AutoCast] attribute.
     *
     * This method only adds casts for native PHP types (int, string, float, bool, array)
     * when the #[AutoCast] attribute is present at class or property level.
     *
     * Explicit casts (from casts() method, #[DataCollectionOf], nested DTOs) are NOT affected
     * by this method and are ALWAYS applied.
     *
     * Performance: Returns empty array immediately if no #[AutoCast] attribute is found.
     * Results are cached to avoid repeated reflection.
     *
     * @return array<string, string>
     */
    protected static function getAutoCasts(): array
    {
        // Check cache first
        $cacheKey = static::class;
        if (isset(self::$autoCastsCache[$cacheKey])) {
            return self::$autoCastsCache[$cacheKey];
        }

        try {
            // Use centralized metadata cache
            $metadata = ConstructorMetadata::get(static::class);

            // Early return: Check for class-level #[AutoCast] attribute first
            $hasClassLevelAutoCast = isset($metadata['classAttributes'][AutoCast::class]);

            // Early return: If no class-level AutoCast, check if ANY property has it
            if (!$hasClassLevelAutoCast) {
                // Quick scan: Check if ANY parameter has AutoCast attribute
                $hasAnyPropertyAutoCast = false;
                foreach ($metadata['parameters'] as $param) {
                    if (isset($param['attributes'][AutoCast::class])) {
                        $hasAnyPropertyAutoCast = true;
                        break;
                    }
                }

                // Early return: No AutoCast attributes found anywhere
                if (!$hasAnyPropertyAutoCast) {
                    self::$autoCastsCache[$cacheKey] = [];
                    return [];
                }
            }

            // At this point, we know there's at least one AutoCast attribute
            $casts = [];

            foreach ($metadata['parameters'] as $param) {
                $propertyName = $param['name'];

                // Check for property-level #[AutoCast] attribute
                $hasPropertyLevelAutoCast = isset($param['attributes'][AutoCast::class]);

                // Skip if no AutoCast attribute at class or property level
                if (!$hasClassLevelAutoCast && !$hasPropertyLevelAutoCast) {
                    continue;
                }

                // Get the parameter type
                $typeName = $param['type'];
                if (null === $typeName) {
                    continue;
                }

                // Only add casts for native PHP types
                $castClass = match ($typeName) {
                    'int', 'integer' => IntegerCast::class,
                    'string' => StringCast::class,
                    'float', 'double' => FloatCast::class,
                    'bool', 'boolean' => BooleanCast::class,
                    'array' => ArrayCast::class,
                    default => null,
                };

                if (null !== $castClass) {
                    $casts[$propertyName] = $castClass;
                }
            }

            // Cache the result
            self::$autoCastsCache[$cacheKey] = $casts;

            return $casts;
        } catch (Throwable) {
            // Ignore errors - cache empty array
            self::$autoCastsCache[$cacheKey] = [];
            return [];
        }
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
            'dto' => DtoCast::class,
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
                throw new InvalidArgumentException(sprintf('Cast class %s must implement CastsAttributes', $castClass));
            }

            self::$castCache[$cacheKey] = $instance;
        }

        $caster = self::$castCache[$cacheKey];

        if (!$caster instanceof CastsAttributes) {
            throw new InvalidArgumentException('Cached cast class must implement CastsAttributes');
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
