<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use BackedEnum;
use event4u\DataHelpers\Converters\JsonConverter;
use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;
use event4u\DataHelpers\Support\Optional;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Throwable;
use UnitEnum;

/**
 * Ultra-Fast Engine for SimpleDto
 *
 * Provides OtherDto-like performance by bypassing all SimpleDto overhead.
 * Target: <1μs per operation.
 *
 * This engine:
 * - Uses direct reflection (no cache)
 * - Processes only explicitly defined attributes
 * - Skips all pipeline steps
 * - Skips validation, casts, lazy/optional wrapping
 * - Direct constructor call
 *
 * Inspired by OtherDto's minimalist approach.
 */
final class UltraFastEngine
{
    /**
     * Cache for UltraFast attribute per class.
     *
     * @var array<class-string, UltraFast|null>
     */
    private static array $ultraFastCache = [];

    /**
     * Cache for reflection classes.
     *
     * @var array<class-string, ReflectionClass<object>>
     */
    private static array $reflectionCache = [];

    /**
     * Cache for ConverterMode attribute per class.
     *
     * @var array<class-string, bool>
     */
    private static array $converterModeCache = [];

    /**
     * Cache for Hidden properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $hiddenCache = [];

    /**
     * Cache for HiddenFromArray properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $hiddenFromArrayCache = [];

    /**
     * Cache for HiddenFromJson properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $hiddenFromJsonCache = [];

    /**
     * Cache for Visible properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $visibleCache = [];

    /**
     * Cache for ConvertEmptyToNull properties per class.
     *
     * @var array<class-string, array<string, ConvertEmptyToNull|null>>
     */
    private static array $convertEmptyCache = [];

    /**
     * Cache for CastWith casters per class.
     *
     * @var array<class-string, array<string, class-string|null>>
     */
    private static array $castWithCache = [];

    /**
     * Cache for DataCollectionOf per class.
     *
     * @var array<class-string, array<string, class-string|null>>
     */
    private static array $dataCollectionOfCache = [];

    /**
     * Cache for MapInputName per class.
     *
     * @var array<class-string, MapInputName|null>
     */
    private static array $mapInputNameCache = [];

    /**
     * Cache for MapOutputName per class.
     *
     * @var array<class-string, MapOutputName|null>
     */
    private static array $mapOutputNameCache = [];

    /**
     * Cache for Computed methods per class.
     *
     * @var array<class-string, array<string, Computed>>
     */
    private static array $computedCache = [];

    /**
     * Cache for Lazy properties per class.
     *
     * @var array<class-string, array<string, Lazy>>
     */
    private static array $lazyCache = [];

    /**
     * Cache for Optional properties per class.
     *
     * @var array<class-string, array<string, OptionalAttribute|true>>
     */
    private static array $optionalCache = [];

    /**
     * Feature flags cache per class.
     * Stores which features are used by each class to avoid unnecessary checks.
     *
     * @var array<class-string, array{
     *     hasMapFrom: bool,
     *     hasMapTo: bool,
     *     hasHidden: bool,
     *     hasHiddenFromArray: bool,
     *     hasHiddenFromJson: bool,
     *     hasVisible: bool,
     *     hasCastWith: bool,
     *     hasConvertEmptyToNull: bool,
     *     hasDataCollectionOf: bool,
     *     hasMapInputName: bool,
     *     hasMapOutputName: bool,
     *     hasComputed: bool,
     *     hasLazy: bool,
     *     hasOptional: bool
     * }>
     */
    private static array $featureFlags = [];

    /**
     * Check if a class has #[UltraFast] attribute.
     *
     * @param class-string $class
     */
    public static function isUltraFast(string $class): bool
    {
        if (!isset(self::$ultraFastCache[$class])) {
            $reflection = self::getReflection($class);
            $attributes = $reflection->getAttributes(UltraFast::class);
            self::$ultraFastCache[$class] = [] === $attributes ? null : $attributes[0]->newInstance();
        }

        return null !== self::$ultraFastCache[$class];
    }

    /**
     * Get UltraFast attribute for a class.
     *
     * @param class-string $class
     */
    public static function getUltraFastAttribute(string $class): ?UltraFast
    {
        if (!isset(self::$ultraFastCache[$class])) {
            self::isUltraFast($class);
        }

        return self::$ultraFastCache[$class];
    }

    /**
     * Create DTO instance using ultra-fast mode from array.
     *
     * This method only accepts arrays for maximum performance (~0.8μs).
     * For other formats, use createFrom(), createFromJson(), etc.
     *
     * @param class-string $class
     * @param array<string, mixed> $data
     */
    public static function createFromArray(string $class, array $data): object
    {
        $reflection = self::getReflection($class);
        $ultraFast = self::getUltraFastAttribute($class);

        if (!$ultraFast instanceof UltraFast) {
            throw new InvalidArgumentException(sprintf('Class %s does not have #[UltraFast] attribute', $class));
        }

        // Get constructor parameters
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $args[] = self::resolveParameter($param, $data, $ultraFast, $reflection);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Create DTO instance using ultra-fast mode from mixed data.
     *
     * Supports arrays, JSON, XML, YAML with auto-detection (~1.3-1.5μs).
     * Requires #[ConverterMode] attribute for non-array data.
     *
     * @param class-string $class
     * @param array<string, mixed>|string|object $data
     */
    public static function createFrom(string $class, mixed $data): object
    {
        // Fast path: array
        if (is_array($data)) {
            return self::createFromArray($class, $data);
        }

        // Check if ConverterMode is enabled
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'UltraFast mode only accepts arrays. Use #[ConverterMode] attribute on %s to enable JSON/XML/YAML support.',
                    $class
                )
            );
        }

        // Parse with auto-detection
        $data = self::parseWithConverter($data);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from JSON string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromJson(string $class, string $json): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                sprintf('JSON parsing requires #[ConverterMode] attribute on %s.', $class)
            );
        }

        $data = (new JsonConverter())->toArray($json);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from XML string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromXml(string $class, string $xml): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                sprintf('XML parsing requires #[ConverterMode] attribute on %s.', $class)
            );
        }

        $data = (new XmlConverter())->toArray($xml);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from YAML string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromYaml(string $class, string $yaml): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                sprintf('YAML parsing requires #[ConverterMode] attribute on %s.', $class)
            );
        }

        $data = (new YamlConverter())->toArray($yaml);
        return self::createFromArray($class, $data);
    }

    /**
     * Resolve a constructor parameter value.
     *
     * @param array<string, mixed> $data
     * @param ReflectionClass<object> $reflection
     */
    private static function resolveParameter(
        ReflectionParameter $param,
        array $data,
        UltraFast $ultraFast,
        ReflectionClass $reflection
    ): mixed {
        $name = $param->getName();
        $flags = self::getFeatureFlags($reflection->getName());

        // Step 1: Check for #[MapFrom] attribute (automatically detected)
        $mappedName = $name;
        $hasMapFrom = false;

        // First check parameter attributes (for constructor promoted properties)
        $mapFromAttrs = $param->getAttributes(MapFrom::class);
        if ([] !== $mapFromAttrs) {
            /** @var MapFrom $mapFrom */
            $mapFrom = $mapFromAttrs[0]->newInstance();
            $mappedName = $mapFrom->source;
            $hasMapFrom = true;
        } else {
            // Fallback to property attributes
            $property = $reflection->hasProperty($name) ? $reflection->getProperty($name) : null;
            if ($property) {
                $mapFromAttrs = $property->getAttributes(MapFrom::class);
                if (!empty($mapFromAttrs)) {
                    /** @var MapFrom $mapFrom */
                    $mapFrom = $mapFromAttrs[0]->newInstance();
                    $mappedName = $mapFrom->source;
                    $hasMapFrom = true;
                }
            }
        }

        // Step 1.5: Apply MapInputName if no MapFrom attribute (class-level transformation)
        if (!$hasMapFrom && $flags['hasMapInputName']) {
            $mappedName = self::transformInputName($reflection->getName(), $name);
        }

        // Step 2: Get value from data (with nested path support)
        $value = null;
        $found = false;

        // Handle array of sources (fallback)
        if (is_array($mappedName)) {
            foreach ($mappedName as $source) {
                if (str_contains($source, '.')) {
                    $parts = explode('.', $source);
                    $current = $data;
                    $tempFound = true;

                    foreach ($parts as $part) {
                        if (is_array($current) && array_key_exists($part, $current)) {
                            $current = $current[$part];
                        } else {
                            $tempFound = false;
                            break;
                        }
                    }

                    if ($tempFound) {
                        $value = $current;
                        $found = true;
                        break;
                    }
                } elseif (array_key_exists($source, $data)) {
                    $value = $data[$source];
                    $found = true;
                    break;
                }
            }
        } elseif (str_contains($mappedName, '.')) {
            // Check if mappedName contains dots (nested path)
            $parts = explode('.', $mappedName);
            $current = $data;
            $found = true;

            foreach ($parts as $part) {
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } else {
                    $found = false;
                    break;
                }
            }

            if ($found) {
                $value = $current;
            }
        } elseif (array_key_exists($mappedName, $data)) {
            $value = $data[$mappedName];
            $found = true;
        }

        if (!$found) {
            // Handle default values
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            // Handle nullable
            if ($param->allowsNull()) {
                return null;
            }

            throw new InvalidArgumentException('Missing required parameter: ' . $name);
        }

        // Step 3: Handle #[ConvertEmptyToNull] attribute (automatically detected)
        $flags = self::getFeatureFlags($reflection->getName());
        if ($flags['hasConvertEmptyToNull']) {
            $convertEmpty = self::getConvertEmptyToNull($reflection->getName(), $name, $param);
            if ($convertEmpty instanceof ConvertEmptyToNull && self::shouldConvertToNull($value, $convertEmpty)) {
                $value = null;
            }
        }

        // Step 4: Handle #[CastWith] attribute (automatically detected)
        if ($flags['hasCastWith'] && null !== $value) {
            $casterClass = self::getCastWith($reflection->getName(), $name, $param);
            if (null !== $casterClass) {
                $value = $casterClass::cast($value);
            }
        }

        // Step 5: Handle #[DataCollectionOf] attribute (automatically detected)
        if ($flags['hasDataCollectionOf'] && is_array($value) && [] !== $value) {
            $dtoClass = self::getDataCollectionOf($reflection->getName(), $name, $param);
            if (null !== $dtoClass) {
                // Check if it's a collection (array of arrays/objects)
                $isCollection = self::isCollection($value);
                if ($isCollection) {
                    // Convert array of arrays to array of DTOs
                    return array_map(function($item) use ($dtoClass) {
                        if (is_array($item)) {
                            // Ensure item is array<string, mixed>
                            /** @var array<string, mixed> $itemArray */
                            $itemArray = $item;
                            if (self::isUltraFast($dtoClass)) {
                                return self::createFromArray($dtoClass, $itemArray);
                            }
                            return $dtoClass::fromArray($itemArray);
                        }
                        return $item;
                    }, $value);
                }
            }
        }

        // Step 6: Handle nested DTOs (auto-cast)
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            // Check if it's a DTO class (also validates it's a class-string)
            if (class_exists($typeName) && self::isDtoClass($typeName) && is_array($value)) {
                /** @var class-string $typeName */
                /** @var array<string, mixed> $value */
                // Recursively create nested DTO
                if (self::isUltraFast($typeName)) {
                    return self::createFromArray($typeName, $value);
                }
                // Fall back to normal fromArray for non-UltraFast DTOs
                return $typeName::fromArray($value);
            }

            // Check if it's an Enum
            if (enum_exists($typeName) && null !== $value) {
                return self::castToEnum($typeName, $value);
            }
        }

        return $value;
    }

    /**
     * Convert DTO to array using ultra-fast mode.
     *
     * @return array<string, mixed>
     */
    public static function toArray(object $dto): array
    {
        $class = $dto::class;
        $reflection = self::getReflection($class);
        $ultraFast = self::getUltraFastAttribute($class);

        if (!$ultraFast instanceof UltraFast) {
            throw new InvalidArgumentException(sprintf('Class %s does not have #[UltraFast] attribute', $class));
        }

        // Get all public properties
        $data = get_object_vars($dto);

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

        // Fast path: No attributes to process
        if (!$flags['hasMapTo'] && !$flags['hasHidden'] && !$flags['hasHiddenFromArray'] && !$flags['hasVisible'] && !$flags['hasMapOutputName'] && !$flags['hasLazy'] && !$flags['hasComputed']) {
            // Direct conversion without attribute checks
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = self::convertValue($value);
            }
            return $result;
        }

        // Get lazy properties if needed
        $lazyProperties = $flags['hasLazy'] ? self::getLazyProperties($class) : [];

        // Slow path: Process attributes
        $result = [];
        $hasVisible = $flags['hasVisible'];
        $visibleProperties = [];

        // If Visible is used, collect visible properties first
        if ($hasVisible) {
            foreach ($reflection->getProperties() as $reflectionProperty) {
                if (self::isVisible($class, $reflectionProperty->getName(), $reflectionProperty)) {
                    $visibleProperties[$reflectionProperty->getName()] = true;
                }
            }
        }

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            if (!array_key_exists($name, $data)) {
                continue;
            }

            // If Visible is used, only include visible properties
            if ($hasVisible && !isset($visibleProperties[$name])) {
                continue;
            }

            // Check for #[Hidden] attribute (only if flag is set)
            if ($flags['hasHidden'] && self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check for #[HiddenFromArray] attribute (only if flag is set)
            if ($flags['hasHiddenFromArray'] && self::isHiddenFromArray($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check for #[Lazy] attribute - exclude lazy properties from toArray()
            if ($flags['hasLazy'] && isset($lazyProperties[$name])) {
                continue;
            }

            // Check for #[MapTo] attribute (only if flag is set)
            $outputName = $name;
            $hasMapTo = false;

            if ($flags['hasMapTo']) {
                $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                if (!empty($mapToAttrs)) {
                    /** @var MapTo $mapTo */
                    $mapTo = $mapToAttrs[0]->newInstance();
                    $outputName = $mapTo->target;
                    $hasMapTo = true;
                }
            }

            // Apply MapOutputName if no MapTo attribute (class-level transformation)
            if (!$hasMapTo && $flags['hasMapOutputName']) {
                $outputName = self::transformOutputName($class, $name);
            }

            $result[$outputName] = self::convertValue($data[$name]);
        }

        // Add computed properties (only if flag is set)
        if ($flags['hasComputed']) {
            $computedMethods = self::getComputedMethods($class);
            foreach ($computedMethods as $methodName => $computedAttr) {
                // Skip lazy computed properties (not included by default)
                if ($computedAttr->lazy) {
                    continue;
                }

                // Use custom name if provided, otherwise use method name
                $outputName = $computedAttr->name ?? $methodName;

                // Call the computed method
                try {
                    $value = $dto->{$methodName}();
                    $result[$outputName] = self::convertValue($value);
                } catch (Throwable) {
                    // If computation fails, skip it
                    continue;
                }
            }
        }

        return $result;
    }

    /** Convert DTO to JSON using ultra-fast mode. */
    public static function toJson(object $dto, int $options = 0): string
    {
        $class = $dto::class;
        $reflection = self::getReflection($class);
        $ultraFast = self::getUltraFastAttribute($class);

        if (!$ultraFast instanceof UltraFast) {
            throw new InvalidArgumentException(sprintf('Class %s does not have #[UltraFast] attribute', $class));
        }

        // Get all public properties
        $data = get_object_vars($dto);

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

        // Fast path: No attributes to process for JSON
        // Note: HiddenFromArray is NOT checked here because it only affects toArray(), not toJson()
        if (!$flags['hasMapTo'] && !$flags['hasHidden'] && !$flags['hasHiddenFromJson'] && !$flags['hasVisible'] && !$flags['hasMapOutputName']) {
            // Direct conversion without attribute checks
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = self::convertValue($value);
            }
            return json_encode($result, JSON_THROW_ON_ERROR | $options);
        }

        // Slow path: Process attributes
        $result = [];
        $hasVisible = $flags['hasVisible'];
        $visibleProperties = [];

        // If Visible is used, collect visible properties first
        if ($hasVisible) {
            foreach ($reflection->getProperties() as $reflectionProperty) {
                if (self::isVisible($class, $reflectionProperty->getName(), $reflectionProperty)) {
                    $visibleProperties[$reflectionProperty->getName()] = true;
                }
            }
        }

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            if (!array_key_exists($name, $data)) {
                continue;
            }

            // If Visible is used, only include visible properties
            if ($hasVisible && !isset($visibleProperties[$name])) {
                continue;
            }

            // Check for #[Hidden] attribute (only if flag is set)
            if ($flags['hasHidden'] && self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check for #[HiddenFromJson] attribute (only if flag is set)
            if ($flags['hasHiddenFromJson'] && self::isHiddenFromJson($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check for #[MapTo] attribute (only if flag is set)
            $outputName = $name;
            $hasMapTo = false;

            if ($flags['hasMapTo']) {
                $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                if (!empty($mapToAttrs)) {
                    /** @var MapTo $mapTo */
                    $mapTo = $mapToAttrs[0]->newInstance();
                    $outputName = $mapTo->target;
                    $hasMapTo = true;
                }
            }

            // Apply MapOutputName if no MapTo attribute (class-level transformation)
            if (!$hasMapTo && $flags['hasMapOutputName']) {
                $outputName = self::transformOutputName($class, $name);
            }

            $result[$outputName] = self::convertValue($data[$name]);
        }

        return json_encode($result, JSON_THROW_ON_ERROR | $options);
    }

    /** Convert a value recursively (handle nested DTOs). */
    private static function convertValue(mixed $value): mixed
    {
        // Handle arrays
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::convertValue($item);
            }
            return $result;
        }

        // Handle DTOs
        if (is_object($value) && method_exists($value, 'toArray')) {
            return self::convertValue($value->toArray());
        }

        // Handle Enums
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }

    /**
     * Check if a class is a DTO class.
     *
     * @param class-string $class
     */
    private static function isDtoClass(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        return method_exists($class, 'fromArray');
    }

    /**
     * Get reflection class (cached).
     *
     * @param class-string $class
     * @return ReflectionClass<object>
     */
    private static function getReflection(string $class): ReflectionClass
    {
        if (!isset(self::$reflectionCache[$class])) {
            /** @var ReflectionClass<object> $reflection */
            $reflection = new ReflectionClass($class);
            self::$reflectionCache[$class] = $reflection;
        }

        return self::$reflectionCache[$class];
    }

    /**
     * Check if class has ConverterMode attribute.
     *
     * @param class-string $class
     */
    private static function hasConverterMode(string $class): bool
    {
        if (isset(self::$converterModeCache[$class])) {
            return self::$converterModeCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(ConverterMode::class);
        $hasMode = [] !== $attrs;

        self::$converterModeCache[$class] = $hasMode;
        return $hasMode;
    }

    /**
     * Parse data with converter (JSON, XML, YAML, etc.).
     *
     * @return array<string, mixed>
     */
    private static function parseWithConverter(mixed $data): array
    {
        // Handle objects
        if (is_object($data)) {
            return (array)$data;
        }

        // Handle strings (JSON, XML, YAML, CSV)
        if (is_string($data)) {
            $trimmed = trim($data);

            // Try JSON first (most common)
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                try {
                    return (new JsonConverter())->toArray($data);
                } catch (Throwable) {
                    // Fall through to XML
                }
            }

            // Try XML
            if (str_starts_with($trimmed, '<')) {
                try {
                    return (new XmlConverter())->toArray($data);
                } catch (Throwable) {
                    throw new InvalidArgumentException('Invalid XML format');
                }
            }

            // Try YAML (fallback for other string formats)
            try {
                return (new YamlConverter())->toArray($data);
            } catch (Throwable) {
                throw new InvalidArgumentException('Unsupported string format. Expected JSON, XML, or YAML.');
            }
        }

        throw new InvalidArgumentException('Data must be array, string (JSON/XML/YAML), or object');
    }

    /**
     * Get feature flags for a class.
     * Scans all properties once and caches which features are used.
     *
     * @param class-string $class
     * @return array{
     *     hasMapFrom: bool,
     *     hasMapTo: bool,
     *     hasHidden: bool,
     *     hasHiddenFromArray: bool,
     *     hasHiddenFromJson: bool,
     *     hasVisible: bool,
     *     hasCastWith: bool,
     *     hasConvertEmptyToNull: bool,
     *     hasDataCollectionOf: bool,
     *     hasMapInputName: bool,
     *     hasMapOutputName: bool,
     *     hasComputed: bool,
     *     hasLazy: bool,
     *     hasOptional: bool
     * }
     */
    private static function getFeatureFlags(string $class): array
    {
        // Check cache
        if (isset(self::$featureFlags[$class])) {
            return self::$featureFlags[$class];
        }

        // Initialize all flags to false
        $flags = [
            'hasMapFrom' => false,
            'hasMapTo' => false,
            'hasHidden' => false,
            'hasHiddenFromArray' => false,
            'hasHiddenFromJson' => false,
            'hasVisible' => false,
            'hasCastWith' => false,
            'hasConvertEmptyToNull' => false,
            'hasDataCollectionOf' => false,
            'hasMapInputName' => false,
            'hasMapOutputName' => false,
            'hasComputed' => false,
            'hasLazy' => false,
            'hasOptional' => false,
        ];

        // Scan class-level attributes first
        $reflection = self::getReflection($class);

        // Check for MapInputName (class-level)
        if ([] !== $reflection->getAttributes(MapInputName::class)) {
            $flags['hasMapInputName'] = true;
        }

        // Check for MapOutputName (class-level)
        if ([] !== $reflection->getAttributes(MapOutputName::class)) {
            $flags['hasMapOutputName'] = true;
        }

        // Scan all properties
        foreach ($reflection->getProperties() as $reflectionProperty) {
            // Check for MapFrom
            if (!$flags['hasMapFrom'] && [] !== $reflectionProperty->getAttributes(MapFrom::class)) {
                $flags['hasMapFrom'] = true;
            }

            // Check for MapTo
            if (!$flags['hasMapTo'] && [] !== $reflectionProperty->getAttributes(MapTo::class)) {
                $flags['hasMapTo'] = true;
            }

            // Check for Hidden
            if (!$flags['hasHidden'] && [] !== $reflectionProperty->getAttributes(Hidden::class)) {
                $flags['hasHidden'] = true;
            }

            // Check for HiddenFromArray
            if (!$flags['hasHiddenFromArray'] && [] !== $reflectionProperty->getAttributes(HiddenFromArray::class)) {
                $flags['hasHiddenFromArray'] = true;
            }

            // Check for HiddenFromJson
            if (!$flags['hasHiddenFromJson'] && [] !== $reflectionProperty->getAttributes(HiddenFromJson::class)) {
                $flags['hasHiddenFromJson'] = true;
            }

            // Check for Visible
            if (!$flags['hasVisible'] && [] !== $reflectionProperty->getAttributes(Visible::class)) {
                $flags['hasVisible'] = true;
            }

            // Check for CastWith
            if (!$flags['hasCastWith'] && [] !== $reflectionProperty->getAttributes(CastWith::class)) {
                $flags['hasCastWith'] = true;
            }

            // Check for ConvertEmptyToNull
            if (!$flags['hasConvertEmptyToNull'] && [] !== $reflectionProperty->getAttributes(
                ConvertEmptyToNull::class
            )) {
                $flags['hasConvertEmptyToNull'] = true;
            }

            // Check for DataCollectionOf
            if (!$flags['hasDataCollectionOf'] && [] !== $reflectionProperty->getAttributes(DataCollectionOf::class)) {
                $flags['hasDataCollectionOf'] = true;
            }

            // Check for Lazy
            if (!$flags['hasLazy'] && [] !== $reflectionProperty->getAttributes(Lazy::class)) {
                $flags['hasLazy'] = true;
            }

            // Check for Optional (attribute or union type)
            if (!$flags['hasOptional']) {
                // Check for #[Optional] attribute
                if ([] !== $reflectionProperty->getAttributes(OptionalAttribute::class)) {
                    $flags['hasOptional'] = true;
                } else {
                    // Check for Optional union type
                    $type = $reflectionProperty->getType();
                    if ($type instanceof ReflectionUnionType) {
                        foreach ($type->getTypes() as $unionType) {
                            if ($unionType instanceof ReflectionNamedType && Optional::class === $unionType->getName()) {
                                $flags['hasOptional'] = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Scan all methods for Computed
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (!$flags['hasComputed'] && [] !== $reflectionMethod->getAttributes(Computed::class)) {
                $flags['hasComputed'] = true;
            }
        }

        // Cache and return
        self::$featureFlags[$class] = $flags;
        return $flags;
    }

    /**
     * Check if property is hidden.
     *
     * @param class-string $class
     */
    private static function isHidden(string $class, string $name, ReflectionProperty $property): bool
    {
        // Check cache
        if (isset(self::$hiddenCache[$class][$name])) {
            return self::$hiddenCache[$class][$name];
        }

        // Check for #[Hidden] attribute
        $attrs = $property->getAttributes(Hidden::class);
        $isHidden = [] !== $attrs;

        self::$hiddenCache[$class][$name] = $isHidden;
        return $isHidden;
    }

    /**
     * Check if property is hidden from array.
     *
     * @param class-string $class
     */
    private static function isHiddenFromArray(string $class, string $name, ReflectionProperty $property): bool
    {
        // Check cache
        if (isset(self::$hiddenFromArrayCache[$class][$name])) {
            return self::$hiddenFromArrayCache[$class][$name];
        }

        // Check for #[HiddenFromArray] attribute
        $attrs = $property->getAttributes(HiddenFromArray::class);
        $isHidden = [] !== $attrs;

        self::$hiddenFromArrayCache[$class][$name] = $isHidden;
        return $isHidden;
    }

    /**
     * Check if property is hidden from JSON.
     *
     * @param class-string $class
     */
    private static function isHiddenFromJson(string $class, string $name, ReflectionProperty $property): bool
    {
        // Check cache
        if (isset(self::$hiddenFromJsonCache[$class][$name])) {
            return self::$hiddenFromJsonCache[$class][$name];
        }

        // Check for #[HiddenFromJson] attribute
        $attrs = $property->getAttributes(HiddenFromJson::class);
        $isHidden = [] !== $attrs;

        self::$hiddenFromJsonCache[$class][$name] = $isHidden;
        return $isHidden;
    }

    /**
     * Check if property is visible.
     *
     * @param class-string $class
     */
    private static function isVisible(string $class, string $name, ReflectionProperty $property): bool
    {
        // Check cache
        if (isset(self::$visibleCache[$class][$name])) {
            return self::$visibleCache[$class][$name];
        }

        // Check for #[Visible] attribute
        $attrs = $property->getAttributes(Visible::class);
        $isVisible = [] !== $attrs;

        self::$visibleCache[$class][$name] = $isVisible;
        return $isVisible;
    }

    /**
     * Get ConvertEmptyToNull attribute for property.
     *
     * @param class-string $class
     */
    private static function getConvertEmptyToNull(
        string $class,
        string $name,
        ReflectionParameter $param
    ): ?ConvertEmptyToNull
    {
        // Check cache
        if (isset(self::$convertEmptyCache[$class][$name])) {
            return self::$convertEmptyCache[$class][$name];
        }

        // Check for #[ConvertEmptyToNull] attribute
        $attrs = $param->getAttributes(ConvertEmptyToNull::class);
        $attr = [] !== $attrs ? $attrs[0]->newInstance() : null;

        self::$convertEmptyCache[$class][$name] = $attr;
        return $attr;
    }

    /**
     * Get CastWith caster class for property.
     *
     * @param class-string $class
     * @return class-string|null
     */
    private static function getCastWith(string $class, string $name, ReflectionParameter $param): ?string
    {
        // Check cache
        if (isset(self::$castWithCache[$class][$name])) {
            return self::$castWithCache[$class][$name];
        }

        // Check for #[CastWith] attribute
        $attrs = $param->getAttributes(CastWith::class);
        if ([] !== $attrs) {
            /** @var CastWith $castWith */
            $castWith = $attrs[0]->newInstance();
            $casterClass = $castWith->casterClass;
            self::$castWithCache[$class][$name] = $casterClass;
            return $casterClass;
        }

        self::$castWithCache[$class][$name] = null;
        return null;
    }

    /**
     * Get DataCollectionOf class for property.
     *
     * @param class-string $class
     * @return class-string|null
     */
    private static function getDataCollectionOf(string $class, string $name, ReflectionParameter $param): ?string
    {
        // Check cache
        if (isset(self::$dataCollectionOfCache[$class][$name])) {
            return self::$dataCollectionOfCache[$class][$name];
        }

        // Check for #[DataCollectionOf] attribute
        $attrs = $param->getAttributes(DataCollectionOf::class);
        if ([] !== $attrs) {
            /** @var DataCollectionOf $dataCollectionOf */
            $dataCollectionOf = $attrs[0]->newInstance();
            $dtoClass = $dataCollectionOf->dtoClass;
            self::$dataCollectionOfCache[$class][$name] = $dtoClass;
            return $dtoClass;
        }

        self::$dataCollectionOfCache[$class][$name] = null;
        return null;
    }

    /** Check if value should be converted to null based on ConvertEmptyToNull attribute. */
    private static function shouldConvertToNull(mixed $value, ConvertEmptyToNull $attr): bool
    {
        // Empty string
        if ('' === $value) {
            return true;
        }

        // Empty array
        if ([] === $value) {
            return true;
        }

        // Zero (if enabled)
        if (0 === $value && $attr->convertZero) {
            return true;
        }

        // String "0" (if enabled)
        if ('0' === $value && $attr->convertStringZero) {
            return true;
        }
        // False (if enabled)
        return false === $value && $attr->convertFalse;
    }

    /**
     * Check if array is a collection (array of arrays/objects).
     *
     * @param array<mixed, mixed> $data
     */
    private static function isCollection(array $data): bool
    {
        if ([] === $data) {
            return false;
        }

        // Check if all keys are numeric
        $keys = array_keys($data);
        foreach ($keys as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        // Check if first element is array or object
        $first = reset($data);
        return is_array($first) || is_object($first);
    }

    /**
     * Cast value to enum.
     *
     * @param class-string $enumClass
     */
    private static function castToEnum(string $enumClass, mixed $value): UnitEnum|BackedEnum
    {
        // Try BackedEnum first
        if (is_subclass_of($enumClass, BackedEnum::class)) {
            if (!is_int($value) && !is_string($value)) {
                $valueStr = is_scalar($value) ? (string)$value : gettype($value);
                throw new InvalidArgumentException('BackedEnum value must be int or string, got: ' . $valueStr);
            }
            /** @var class-string<BackedEnum> $backedEnumClass */
            $backedEnumClass = $enumClass;
            return $backedEnumClass::from($value);
        }

        // Try UnitEnum
        if (is_subclass_of($enumClass, UnitEnum::class)) {
            /** @var class-string<UnitEnum> $unitEnumClass */
            $unitEnumClass = $enumClass;
            foreach ($unitEnumClass::cases() as $unitEnum) {
                if ($unitEnum->name === $value) {
                    return $unitEnum;
                }
            }
            $valueStr = is_scalar($value) ? (string)$value : gettype($value);
            throw new InvalidArgumentException(sprintf('Invalid enum value: %s for %s', $valueStr, $enumClass));
        }

        throw new InvalidArgumentException(sprintf('Class %s is not an enum', $enumClass));
    }

    /**
     * Get MapInputName attribute for class.
     *
     * @param class-string $class
     */
    private static function getMapInputName(string $class): ?MapInputName
    {
        // Check cache
        if (array_key_exists($class, self::$mapInputNameCache)) {
            return self::$mapInputNameCache[$class];
        }

        // Get attribute
        $reflection = self::getReflection($class);
        $attributes = $reflection->getAttributes(MapInputName::class);

        if ([] === $attributes) {
            self::$mapInputNameCache[$class] = null;
            return null;
        }

        $attr = $attributes[0]->newInstance();
        self::$mapInputNameCache[$class] = $attr;
        return $attr;
    }

    /**
     * Get MapOutputName attribute for class.
     *
     * @param class-string $class
     */
    private static function getMapOutputName(string $class): ?MapOutputName
    {
        // Check cache
        if (array_key_exists($class, self::$mapOutputNameCache)) {
            return self::$mapOutputNameCache[$class];
        }

        // Get attribute
        $reflection = self::getReflection($class);
        $attributes = $reflection->getAttributes(MapOutputName::class);

        if ([] === $attributes) {
            self::$mapOutputNameCache[$class] = null;
            return null;
        }

        $attr = $attributes[0]->newInstance();
        self::$mapOutputNameCache[$class] = $attr;
        return $attr;
    }

    /**
     * Transform property name for input using MapInputName convention.
     *
     * @param class-string $class
     */
    private static function transformInputName(string $class, string $propertyName): string
    {
        $mapInputName = self::getMapInputName($class);
        if (!$mapInputName instanceof MapInputName) {
            return $propertyName;
        }

        return $mapInputName->convention->transform($propertyName);
    }

    /**
     * Transform property name for output using MapOutputName convention.
     *
     * @param class-string $class
     */
    private static function transformOutputName(string $class, string $propertyName): string
    {
        $mapOutputName = self::getMapOutputName($class);
        if (!$mapOutputName instanceof MapOutputName) {
            return $propertyName;
        }

        return $mapOutputName->convention->transform($propertyName);
    }

    /**
     * Get all Computed methods for a class.
     *
     * @param class-string $class
     * @return array<string, Computed>
     */
    private static function getComputedMethods(string $class): array
    {
        // Check cache
        if (isset(self::$computedCache[$class])) {
            return self::$computedCache[$class];
        }

        $computed = [];
        $reflection = self::getReflection($class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $attributes = $reflectionMethod->getAttributes(Computed::class);
            if ([] !== $attributes) {
                $computed[$reflectionMethod->getName()] = $attributes[0]->newInstance();
            }
        }

        self::$computedCache[$class] = $computed;
        return $computed;
    }

    /**
     * Get all Lazy properties for a class.
     *
     * @param class-string $class
     * @return array<string, Lazy>
     */
    private static function getLazyProperties(string $class): array
    {
        // Check cache
        if (isset(self::$lazyCache[$class])) {
            return self::$lazyCache[$class];
        }

        $lazy = [];
        $reflection = self::getReflection($class);

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Lazy::class);
            if ([] !== $attributes) {
                $lazy[$reflectionProperty->getName()] = $attributes[0]->newInstance();
            }
        }

        self::$lazyCache[$class] = $lazy;
        return $lazy;
    }

    /**
     * Get all Optional properties for a class.
     *
     * @param class-string $class
     * @return array<string, OptionalAttribute|true>
     */
    private static function getOptionalProperties(string $class): array
    {
        // Check cache
        if (isset(self::$optionalCache[$class])) {
            return self::$optionalCache[$class];
        }

        $optional = [];
        $reflection = self::getReflection($class);

        foreach ($reflection->getProperties() as $reflectionProperty) {
            // Check for #[Optional] attribute
            $attributes = $reflectionProperty->getAttributes(OptionalAttribute::class);
            if ([] !== $attributes) {
                $optional[$reflectionProperty->getName()] = $attributes[0]->newInstance();
                continue;
            }

            // Check for Optional union type
            $type = $reflectionProperty->getType();
            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType && Optional::class === $unionType->getName()) {
                        $optional[$reflectionProperty->getName()] = true;
                        break;
                    }
                }
            }
        }

        self::$optionalCache[$class] = $optional;
        return $optional;
    }

    /** Clear all caches (for testing). */
    public static function clearCache(): void
    {
        self::$ultraFastCache = [];
        self::$reflectionCache = [];
        self::$converterModeCache = [];
        self::$hiddenCache = [];
        self::$hiddenFromArrayCache = [];
        self::$hiddenFromJsonCache = [];
        self::$visibleCache = [];
        self::$convertEmptyCache = [];
        self::$castWithCache = [];
        self::$dataCollectionOfCache = [];
        self::$mapInputNameCache = [];
        self::$mapOutputNameCache = [];
        self::$computedCache = [];
        self::$lazyCache = [];
        self::$optionalCache = [];
        self::$featureFlags = [];
    }
}
