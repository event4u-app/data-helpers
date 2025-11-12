<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Support;

use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\LiteDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\LiteDto\Attributes\EnumSerialize;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\Map;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\Support\FileLoader;
use event4u\DataHelpers\Support\StringFormatDetector;
use Exception;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use UnitEnum;

/**
 * High-performance engine for LiteDto.
 *
 * Optimized for maximum speed (~0.3μs per operation) with minimal overhead.
 * Uses aggressive caching and direct property access.
 */
final class LiteEngine
{
    /**
     * Cache for reflection classes.
     *
     * @var array<class-string, ReflectionClass<object>>
     */
    private static array $reflectionCache = [];

    /**
     * Cache for ConverterMode attribute.
     *
     * @var array<class-string, bool>
     */
    private static array $converterModeCache = [];

    /**
     * Cache for From mappings per class.
     *
     * @var array<class-string, array<string, string>>
     */
    private static array $fromMappingCache = [];

    /**
     * Cache for To mappings per class.
     *
     * @var array<class-string, array<string, string>>
     */
    private static array $toMappingCache = [];

    /**
     * Cache for Hidden properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $hiddenCache = [];

    /**
     * Cache for ConvertEmptyToNull properties per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $convertEmptyCache = [];

    /**
     * Cache for CastWith casters per class.
     *
     * @var array<class-string, array<string, class-string|null>>
     */
    private static array $castWithCache = [];

    /**
     * Cache for EnumSerialize modes per class.
     *
     * @var array<class-string, array<string, string>>
     */
    private static array $enumSerializeCache = [];

    /**
     * Cache for UltraFast mode per class.
     *
     * @var array<class-string, bool>
     */
    private static array $ultraFastCache = [];

    /**
     * Cache for UltraFast attribute instances per class.
     *
     * @var array<class-string, UltraFast|null>
     */
    private static array $ultraFastAttributeCache = [];

    /**
     * Create DTO from data.
     *
     * @param class-string $class
     * @param array<string, mixed>|string|object $data
     */
    public static function createFromData(string $class, mixed $data): object
    {
        // Check for UltraFast mode first
        if (self::isUltraFast($class)) {
            // Check if ConverterMode is enabled for UltraFast
            $converterMode = self::hasConverterMode($class);

            // If data is not an array and ConverterMode is enabled, convert it
            if (!is_array($data) && $converterMode) {
                $data = self::parseWithConverter($data);
            }

            return self::createUltraFast($class, $data);
        }

        // Step 1: Check ConverterMode
        $converterMode = self::hasConverterMode($class);

        // Step 2: Parse data
        if (!is_array($data)) {
            if ($converterMode) {
                $data = self::parseWithConverter($data);
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'LiteDto only accepts arrays in standard mode. Use #[ConverterMode] attribute on %s to enable JSON/XML/CSV support.',
                        $class
                    )
                );
            }
        }

        // Step 3: Get reflection
        $reflection = self::getReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        // Step 4: Build constructor arguments
        $args = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $args[] = self::resolveParameter($reflectionParameter, $data, $reflection);
        }

        // Step 5: Create instance
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Convert DTO to array for JSON serialization.
     * Applies DateTime formatting with #[DateTimeFormat] attribute.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public static function toJsonArray(object $dto, array $context = []): array
    {
        $class = $dto::class;

        // UltraFast mode: check if MapTo is allowed
        if (self::isUltraFast($class)) {
            $data = get_object_vars($dto);
            $reflection = self::getReflection($class);

            // Check if any property has Map, MapTo, EnumSerialize or DateTimeFormat attributes (auto-detect)
            $hasMapTo = false;
            $hasEnumSerialize = false;
            $hasDateTimeFormat = false;
            foreach ($reflection->getProperties() as $prop) {
                if (!empty($prop->getAttributes(Map::class)) || !empty($prop->getAttributes(MapTo::class))) {
                    $hasMapTo = true;
                }
                if (!empty($prop->getAttributes(EnumSerialize::class))) {
                    $hasEnumSerialize = true;
                }
                if (!empty($prop->getAttributes(DateTimeFormat::class))) {
                    $hasDateTimeFormat = true;
                }
                if ($hasMapTo && $hasEnumSerialize && $hasDateTimeFormat) {
                    break;
                }
            }

            // If no attributes to process, just return raw data
            if (!$hasMapTo && !$hasEnumSerialize && !$hasDateTimeFormat) {
                return $data;
            }

            // Process attributes
            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                $value = $data[$name];

                // Check for #[Map] attribute first (bidirectional mapping)
                $mapAttrs = $reflectionProperty->getAttributes(Map::class);
                if (!empty($mapAttrs)) {
                    /** @var Map $map */
                    $map = $mapAttrs[0]->newInstance();
                    $outputName = $map->key;
                }
                // Then check for #[MapTo] attribute
                elseif (!empty($reflectionProperty->getAttributes(MapTo::class))) {
                    /** @var MapTo $mapTo */
                    $mapTo = $reflectionProperty->getAttributes(MapTo::class)[0]->newInstance();
                    $outputName = $mapTo->target;
                } else {
                    $outputName = $name;
                }

                // Check for #[EnumSerialize] attribute
                if ($value instanceof UnitEnum) {
                    $enumSerializeAttrs = $reflectionProperty->getAttributes(EnumSerialize::class);
                    if (!empty($enumSerializeAttrs)) {
                        /** @var EnumSerialize $enumSerialize */
                        $enumSerialize = $enumSerializeAttrs[0]->newInstance();
                        $value = 'value' === $enumSerialize->mode && $value instanceof BackedEnum
                            ? $value->value
                            : $value->name;
                    }
                }

                // Check for #[DateTimeFormat] attribute (JSON serialization only)
                if ($value instanceof DateTimeInterface) {
                    $dateTimeFormatAttrs = $reflectionProperty->getAttributes(
                        DateTimeFormat::class
                    );
                    if (!empty($dateTimeFormatAttrs)) {
                        /** @var DateTimeFormat $dateTimeFormat */
                        $dateTimeFormat = $dateTimeFormatAttrs[0]->newInstance();
                        $value = $dateTimeFormat->format($value);
                    }
                }

                $result[$outputName] = $value;
            }

            return $result;
        }

        $reflection = self::getReflection($class);

        // Get all public properties
        $data = get_object_vars($dto);
        $result = [];

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            // Check if hidden
            if (self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check conditional properties
            if (!self::shouldIncludeConditionalProperty($reflectionProperty, $data[$name], $dto, $context)) {
                continue;
            }

            // Get output name (check for #[MapTo] attribute)
            $outputName = self::getToMapping($class, $name, $reflectionProperty);

            // Convert value (handle nested DTOs and enums) and apply DateTime formatting
            $result[$outputName] = self::convertValueForJson($data[$name], $class, $name, $reflectionProperty);
        }

        return $result;
    }

    /**
     * Convert DTO to array.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public static function toArray(object $dto, array $context = []): array
    {
        $class = $dto::class;

        // UltraFast mode: check if MapTo is allowed
        if (self::isUltraFast($class)) {
            $data = get_object_vars($dto);
            $reflection = self::getReflection($class);

            // Check if any property has Map, MapTo or EnumSerialize attributes (auto-detect)
            // Note: DateTimeFormat is NOT checked here as it only affects JSON serialization, not toArray()
            $hasMapTo = false;
            $hasEnumSerialize = false;
            foreach ($reflection->getProperties() as $prop) {
                if (!empty($prop->getAttributes(Map::class)) || !empty($prop->getAttributes(MapTo::class))) {
                    $hasMapTo = true;
                }
                if (!empty($prop->getAttributes(EnumSerialize::class))) {
                    $hasEnumSerialize = true;
                }
                if ($hasMapTo && $hasEnumSerialize) {
                    break;
                }
            }

            // If no attributes to process, just return raw data
            if (!$hasMapTo && !$hasEnumSerialize) {
                return $data;
            }

            // Process attributes
            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                $value = $data[$name];

                // Check for #[Map] attribute first (bidirectional mapping)
                $mapAttrs = $reflectionProperty->getAttributes(Map::class);
                if (!empty($mapAttrs)) {
                    /** @var Map $map */
                    $map = $mapAttrs[0]->newInstance();
                    $outputName = $map->key;
                }
                // Then check for #[MapTo] attribute
                elseif (!empty($reflectionProperty->getAttributes(MapTo::class))) {
                    /** @var MapTo $mapTo */
                    $mapTo = $reflectionProperty->getAttributes(MapTo::class)[0]->newInstance();
                    $outputName = $mapTo->target;
                } else {
                    $outputName = $name;
                }

                // Check for #[EnumSerialize] attribute
                if ($value instanceof UnitEnum) {
                    $enumSerializeAttrs = $reflectionProperty->getAttributes(EnumSerialize::class);
                    if (!empty($enumSerializeAttrs)) {
                        /** @var EnumSerialize $enumSerialize */
                        $enumSerialize = $enumSerializeAttrs[0]->newInstance();
                        $value = 'value' === $enumSerialize->mode && $value instanceof BackedEnum
                            ? $value->value
                            : $value->name;
                    }
                }

                // Note: DateTime formatting is NOT applied in toArray() - only in JSON serialization

                $result[$outputName] = $value;
            }

            return $result;
        }

        $reflection = self::getReflection($class);

        // Get all public properties
        $data = get_object_vars($dto);
        $result = [];

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            // Check if hidden
            if (self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check conditional properties
            if (!self::shouldIncludeConditionalProperty($reflectionProperty, $data[$name], $dto, $context)) {
                continue;
            }

            // Get output name (check for #[MapTo] attribute)
            $outputName = self::getToMapping($class, $name, $reflectionProperty);

            // Convert value (handle nested DTOs and enums)
            $result[$outputName] = self::convertValue($data[$name], $class, $name, $reflectionProperty);
        }

        return $result;
    }

    /** Resolve parameter value from data.
     * @param array<string, mixed> $data
     * @param ReflectionClass<object> $reflection
     */
    private static function resolveParameter(
        ReflectionParameter $param,
        array $data,
        ReflectionClass $reflection
    ): mixed {
        $name = $param->getName();

        // Get source key (check for #[MapFrom] attribute)
        $sourceKey = self::getFromMapping($reflection->getName(), $name, $param);

        // Get value from data
        $value = $data[$sourceKey] ?? null;

        // Check for #[ConvertEmptyToNull]
        if (self::shouldConvertEmptyToNull($reflection->getName(), $name, $param) && ('' === $value || [] === $value)) {
            $value = null;
        }

        // Check for #[CastWith] - apply custom caster
        $casterClass = self::getCastWith($reflection->getName(), $name, $param);
        if (null !== $casterClass && null !== $value) {
            $value = $casterClass::cast($value);
        }

        // Handle nested DTOs and collections
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            // Check if it's an array (potential collection)
            if ('array' === $typeName && is_array($value)) {
                // Try to extract DTO type from docblock
                $dtoClass = self::extractDtoClassFromDocBlock($param);
                if ($dtoClass && self::isCollection($value)) {
                    return array_map($dtoClass::from(...), $value);
                }
            }

            // Check if it's a LiteDto (nested DTO)
            if (!$type->isBuiltin() && is_subclass_of($typeName, LiteDto::class)) {
                // Single nested DTO
                /** @var class-string<LiteDto> $typeName */
                if (is_array($value) || is_object($value) || is_string($value)) {
                    /** @var array<string, mixed>|object|string $value */
                    return $typeName::from($value);
                }
            }

            // Check if it's DateTime/DateTimeImmutable/Carbon
            if (!$type->isBuiltin() && null !== $value && self::isDateTimeType($typeName)) {
                /** @var class-string $typeName */
                return self::castToDateTime($typeName, $value, $name, $reflection->getName());
            }

            // Check if it's an Enum
            if (!$type->isBuiltin() && enum_exists($typeName) && null !== $value) {
                // Try to cast to enum
                return self::castToEnum($typeName, $value);
            }
        }

        // Handle default values
        if (null === $value && $param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        return $value;
    }

    /**
     * Get MapFrom mapping for a parameter.
     *
     * @param class-string $class
     */
    private static function getFromMapping(string $class, string $name, ReflectionParameter $param): string
    {
        // Check cache
        if (isset(self::$fromMappingCache[$class][$name])) {
            return self::$fromMappingCache[$class][$name];
        }

        // Check for #[Map] attribute first (bidirectional mapping)
        $mapAttrs = $param->getAttributes(Map::class);
        if ([] !== $mapAttrs) {
            /** @var Map $map */
            $map = $mapAttrs[0]->newInstance();
            self::$fromMappingCache[$class][$name] = $map->key;
            return $map->key;
        }

        // Check for #[MapFrom] attribute on parameter
        $attrs = $param->getAttributes(MapFrom::class);
        if ([] !== $attrs) {
            /** @var MapFrom $from */
            $from = $attrs[0]->newInstance();
            self::$fromMappingCache[$class][$name] = $from->source;
            return $from->source;
        }

        // No mapping - use parameter name
        self::$fromMappingCache[$class][$name] = $name;
        return $name;
    }

    /**
     * Get MapTo mapping for a property.
     *
     * @param class-string $class
     */
    private static function getToMapping(string $class, string $name, ReflectionProperty $property): string
    {
        // Check cache
        if (isset(self::$toMappingCache[$class][$name])) {
            return self::$toMappingCache[$class][$name];
        }

        // Check for #[Map] attribute first (bidirectional mapping)
        $mapAttrs = $property->getAttributes(Map::class);
        if ([] !== $mapAttrs) {
            /** @var Map $map */
            $map = $mapAttrs[0]->newInstance();
            self::$toMappingCache[$class][$name] = $map->key;
            return $map->key;
        }

        // Check for #[MapTo] attribute
        $attrs = $property->getAttributes(MapTo::class);
        if ([] !== $attrs) {
            /** @var MapTo $to */
            $to = $attrs[0]->newInstance();
            self::$toMappingCache[$class][$name] = $to->target;
            return $to->target;
        }

        // No mapping - use property name
        self::$toMappingCache[$class][$name] = $name;
        return $name;
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
     * Check if parameter should convert empty to null.
     *
     * @param class-string $class
     */
    private static function shouldConvertEmptyToNull(string $class, string $name, ReflectionParameter $param): bool
    {
        // Check cache
        if (isset(self::$convertEmptyCache[$class][$name])) {
            return self::$convertEmptyCache[$class][$name];
        }

        // Check for #[ConvertEmptyToNull] attribute
        $attrs = $param->getAttributes(ConvertEmptyToNull::class);
        $shouldConvert = [] !== $attrs;

        self::$convertEmptyCache[$class][$name] = $shouldConvert;
        return $shouldConvert;
    }

    /**
     * Get CastWith caster class for property.
     *
     * @param class-string $class
     * @return class-string|null
     */
    private static function getCastWith(
        string $class,
        string $name,
        ReflectionParameter|ReflectionProperty $reflection
    ): ?string {
        if (isset(self::$castWithCache[$class][$name])) {
            return self::$castWithCache[$class][$name];
        }

        $attrs = $reflection->getAttributes(CastWith::class);
        $casterClass = null;

        if ([] !== $attrs) {
            $attr = $attrs[0]->newInstance();
            $casterClass = $attr->casterClass;
        }

        self::$castWithCache[$class][$name] = $casterClass;
        return $casterClass;
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
     * Parse data with converter (JSON, XML, etc.).
     *
     * @return array<string, mixed>
     */
    private static function parseWithConverter(mixed $data): array
    {
        // Handle objects
        if (is_object($data)) {
            return (array)$data;
        }

        // Handle strings
        if (is_string($data)) {
            // Check if it's a file path first
            if (file_exists($data)) {
                return FileLoader::loadAsArray($data);
            }

            $trimmed = trim($data);

            // Try XML first
            if (StringFormatDetector::isXml($trimmed)) {
                $parsed = @simplexml_load_string($data);
                if (false !== $parsed) {
                    $json = json_encode($parsed);
                    if (false !== $json) {
                        /** @var array<string, mixed> */
                        return json_decode($json, true) ?? [];
                    }
                }
            }

            // Try JSON
            if (StringFormatDetector::isJson($trimmed)) {
                /** @var array<string, mixed> */
                return json_decode($data, true) ?? [];
            }

            // Try YAML (fallback for other string formats)
            try {
                $converter = new YamlConverter();
                return $converter->toArray($data);
            } catch (Throwable) {
                throw new InvalidArgumentException('Unable to parse string data. Supported formats: JSON, XML, YAML');
            }
        }

        throw new InvalidArgumentException('Data must be array, string (JSON/XML/YAML) or object');
    }

    /**
     * Check if array is a collection (array of arrays).
     *
     * @param array<mixed> $value
     */
    private static function isCollection(array $value): bool
    {
        if ([] === $value) {
            return false;
        }

        // Check if first element is an array
        $first = reset($value);
        return is_array($first);
    }

    /** Convert value recursively (handle nested DTOs and enums).
     * Note: DateTime formatting is NOT applied here - only in JSON serialization.
     * @param class-string|null $class
     */
    private static function convertValue(
        mixed $value,
        ?string $class = null,
        ?string $propertyName = null,
        ?ReflectionProperty $property = null
    ): mixed {
        // Handle enums
        if ($value instanceof BackedEnum || $value instanceof UnitEnum) {
            if ($class && $propertyName && $property instanceof ReflectionProperty) {
                $mode = self::getEnumSerializeMode($class, $propertyName, $property);
                return self::serializeEnum($value, $mode);
            }
            // Default: serialize to value
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }

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

        return $value;
    }

    /**
     * Convert value recursively for JSON serialization (handle nested DTOs, enums, and DateTime formatting).
     *
     * @param class-string|null $class
     */
    private static function convertValueForJson(
        mixed $value,
        ?string $class = null,
        ?string $propertyName = null,
        ?ReflectionProperty $property = null
    ): mixed {
        // Handle DateTime with #[DateTimeFormat]
        if ($value instanceof DateTimeInterface && $class && $propertyName && $property instanceof ReflectionProperty) {
            $dateTimeFormatAttrs = $property->getAttributes(
                DateTimeFormat::class
            );
            if ([] !== $dateTimeFormatAttrs) {
                /** @var DateTimeFormat $dateTimeFormat */
                $dateTimeFormat = $dateTimeFormatAttrs[0]->newInstance();
                return $dateTimeFormat->format($value);
            }
        }

        // Handle enums
        if ($value instanceof BackedEnum || $value instanceof UnitEnum) {
            if ($class && $propertyName && $property instanceof ReflectionProperty) {
                $mode = self::getEnumSerializeMode($class, $propertyName, $property);
                return self::serializeEnum($value, $mode);
            }
            // Default: serialize to value
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }

        // Handle arrays
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::convertValueForJson($item);
            }
            return $result;
        }

        // Handle DTOs - use toJsonArray() for JSON serialization
        if (is_object($value) && method_exists($value, 'toJsonArray')) {
            return self::convertValueForJson($value->toJsonArray());
        }

        // Fallback to toArray() if toJsonArray() doesn't exist
        if (is_object($value) && method_exists($value, 'toArray')) {
            return self::convertValueForJson($value->toArray());
        }

        return $value;
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
            self::$reflectionCache[$class] = new ReflectionClass($class);
        }

        return self::$reflectionCache[$class];
    }

    /**
     * Extract DTO class from docblock @var annotation.
     *
     * @return class-string|null
     */
    private static function extractDtoClassFromDocBlock(ReflectionParameter $param): ?string
    {
        // Get the declaring class to access property docblocks
        $declaringClass = $param->getDeclaringClass();
        if (!$declaringClass) {
            return null;
        }

        // Try to get property with same name (for promoted properties)
        $paramName = $param->getName();
        if ($declaringClass->hasProperty($paramName)) {
            $property = $declaringClass->getProperty($paramName);
            $docComment = $property->getDocComment();

            if ($docComment) {
                // Look for @var array<ClassName> or @var ClassName[]
                if (preg_match('/@var\s+array<([^>]+)>/', $docComment, $matches)) {
                    $className = trim($matches[1]);
                    if (class_exists($className) && is_subclass_of($className, LiteDto::class)) {
                        return $className;
                    }
                }

                if (preg_match('/@var\s+([^\[\]]+)\[\]/', $docComment, $matches)) {
                    $className = trim($matches[1]);
                    if (class_exists($className) && is_subclass_of($className, LiteDto::class)) {
                        return $className;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a type name is a DateTime-related type.
     *
     * @param string $typeName Type name to check
     * @return bool True if it's DateTime, DateTimeImmutable, Carbon, or CarbonImmutable
     */
    private static function isDateTimeType(string $typeName): bool
    {
        if ('DateTime' === $typeName || 'DateTimeImmutable' === $typeName) {
            return true;
        }

        // Check for Carbon types (if Carbon is installed)
        if (class_exists('Carbon\Carbon')) {
            return 'Carbon\Carbon' === $typeName || 'Carbon\CarbonImmutable' === $typeName;
        }

        return false;
    }

    /**
     * Cast value to DateTime, DateTimeImmutable, Carbon, or CarbonImmutable.
     *
     * Supports:
     * - DateTime/DateTimeImmutable conversion
     * - Carbon/CarbonImmutable (if Carbon is installed)
     * - String parsing with optional format from #[DateTimeFormat]
     * - Automatic format detection as fallback
     * - Unix timestamps (int)
     *
     * @param class-string $dateTimeClass Target class (DateTime, DateTimeImmutable, Carbon\Carbon, Carbon\CarbonImmutable)
     * @param string|null $propertyName Property name for DateTimeFormat attribute lookup
     * @param class-string|null $dtoClass DTO class for DateTimeFormat attribute lookup
     * @return DateTime|DateTimeImmutable DateTime instance or Carbon instance
     */
    private static function castToDateTime(
        string $dateTimeClass,
        mixed $value,
        ?string $propertyName = null,
        ?string $dtoClass = null
    ): DateTime|DateTimeImmutable {
        // If already the correct DateTime instance, return it
        if ($value instanceof $dateTimeClass) {
            return $value; // @phpstan-ignore-line
        }

        // Check if target is Carbon (if installed)
        $isCarbonTarget = class_exists('Carbon\Carbon') && (
            'Carbon\Carbon' === $dateTimeClass || 'Carbon\CarbonImmutable' === $dateTimeClass
        );

        // Convert between DateTime types
        if ('DateTime' === $dateTimeClass || 'Carbon\Carbon' === $dateTimeClass) {
            if ($value instanceof DateTimeImmutable) {
                $dt = DateTime::createFromImmutable($value);
                return $isCarbonTarget ? Carbon::instance($dt) : $dt; // @phpstan-ignore-line
            }
            if ($value instanceof DateTimeInterface) {
                $dt = DateTime::createFromInterface($value);
                return $isCarbonTarget ? Carbon::instance($dt) : $dt; // @phpstan-ignore-line
            }
        }

        if ('DateTimeImmutable' === $dateTimeClass || 'Carbon\CarbonImmutable' === $dateTimeClass) {
            if ($value instanceof DateTime) {
                $dt = DateTimeImmutable::createFromMutable($value);
                return $isCarbonTarget ? CarbonImmutable::instance($dt) : $dt; // @phpstan-ignore-line
            }
            if ($value instanceof DateTimeInterface) {
                $dt = DateTimeImmutable::createFromInterface($value);
                return $isCarbonTarget ? CarbonImmutable::instance($dt) : $dt; // @phpstan-ignore-line
            }
        }

        // Handle null or empty string
        if (null === $value || '' === $value) {
            throw new InvalidArgumentException('Cannot cast null or empty string to ' . $dateTimeClass);
        }

        // Cast from int (timestamp)
        if (is_int($value)) {
            try {
                if ($isCarbonTarget) {
                    return 'Carbon\Carbon' === $dateTimeClass
                        ? Carbon::createFromTimestamp($value) // @phpstan-ignore-line
                        : CarbonImmutable::createFromTimestamp($value); // @phpstan-ignore-line
                }

                $dateTime = 'DateTime' === $dateTimeClass ? new DateTime() : new DateTimeImmutable();
                return $dateTime->setTimestamp($value);
            } catch (Exception $e) {
                throw new InvalidArgumentException(
                    'Cannot cast timestamp to ' . $dateTimeClass . ': ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        // Cast from string
        if (is_string($value)) {
            return self::parseDateTimeFromString($value, $dateTimeClass, $propertyName, $dtoClass);
        }

        throw new InvalidArgumentException('Cannot cast value to ' . $dateTimeClass);
    }

    /**
     * Parse DateTime from string with optional format from #[DateTimeFormat].
     *
     * Strategy:
     * 1. If #[DateTimeFormat] is present, try to parse with that format first
     * 2. If format parsing fails or no format specified, try automatic detection
     * 3. Support for date-only, time-only, and datetime strings
     *
     * @param string $value String value to parse
     * @param class-string $dateTimeClass Target class (DateTime, DateTimeImmutable, Carbon\Carbon, Carbon\CarbonImmutable)
     * @param string|null $propertyName Property name for DateTimeFormat attribute lookup
     * @param class-string|null $dtoClass DTO class for DateTimeFormat attribute lookup
     * @return DateTime|DateTimeImmutable Parsed DateTime instance
     */
    private static function parseDateTimeFromString(
        string $value,
        string $dateTimeClass,
        ?string $propertyName = null,
        ?string $dtoClass = null
    ): DateTime|DateTimeImmutable {
        $isCarbonTarget = class_exists('Carbon\Carbon') && (
            'Carbon\Carbon' === $dateTimeClass || 'Carbon\CarbonImmutable' === $dateTimeClass
        );

        // Try to get DateTimeFormat attribute if property name and DTO class are provided
        $format = null;
        if (null !== $propertyName && null !== $dtoClass) {
            try {
                $reflection = self::getReflection($dtoClass);
                $property = $reflection->getProperty($propertyName);
                $dateTimeFormatAttrs = $property->getAttributes(
                    DateTimeFormat::class
                );
                if (!empty($dateTimeFormatAttrs)) {
                    /** @var DateTimeFormat $dateTimeFormat */
                    $dateTimeFormat = $dateTimeFormatAttrs[0]->newInstance();
                    $format = $dateTimeFormat->format;
                }
            } catch (Exception) {
                // Ignore errors, fall back to automatic detection
            }
        }

        // Strategy 1: Try parsing with specified format
        if (null !== $format) {
            try {
                if ($isCarbonTarget) {
                    $parsed = 'Carbon\Carbon' === $dateTimeClass
                        ? Carbon::createFromFormat($format, $value) // @phpstan-ignore-line
                        : CarbonImmutable::createFromFormat($format, $value); // @phpstan-ignore-line

                    if (false !== $parsed) { // @phpstan-ignore-line
                        return $parsed; // @phpstan-ignore-line
                    }
                } else {
                    $parsed = 'DateTime' === $dateTimeClass
                        ? DateTime::createFromFormat($format, $value)
                        : DateTimeImmutable::createFromFormat($format, $value);

                    if (false !== $parsed) {
                        return $parsed;
                    }
                }
            } catch (Exception) {
                // Format parsing failed, continue to automatic detection
            }
        }

        // Strategy 2: Try automatic detection with common formats
        $formats = [
            // ISO 8601 formats
            'Y-m-d\TH:i:s.uP',      // 2024-01-15T10:30:00.123456+01:00
            'Y-m-d\TH:i:sP',        // 2024-01-15T10:30:00+01:00
            'Y-m-d\TH:i:s',         // 2024-01-15T10:30:00
            // Common datetime formats
            'Y-m-d H:i:s.u',        // 2024-01-15 10:30:00.123456
            'Y-m-d H:i:s',          // 2024-01-15 10:30:00
            'Y-m-d H:i',            // 2024-01-15 10:30
            // Date-only formats
            'Y-m-d',                // 2024-01-15
            'd.m.Y',                // 15.01.2024
            'd/m/Y',                // 15/01/2024
            'm/d/Y',                // 01/15/2024 (US format)
            // Time-only formats
            'H:i:s',                // 10:30:00
            'H:i',                  // 10:30
        ];

        foreach ($formats as $tryFormat) {
            try {
                if ($isCarbonTarget) {
                    $parsed = 'Carbon\Carbon' === $dateTimeClass
                        ? Carbon::createFromFormat($tryFormat, $value) // @phpstan-ignore-line
                        : CarbonImmutable::createFromFormat($tryFormat, $value); // @phpstan-ignore-line

                    if (false !== $parsed) { // @phpstan-ignore-line
                        return $parsed; // @phpstan-ignore-line
                    }
                } else {
                    $parsed = 'DateTime' === $dateTimeClass
                        ? DateTime::createFromFormat($tryFormat, $value)
                        : DateTimeImmutable::createFromFormat($tryFormat, $value);

                    if (false !== $parsed) {
                        return $parsed;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        // Strategy 3: Fallback to native PHP parsing (strtotime)
        try {
            if ($isCarbonTarget) {
                return 'Carbon\Carbon' === $dateTimeClass
                    ? new Carbon($value) // @phpstan-ignore-line
                    : new CarbonImmutable($value); // @phpstan-ignore-line
            }

            return 'DateTime' === $dateTimeClass
                ? new DateTime($value)
                : new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot parse date/time string "%s" to %s. %s',
                    $value,
                    $dateTimeClass,
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Cast value to enum.
     *
     * @param class-string $enumClass
     */
    private static function castToEnum(string $enumClass, mixed $value): mixed
    {
        // If already an enum instance, return it
        if ($value instanceof $enumClass) {
            return $value;
        }

        // Try to cast from value (BackedEnum)
        if (is_subclass_of($enumClass, BackedEnum::class) && (is_int($value) || is_string($value))) {
            /** @var class-string<BackedEnum> $enumClass */
            return $enumClass::from($value);
        }

        // Try to cast from name (UnitEnum)
        if (is_string($value)) {
            /** @var class-string<UnitEnum> $enumClass */
            $cases = $enumClass::cases();
            foreach ($cases as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }

        throw new InvalidArgumentException('Cannot cast value to enum ' . $enumClass);
    }

    /**
     * Get EnumSerialize mode for property.
     *
     * @param class-string $class
     */
    private static function getEnumSerializeMode(
        string $class,
        string $name,
        ReflectionProperty $property
    ): string {
        if (isset(self::$enumSerializeCache[$class][$name])) {
            return self::$enumSerializeCache[$class][$name];
        }

        $attrs = $property->getAttributes(EnumSerialize::class);
        $mode = 'value'; // Default

        if ([] !== $attrs) {
            $attr = $attrs[0]->newInstance();
            $mode = $attr->mode;
        }

        self::$enumSerializeCache[$class][$name] = $mode;
        return $mode;
    }

    /**
     * Serialize enum based on mode.
     *
     * @return string|int|array<string, string|int>
     */
    private static function serializeEnum(BackedEnum|UnitEnum $enum, string $mode): string|int|array
    {
        return match ($mode) {
            'name' => $enum->name,
            'value' => $enum instanceof BackedEnum ? $enum->value : $enum->name,
            'both' => [
                'name' => $enum->name,
                'value' => $enum instanceof BackedEnum ? $enum->value : $enum->name,
            ],
            default => $enum instanceof BackedEnum ? $enum->value : $enum->name,
        };
    }

    /**
     * Check if class has UltraFast attribute.
     *
     * @param class-string $class
     */
    private static function isUltraFast(string $class): bool
    {
        if (isset(self::$ultraFastCache[$class])) {
            return self::$ultraFastCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(UltraFast::class);
        $isUltraFast = [] !== $attrs;

        self::$ultraFastCache[$class] = $isUltraFast;
        return $isUltraFast;
    }

    /**
     * Get UltraFast attribute instance for a class.
     *
     * @param class-string $class
     */
    private static function getUltraFastAttribute(string $class): ?UltraFast
    {
        if (isset(self::$ultraFastAttributeCache[$class])) {
            return self::$ultraFastAttributeCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(UltraFast::class);

        if ([] === $attrs) {
            self::$ultraFastAttributeCache[$class] = null;
            return null;
        }

        /** @var UltraFast $ultraFast */
        $ultraFast = $attrs[0]->newInstance();
        self::$ultraFastAttributeCache[$class] = $ultraFast;
        return $ultraFast;
    }

    /**
     * Create DTO in UltraFast mode (minimal overhead).
     *
     * @param class-string $class
     */
    private static function createUltraFast(string $class, mixed $data): object
    {
        // Only accept arrays in UltraFast mode
        if (!is_array($data)) {
            throw new InvalidArgumentException(
                "UltraFast mode only accepts arrays. Use #[ConverterMode] for JSON/XML support."
            );
        }

        $ultraFast = self::getUltraFastAttribute($class);
        $reflection = self::getReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        // Build constructor arguments
        $args = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $paramName = $reflectionParameter->getName();
            $value = null;

            // Step 1: Check for #[Map] or #[MapFrom] (auto-detect or explicitly allowed)
            $mapAttrs = $reflectionParameter->getAttributes(Map::class);
            $mapFromAttrs = $reflectionParameter->getAttributes(MapFrom::class);
            $hasMap = !empty($mapAttrs);
            $hasMapFrom = !empty($mapFromAttrs);
            $allowMapFrom = ($ultraFast instanceof UltraFast && $ultraFast->allowMapFrom) || $hasMap || $hasMapFrom;

            if ($allowMapFrom && $hasMap) {
                /** @var Map $map */
                $map = $mapAttrs[0]->newInstance();
                $sourceKey = $map->key;
                $value = $data[$sourceKey] ?? null;
            } elseif ($allowMapFrom && $hasMapFrom) {
                /** @var MapFrom $mapFrom */
                $mapFrom = $mapFromAttrs[0]->newInstance();
                $sourceKey = $mapFrom->source;
                $value = $data[$sourceKey] ?? null;
            } else {
                $value = $data[$paramName] ?? null;
            }

            // Step 2: Apply #[ConvertEmptyToNull] if present (auto-detect)
            $convertEmptyAttrs = $reflectionParameter->getAttributes(ConvertEmptyToNull::class);
            if (!empty($convertEmptyAttrs) && ('' === $value || [] === $value)) {
                $value = null;
            }

            // Step 3: Apply #[CastWith] if present (auto-detect or explicitly allowed)
            $castWithAttrs = $reflectionParameter->getAttributes(CastWith::class);
            $hasCastWith = !empty($castWithAttrs);
            $allowCastWith = ($ultraFast instanceof UltraFast && $ultraFast->allowCastWith) || $hasCastWith;

            if ($allowCastWith && $hasCastWith && null !== $value) {
                /** @var CastWith $castWith */
                $castWith = $castWithAttrs[0]->newInstance();
                $casterClass = $castWith->casterClass;

                if (class_exists($casterClass) && method_exists($casterClass, 'cast')) {
                    $value = $casterClass::cast($value);
                }
            }

            // Step 4: Cast DateTime/DateTimeImmutable/Carbon/Enum if needed
            if (null !== $value) {
                $type = $reflectionParameter->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    if (self::isDateTimeType($typeName)) {
                        /** @var class-string $typeName */
                        $value = self::castToDateTime($typeName, $value, $paramName, $class);
                    } elseif (enum_exists($typeName)) {
                        // Cast to enum (auto-detect)
                        if (is_string($value) || is_int($value)) {
                            // Try backed enum first
                            if (is_subclass_of($typeName, BackedEnum::class)) {
                                $value = $typeName::from($value);
                            } else {
                                // Try unit enum by name
                                $value = constant($typeName . '::' . $value);
                            }
                        }
                    }
                }
            }

            $args[] = $value;
        }

        // Create instance
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Check if a property should be included based on conditional attributes.
     *
     * @param mixed $value Property value
     * @param object $dto Dto instance
     * @param array<string, mixed> $context Context data
     */
    private static function shouldIncludeConditionalProperty(
        ReflectionProperty $property,
        mixed $value,
        object $dto,
        array $context
    ): bool {
        // Get all conditional attributes
        $conditionalAttrs = $property->getAttributes(
            ConditionalProperty::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        // No conditional attributes = always include
        if ([] === $conditionalAttrs) {
            return true;
        }

        // All conditional attributes must pass (AND logic)
        foreach ($conditionalAttrs as $attr) {
            /** @var ConditionalProperty $conditional */
            $conditional = $attr->newInstance();
            if (!$conditional->shouldInclude($value, $dto, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all property keys of a DTO class.
     *
     * Returns only properties defined in the final DTO class, not from traits or parent classes.
     * By default, all properties are included (even hidden ones).
     *
     * @param class-string $class DTO class name
     * @param bool $includeHiddenFromArray Include properties with #[Hidden] attribute (default: true)
     * @param bool $includeHiddenFromJson Include properties with #[Hidden] attribute (same as includeHiddenFromArray for LiteDto, default: true)
     * @return array<int, string> Array of property names
     */
    public static function getKeys(
        string $class,
        bool $includeHiddenFromArray = true,
        bool $includeHiddenFromJson = true
    ): array {
        $reflection = self::getReflection($class);

        // Get all public properties
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        // Internal properties from LiteDto that should be excluded
        $internalProperties = [
            'toArrayCache',
            'toJsonCache',
        ];

        $keys = [];

        foreach ($properties as $property) {
            $name = $property->getName();

            // Skip internal properties
            if (in_array($name, $internalProperties, true)) {
                continue;
            }

            // Skip properties not defined in the final DTO class
            if ($property->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            // Check if property should be excluded based on Hidden attribute
            // LiteDto only has #[Hidden] attribute (no separate HiddenFromArray/HiddenFromJson)
            // If either parameter is false, exclude hidden properties
            if ((!$includeHiddenFromArray || !$includeHiddenFromJson) && self::isHidden($class, $name, $property)) {
                continue;
            }

            $keys[] = $name;
        }

        return $keys;
    }
}
