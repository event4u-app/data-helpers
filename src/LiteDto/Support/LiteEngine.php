<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Support;

use BackedEnum;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\LiteDto\Attributes\EnumSerialize;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Support\StringFormatDetector;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
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
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public static function toArray(object $dto): array
    {
        $class = $dto::class;

        // UltraFast mode: check if MapTo is allowed
        if (self::isUltraFast($class)) {
            $ultraFast = self::getUltraFastAttribute($class);
            $data = get_object_vars($dto);

            // If MapTo is not allowed, just return raw data
            if (!$ultraFast instanceof UltraFast || !$ultraFast->allowMapTo) {
                return $data;
            }

            // Process MapTo attributes
            $reflection = self::getReflection($class);
            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                // Check for #[MapTo] attribute
                $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                if (!empty($mapToAttrs)) {
                    /** @var MapTo $mapTo */
                    $mapTo = $mapToAttrs[0]->newInstance();
                    $outputName = $mapTo->target;
                } else {
                    $outputName = $name;
                }

                $result[$outputName] = $data[$name];
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

        // Handle strings
        if (is_string($data)) {
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
                $converter = new \event4u\DataHelpers\Converters\YamlConverter();
                return $converter->toArray($data);
            } catch (\Throwable) {
                throw new InvalidArgumentException('Unable to parse string data. Supported formats: JSON, XML, YAML');
            }
        }

        throw new InvalidArgumentException('Data must be array, string (JSON/XML/YAML), or object');
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
        // Check if ConverterMode is enabled
        $converterMode = self::hasConverterMode($class);

        // Parse data if not array and ConverterMode is enabled
        if (!is_array($data)) {
            if ($converterMode) {
                $data = self::parseWithConverter($data);
            } else {
                throw new InvalidArgumentException(
                    "UltraFast mode only accepts arrays. Use #[ConverterMode] attribute on {$class} to enable JSON/XML/CSV support."
                );
            }
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

            // Step 1: Check for #[MapFrom] if allowed
            if ($ultraFast instanceof UltraFast && $ultraFast->allowMapFrom) {
                $mapFromAttrs = $reflectionParameter->getAttributes(MapFrom::class);
                if (!empty($mapFromAttrs)) {
                    /** @var MapFrom $mapFrom */
                    $mapFrom = $mapFromAttrs[0]->newInstance();
                    $sourceKey = $mapFrom->source;
                    $value = $data[$sourceKey] ?? null;
                } else {
                    $value = $data[$paramName] ?? null;
                }
            } else {
                $value = $data[$paramName] ?? null;
            }

            // Step 2: Apply #[CastWith] if allowed and value is not null
            if ($ultraFast instanceof UltraFast && $ultraFast->allowCastWith && null !== $value) {
                $castWithAttrs = $reflectionParameter->getAttributes(CastWith::class);
                if (!empty($castWithAttrs)) {
                    /** @var CastWith $castWith */
                    $castWith = $castWithAttrs[0]->newInstance();
                    $casterClass = $castWith->casterClass;

                    if (class_exists($casterClass) && method_exists($casterClass, 'cast')) {
                        $value = $casterClass::cast($value);
                    }
                }
            }

            $args[] = $value;
        }

        // Create instance
        return $reflection->newInstanceArgs($args);
    }
}
