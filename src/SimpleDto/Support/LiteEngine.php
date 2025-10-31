<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Support;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\LiteDto\Attributes\AutoCast;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\Computed;
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;
use event4u\DataHelpers\LiteDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\LiteDto\Attributes\EnumSerialize;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\LiteDto\Attributes\Lazy;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapInputName;
use event4u\DataHelpers\LiteDto\Attributes\MapOutputName;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\NoAttributes;
use event4u\DataHelpers\LiteDto\Attributes\NoCasts;
use event4u\DataHelpers\LiteDto\Attributes\NoValidation;
use event4u\DataHelpers\LiteDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\LiteDto\Attributes\RuleGroup;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\Attributes\ValidateRequest;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Nullable;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Sometimes;
use event4u\DataHelpers\LiteDto\Attributes\Visible;
use event4u\DataHelpers\LiteDto\Attributes\WithMessage;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\LiteDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\LiteDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\Support\Optional;
use event4u\DataHelpers\Support\StringFormatDetector;
use event4u\DataHelpers\Validation\ValidationResult;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
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
     * @var array<class-string, array<string, Visible|null>>
     */
    private static array $visibleCache = [];

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
     * Cache for Validation Rules per class.
     *
     * @var array<class-string, array<string, array<ValidationAttribute>>>
     */
    private static array $validationRulesCache = [];

    /**
     * Cache for Nullable meta-attribute per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $validationNullableCache = [];

    /**
     * Cache for Sometimes meta-attribute per class.
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $validationSometimesCache = [];

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
     * Cache for MapInputName attribute per class.
     *
     * @var array<class-string, MapInputName|null>
     */
    private static array $mapInputNameCache = [];

    /**
     * Cache for MapOutputName attribute per class.
     *
     * @var array<class-string, MapOutputName|null>
     */
    private static array $mapOutputNameCache = [];

    /**
     * Cache for DataCollectionOf attributes per class.
     *
     * @var array<class-string, array<string, class-string|null>>
     */
    private static array $dataCollectionOfCache = [];

    /**
     * Cache for Computed methods per class.
     *
     * @var array<class-string, array<string, Computed>>
     */
    private static array $computedCache = [];

    /**
     * Cache for Lazy properties per class.
     *
     * @var array<class-string, array<string, true>>
     */
    private static array $lazyCache = [];

    /**
     * Cache for Optional properties per class.
     *
     * @var array<class-string, array<string, true>>
     */
    private static array $optionalCache = [];

    /**
     * Cache for Conditional Properties per class.
     *
     * @var array<class-string, array<string, array<ConditionalProperty>>>
     */
    private static array $conditionalPropertiesCache = [];

    /**
     * Cache for RuleGroup attributes per class.
     *
     * @var array<class-string, array<string, RuleGroup|null>>
     */
    private static array $ruleGroupCache = [];

    /**
     * Cache for WithMessage attributes per class.
     *
     * @var array<class-string, array<string, WithMessage|null>>
     */
    private static array $withMessageCache = [];

    /**
     * Feature flag: Does this class have Computed methods?
     *
     * @var array<class-string, bool>
     */
    private static array $hasComputedCache = [];

    /**
     * Feature flag: Does this class have Lazy properties?
     *
     * @var array<class-string, bool>
     */
    private static array $hasLazyCache = [];

    /**
     * Cache for NoValidation attribute per class.
     *
     * @var array<class-string, bool>
     */
    private static array $noValidationCache = [];

    /**
     * Cache for ValidateRequest attribute per class.
     *
     * @var array<class-string, ValidateRequest|null>
     */
    private static array $validateRequestCache = [];

    /**
     * Cache for NoCasts attribute per class.
     *
     * @var array<class-string, bool>
     */
    private static array $noCastsCache = [];

    /**
     * Cache for AutoCast attribute per class and property.
     * Structure: [class => [propertyName => bool]]
     *
     * @var array<class-string, array<string, bool>>
     */
    private static array $autoCastCache = [];

    /**
     * Cache for class-level AutoCast attribute.
     *
     * @var array<class-string, bool>
     */
    private static array $classAutoCastCache = [];

    /**
     * Feature flags cache for Normal mode per class.
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
     *     hasEnumSerialize: bool,
     *     hasDataCollectionOf: bool,
     *     hasMapInputName: bool,
     *     hasMapOutputName: bool,
     *     hasComputed: bool,
     *     hasLazy: bool,
     *     hasValidation: bool,
     *     hasRuleGroup: bool,
     *     hasWithMessage: bool,
     *     hasAnyArrayAttribute: bool,
     *     hasAnyJsonAttribute: bool,
     *     hasAutoCast: bool,
     *     hasNoCasts: bool,
     * }>
     */
    private static array $featureFlags = [];

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

        // Hook: beforeCreate (allow modifying input data)
        // Create temporary instance to call hook
        $tempReflection = new ReflectionClass($class);
        $tempInstance = $tempReflection->newInstanceWithoutConstructor();
        if ($tempReflection->hasMethod('beforeCreate')) {
            $method = $tempReflection->getMethod('beforeCreate');
            $method->invokeArgs($tempInstance, [&$data]);
        }

        // Hook: beforeMapping (before property mapping)
        if ($tempReflection->hasMethod('beforeMapping')) {
            $method = $tempReflection->getMethod('beforeMapping');
            $method->invokeArgs($tempInstance, [&$data]);
        }

        // Step 3: Get reflection
        $reflection = self::getReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            $instance = new $class();

            // Hook: afterMapping
            if ($reflection->hasMethod('afterMapping')) {
                $method = $reflection->getMethod('afterMapping');
                $method->invoke($instance);
            }

            // Hook: afterCreate
            if ($reflection->hasMethod('afterCreate')) {
                $method = $reflection->getMethod('afterCreate');
                $method->invoke($instance);
            }

            return $instance;
        }

        // Step 4: Build constructor arguments
        $args = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $args[] = self::resolveParameter($reflectionParameter, $data, $reflection, $tempInstance);
        }

        // Step 5: Create instance
        $instance = $reflection->newInstanceArgs($args);

        // Hook: afterMapping (after property mapping and casting)
        if ($reflection->hasMethod('afterMapping')) {
            $method = $reflection->getMethod('afterMapping');
            $method->invoke($instance);
        }

        // Hook: afterCreate (after instance creation)
        if ($reflection->hasMethod('afterCreate')) {
            $method = $reflection->getMethod('afterCreate');
            $method->invoke($instance);
        }

        return $instance;
    }

    /**
     * Convert DTO to array.
     *
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public static function toArray(object $dto, array $context = []): array
    {
        $class = $dto::class;

        // UltraFast mode: auto-detect attributes with feature flags
        if (self::isUltraFast($class)) {
            // Get feature flags (cached after first call)
            $flags = self::getFeatureFlags($class);

            // Fast path: No attributes that affect toArray()
            if (!$flags['hasAnyArrayAttribute']) {
                return get_object_vars($dto);
            }

            // Slow path: Process attributes
            $reflection = self::getReflection($class);
            $data = get_object_vars($dto);

            // Build skip set for Hidden/HiddenFromArray/Lazy properties (only if needed)
            $toSkip = null;
            if ($flags['hasHidden'] || $flags['hasHiddenFromArray'] || $flags['hasLazy']) {
                $toSkip = [];
                if ($flags['hasHidden']) {
                    $toSkip += self::$hiddenCache[$class] ?? [];
                }
                if ($flags['hasHiddenFromArray']) {
                    $toSkip += self::$hiddenFromArrayCache[$class] ?? [];
                }
                if ($flags['hasLazy']) {
                    $toSkip += self::$lazyCache[$class] ?? [];
                }
            }

            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                // Skip Hidden/HiddenFromArray/Lazy properties (only if skip set exists)
                if (null !== $toSkip && isset($toSkip[$name])) {
                    continue;
                }

                $value = $data[$name];

                // Unwrap Optional values (only if flag is set)
                if ($flags['hasOptional'] && $value instanceof Optional) {
                    // Skip empty Optional values (not present)
                    if ($value->isEmpty()) {
                        continue;
                    }
                    // Unwrap present Optional values
                    $value = $value->get();
                }

                // Check conditional properties (only if flag is set)
                if ($flags['hasConditionalProperties'] && !self::shouldIncludeConditionalProperty(
                    $class,
                    $name,
                    $value,
                    $dto,
                    $context
                )) {
                    continue;
                }

                // Get output name (check for #[MapTo] attribute) - only if flag is set
                $outputName = $name;
                if ($flags['hasMapTo']) {
                    $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                    if (!empty($mapToAttrs)) {
                        /** @var MapTo $mapTo */
                        $mapTo = $mapToAttrs[0]->newInstance();
                        $outputName = $mapTo->target;
                    }
                }

                // Handle enums with #[EnumSerialize] (only if flag is set)
                if ($flags['hasEnumSerialize'] && ($value instanceof BackedEnum || $value instanceof UnitEnum)) {
                    $mode = self::getEnumSerializeMode($class, $name, $reflectionProperty);
                    $value = self::serializeEnum($value, $mode);
                }

                // Convert value only for complex types (nested DTOs, arrays)
                if (is_object($value) || is_array($value)) {
                    $result[$outputName] = self::convertValue($value, $class, $name, $reflectionProperty);
                } else {
                    $result[$outputName] = $value;
                }
            }

            // Add computed properties - only if flag is set
            if ($flags['hasComputed']) {
                $computedMethods = self::getComputedMethods($class);
                foreach ($computedMethods as $name => $computed) {
                    // Skip lazy computed properties
                    if ($computed->lazy) {
                        continue;
                    }

                    // Get method name (use original method name, not custom name)
                    $methodName = null;
                    foreach ($reflection->getMethods() as $method) {
                        $attrs = $method->getAttributes(Computed::class);
                        if ([] !== $attrs) {
                            /** @var Computed $attr */
                            $attr = $attrs[0]->newInstance();
                            $attrName = $attr->name ?? $method->getName();
                            if ($attrName === $name) {
                                $methodName = $method->getName();
                                break;
                            }
                        }
                    }

                    if ($methodName && $reflection->hasMethod($methodName)) {
                        $method = $reflection->getMethod($methodName);
                        if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                            $result[$name] = $method->invoke($dto);
                        }
                    }
                }
            }

            return $result;
        }

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

        $reflection = self::getReflection($class);

        // Get all public properties
        $data = get_object_vars($dto);
        $result = [];

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            // Check if hidden (from both array and JSON) - only if flag is set
            if ($flags['hasHidden'] && self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check if hidden from array specifically - only if flag is set
            if ($flags['hasHiddenFromArray'] && self::isHiddenFromArray($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check if lazy - only if flag is set
            if ($flags['hasLazy']) {
                $lazyProperties = self::getLazyProperties($class);
                if (isset($lazyProperties[$name])) {
                    continue;
                }
            }

            $value = $data[$name];

            // Unwrap Optional values (only if flag is set)
            if ($flags['hasOptional'] && $value instanceof Optional) {
                // Skip empty Optional values (not present)
                if ($value->isEmpty()) {
                    continue;
                }
                // Unwrap present Optional values
                $value = $value->get();
            }

            // Check conditional properties (only if flag is set)
            if ($flags['hasConditionalProperties'] && !self::shouldIncludeConditionalProperty(
                $class,
                $name,
                $value,
                $dto,
                $context
            )) {
                continue;
            }

            // Get output name (check for #[MapTo] or #[MapOutputName] attribute) - only if flag is set
            $outputName = $name;
            if ($flags['hasMapTo'] || $flags['hasMapOutputName']) {
                $outputName = self::getToMapping($class, $name, $reflectionProperty);
            }

            // Convert value (handle nested DTOs and enums) - always needed for nested DTOs
            $result[$outputName] = self::convertValue($value, $class, $name, $reflectionProperty);
        }

        // Add computed properties - only if flag is set
        if ($flags['hasComputed']) {
            $computedMethods = self::getComputedMethods($class);
            foreach ($computedMethods as $name => $computed) {
                // Skip lazy computed properties
                if ($computed->lazy) {
                    continue;
                }

                // Get method name (use original method name, not custom name)
                $methodName = null;
                foreach ($reflection->getMethods() as $method) {
                    $attrs = $method->getAttributes(Computed::class);
                    if ([] !== $attrs) {
                        /** @var Computed $attr */
                        $attr = $attrs[0]->newInstance();
                        $attrName = $attr->name ?? $method->getName();
                        if ($attrName === $name) {
                            $methodName = $method->getName();
                            break;
                        }
                    }
                }

                if ($methodName && $reflection->hasMethod($methodName)) {
                    $method = $reflection->getMethod($methodName);
                    if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                        $result[$name] = $method->invoke($dto);
                    }
                }
            }
        }

        // Hook: beforeSerialization (allow modifying output data)
        $reflection = new ReflectionClass($dto);
        if ($reflection->hasMethod('beforeSerialization')) {
            $method = $reflection->getMethod('beforeSerialization');
            $method->invokeArgs($dto, [&$result]);
        }

        // Hook: afterSerialization (allow modifying and returning output data)
        if ($reflection->hasMethod('afterSerialization')) {
            $method = $reflection->getMethod('afterSerialization');
            $result = $method->invoke($dto, $result);
        }

        return $result;
    }

    /**
     * Convert DTO to array for JSON serialization.
     *
     * Similar to toArray() but respects HiddenFromJson instead of HiddenFromArray.
     *
     * @param object $dto The DTO instance
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public static function toJsonArray(object $dto, array $context = []): array
    {
        $class = $dto::class;

        // UltraFast mode: auto-detect attributes with feature flags
        if (self::isUltraFast($class)) {
            // Get feature flags (cached after first call)
            $flags = self::getFeatureFlags($class);

            // Fast path: No attributes that affect toJsonArray()
            if (!$flags['hasAnyJsonAttribute']) {
                return get_object_vars($dto);
            }

            // Slow path: Process attributes
            $reflection = self::getReflection($class);
            $data = get_object_vars($dto);

            // Build skip set for Hidden/HiddenFromJson/Lazy properties (only if needed)
            $toSkip = null;
            if ($flags['hasHidden'] || $flags['hasHiddenFromJson'] || $flags['hasLazy']) {
                $toSkip = [];
                if ($flags['hasHidden']) {
                    $toSkip += self::$hiddenCache[$class] ?? [];
                }
                if ($flags['hasHiddenFromJson']) {
                    $toSkip += self::$hiddenFromJsonCache[$class] ?? [];
                }
                if ($flags['hasLazy']) {
                    $toSkip += self::$lazyCache[$class] ?? [];
                }
            }

            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                // Skip Hidden/HiddenFromJson/Lazy properties (only if skip set exists)
                if (null !== $toSkip && isset($toSkip[$name])) {
                    continue;
                }

                $value = $data[$name];

                // Unwrap Optional values (only if flag is set)
                if ($flags['hasOptional'] && $value instanceof Optional) {
                    // Skip empty Optional values (not present)
                    if ($value->isEmpty()) {
                        continue;
                    }
                    // Unwrap present Optional values
                    $value = $value->get();
                }

                // Check conditional properties (only if flag is set)
                if ($flags['hasConditionalProperties'] && !self::shouldIncludeConditionalProperty(
                    $class,
                    $name,
                    $value,
                    $dto,
                    $context
                )) {
                    continue;
                }

                // Get output name (check for #[MapTo] attribute) - only if flag is set
                $outputName = $name;
                if ($flags['hasMapTo']) {
                    $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                    if (!empty($mapToAttrs)) {
                        /** @var MapTo $mapTo */
                        $mapTo = $mapToAttrs[0]->newInstance();
                        $outputName = $mapTo->target;
                    }
                }

                // Handle enums with #[EnumSerialize] (only if flag is set)
                if ($flags['hasEnumSerialize'] && ($value instanceof BackedEnum || $value instanceof UnitEnum)) {
                    $mode = self::getEnumSerializeMode($class, $name, $reflectionProperty);
                    $value = self::serializeEnum($value, $mode);
                }

                // Convert value only for complex types (nested DTOs, arrays)
                if (is_object($value) || is_array($value)) {
                    $result[$outputName] = self::convertValue($value, $class, $name, $reflectionProperty);
                } else {
                    $result[$outputName] = $value;
                }
            }

            // Add computed properties - only if flag is set
            if ($flags['hasComputed']) {
                $computedMethods = self::getComputedMethods($class);
                foreach ($computedMethods as $name => $computed) {
                    // Skip lazy computed properties
                    if ($computed->lazy) {
                        continue;
                    }

                    // Get method name (use original method name, not custom name)
                    $methodName = null;
                    foreach ($reflection->getMethods() as $method) {
                        $attrs = $method->getAttributes(Computed::class);
                        if ([] !== $attrs) {
                            /** @var Computed $attr */
                            $attr = $attrs[0]->newInstance();
                            $attrName = $attr->name ?? $method->getName();
                            if ($attrName === $name) {
                                $methodName = $method->getName();
                                break;
                            }
                        }
                    }

                    if ($methodName && $reflection->hasMethod($methodName)) {
                        $method = $reflection->getMethod($methodName);
                        if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                            $result[$name] = $method->invoke($dto);
                        }
                    }
                }
            }

            // Hook: beforeSerialization (allow modifying output data)
            $reflection = new ReflectionClass($dto);
            if ($reflection->hasMethod('beforeSerialization')) {
                $method = $reflection->getMethod('beforeSerialization');
                $method->invokeArgs($dto, [&$result]);
            }

            // Hook: afterSerialization (allow modifying and returning output data)
            if ($reflection->hasMethod('afterSerialization')) {
                $method = $reflection->getMethod('afterSerialization');
                $result = $method->invoke($dto, $result);
            }

            return $result;
        }

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

        $reflection = self::getReflection($class);

        // Get all public properties
        $data = get_object_vars($dto);
        $result = [];

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            // Check if hidden (from both array and JSON) - only if flag is set
            if ($flags['hasHidden'] && self::isHidden($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check if hidden from JSON specifically - only if flag is set
            if ($flags['hasHiddenFromJson'] && self::isHiddenFromJson($class, $name, $reflectionProperty)) {
                continue;
            }

            // Check if lazy - only if flag is set
            if ($flags['hasLazy']) {
                $lazyProperties = self::getLazyProperties($class);
                if (isset($lazyProperties[$name])) {
                    continue;
                }
            }

            $value = $data[$name];

            // Unwrap Optional values (only if flag is set)
            if ($flags['hasOptional'] && $value instanceof Optional) {
                // Skip empty Optional values (not present)
                if ($value->isEmpty()) {
                    continue;
                }
                // Unwrap present Optional values
                $value = $value->get();
            }

            // Check conditional properties (only if flag is set)
            if ($flags['hasConditionalProperties'] && !self::shouldIncludeConditionalProperty(
                $class,
                $name,
                $value,
                $dto,
                $context
            )) {
                continue;
            }

            // Get output name (check for #[MapTo] or #[MapOutputName] attribute) - only if flag is set
            $outputName = $name;
            if ($flags['hasMapTo'] || $flags['hasMapOutputName']) {
                $outputName = self::getToMapping($class, $name, $reflectionProperty);
            }

            // Convert value (handle nested DTOs and enums) - always needed for nested DTOs
            $result[$outputName] = self::convertValue($value, $class, $name, $reflectionProperty);
        }

        // Add computed properties - only if flag is set
        if ($flags['hasComputed']) {
            $computedMethods = self::getComputedMethods($class);
            foreach ($computedMethods as $name => $computed) {
                // Skip lazy computed properties
                if ($computed->lazy) {
                    continue;
                }

                // Get method name (use original method name, not custom name)
                $methodName = null;
                foreach ($reflection->getMethods() as $method) {
                    $attrs = $method->getAttributes(Computed::class);
                    if ([] !== $attrs) {
                        /** @var Computed $attr */
                        $attr = $attrs[0]->newInstance();
                        $attrName = $attr->name ?? $method->getName();
                        if ($attrName === $name) {
                            $methodName = $method->getName();
                            break;
                        }
                    }
                }

                if ($methodName && $reflection->hasMethod($methodName)) {
                    $method = $reflection->getMethod($methodName);
                    if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                        $result[$name] = $method->invoke($dto);
                    }
                }
            }
        }

        // Hook: beforeSerialization (allow modifying output data)
        $reflection = new ReflectionClass($dto);
        if ($reflection->hasMethod('beforeSerialization')) {
            $method = $reflection->getMethod('beforeSerialization');
            $method->invokeArgs($dto, [&$result]);
        }

        // Hook: afterSerialization (allow modifying and returning output data)
        if ($reflection->hasMethod('afterSerialization')) {
            $method = $reflection->getMethod('afterSerialization');
            $result = $method->invoke($dto, $result);
        }

        return $result;
    }

    /** Resolve parameter value from data.
     * @param array<string, mixed> $data
     * @param ReflectionClass<object> $reflection
     * @param object|null $dtoInstance Optional DTO instance for hooks
     */
    private static function resolveParameter(
        ReflectionParameter $param,
        array $data,
        ReflectionClass $reflection,
        ?object $dtoInstance = null
    ): mixed {
        $name = $param->getName();
        $class = $reflection->getName();

        // Get source key (check for #[MapFrom] attribute)
        $sourceKey = self::getFromMapping($class, $name, $param);

        // Check if value was provided (for Optional support)
        $wasProvided = array_key_exists($sourceKey, $data);

        // Get value from data
        $value = $data[$sourceKey] ?? null;

        // Check for #[ConvertEmptyToNull]
        if (self::shouldConvertEmptyToNull($class, $name, $param) && ('' === $value || [] === $value)) {
            $value = null;
        }

        // Hook: beforeCasting (allow modifying value before casting)
        if (null !== $dtoInstance) {
            $dtoReflection = new ReflectionClass($dtoInstance);
            if ($dtoReflection->hasMethod('beforeCasting')) {
                $method = $dtoReflection->getMethod('beforeCasting');
                $method->invokeArgs($dtoInstance, [$name, &$value]);
            }
        }

        // PERFORMANCE: Check for #[NoCasts] FIRST - skip ALL casting
        $noCasts = self::hasNoCasts($class);

        if (!$noCasts) {
            // Check for #[CastWith] - apply custom caster
            $casterClass = self::getCastWith($class, $name, $param);
            if (null !== $casterClass && null !== $value) {
                $value = $casterClass::cast($value);
            }

            // Handle nested DTOs and collections
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType) {
                $typeName = $type->getName();

                // Check if it's an array (potential collection)
                if ('array' === $typeName && is_array($value)) {
                    // Check for #[DataCollectionOf] attribute (highest priority)
                    $dataCollectionOfClass = self::getDataCollectionOf($reflection->getName(), $name, $param);
                    if ($dataCollectionOfClass && self::isCollection($value)) {
                        /** @var class-string<LiteDto> $dataCollectionOfClass */
                        $value = array_map($dataCollectionOfClass::from(...), $value);

                        // Hook: afterCasting
                        self::callHook($dtoInstance, 'afterCasting', [$name, $value]);

                        return $value;
                    }

                    // Fallback: Try to extract DTO type from docblock
                    $dtoClass = self::extractDtoClassFromDocBlock($param);
                    if ($dtoClass && self::isCollection($value)) {
                        $value = array_map($dtoClass::from(...), $value);

                        // Hook: afterCasting
                        self::callHook($dtoInstance, 'afterCasting', [$name, $value]);

                        return $value;
                    }
                }

                // Check if it's a LiteDto (nested DTO)
                if (!$type->isBuiltin() && is_subclass_of($typeName, LiteDto::class)) {
                    // Single nested DTO
                    /** @var class-string<LiteDto> $typeName */
                    if (is_array($value) || is_object($value) || is_string($value)) {
                        /** @var array<string, mixed>|object|string $value */
                        $value = $typeName::from($value);

                        // Hook: afterCasting
                        self::callHook($dtoInstance, 'afterCasting', [$name, $value]);

                        return $value;
                    }
                }

                // Check if it's an Enum
                if (!$type->isBuiltin() && enum_exists($typeName) && null !== $value) {
                    // Try to cast to enum
                    $value = self::castToEnum($typeName, $value);

                    // Hook: afterCasting
                    self::callHook($dtoInstance, 'afterCasting', [$name, $value]);

                    return $value;
                }

                // Check if it's DateTime or DateTimeImmutable (automatic casting)
                if (!$type->isBuiltin() && null !== $value && (DateTime::class === $typeName || DateTimeImmutable::class === $typeName)) {
                    $value = self::castToDateTime($typeName, $value);
                    // Hook: afterCasting
                    self::callHook($dtoInstance, 'afterCasting', [$name, $value]);
                    return $value;
                }

                // Check for #[AutoCast] - automatic casting for native PHP types
                if (null !== $value && $type->isBuiltin() && self::shouldAutoCast($class, $name)) {
                    $value = self::autoCastValue($value, $typeName);
                }
            }
        }

        // Hook: afterCasting (for non-cast values or AutoCast)
        self::callHook($dtoInstance, 'afterCasting', [$name, $value]);

        // Handle default values
        if (null === $value && $param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        // Check for #[Optional] attribute - wrap in Optional if present
        $optionalAttrs = $param->getAttributes(OptionalAttribute::class);
        if ([] !== $optionalAttrs) {
            return $wasProvided ? Optional::of($value) : Optional::empty();
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

        // Check for #[MapFrom] attribute on parameter (highest priority)
        $attrs = $param->getAttributes(MapFrom::class);
        if ([] !== $attrs) {
            /** @var MapFrom $from */
            $from = $attrs[0]->newInstance();
            self::$fromMappingCache[$class][$name] = $from->source;
            return $from->source;
        }

        // Check for class-level #[MapInputName] attribute
        $mapInputName = self::getMapInputName($class);
        if ($mapInputName instanceof MapInputName) {
            $transformedName = $mapInputName->convention->transform($name);
            self::$fromMappingCache[$class][$name] = $transformedName;
            return $transformedName;
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

        // Check for #[MapTo] attribute (highest priority)
        $attrs = $property->getAttributes(MapTo::class);
        if ([] !== $attrs) {
            /** @var MapTo $to */
            $to = $attrs[0]->newInstance();
            self::$toMappingCache[$class][$name] = $to->target;
            return $to->target;
        }

        // Check for class-level #[MapOutputName] attribute
        $mapOutputName = self::getMapOutputName($class);
        if ($mapOutputName instanceof MapOutputName) {
            $transformedName = $mapOutputName->convention->transform($name);
            self::$toMappingCache[$class][$name] = $transformedName;
            return $transformedName;
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
     * Get Visible attribute for property.
     *
     * @param class-string $class
     * @phpstan-ignore method.unused (Will be used in Phase 3 for Visible attribute support)
     */
    private static function getVisibleAttribute(string $class, string $name, ReflectionProperty $property): ?Visible
    {
        // Check cache
        if (isset(self::$visibleCache[$class][$name])) {
            return self::$visibleCache[$class][$name];
        }

        // Check for #[Visible] attribute
        $attrs = $property->getAttributes(Visible::class);
        if ([] !== $attrs) {
            /** @var Visible $visible */
            $visible = $attrs[0]->newInstance();
            self::$visibleCache[$class][$name] = $visible;
            return $visible;
        }

        self::$visibleCache[$class][$name] = null;
        return null;
    }

    /**
     * Get MapInputName attribute for class.
     *
     * @param class-string $class
     */
    private static function getMapInputName(string $class): ?MapInputName
    {
        // Check cache
        if (isset(self::$mapInputNameCache[$class])) {
            return self::$mapInputNameCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(MapInputName::class);

        if ([] !== $attrs) {
            /** @var MapInputName $mapInputName */
            $mapInputName = $attrs[0]->newInstance();
            self::$mapInputNameCache[$class] = $mapInputName;
            return $mapInputName;
        }

        self::$mapInputNameCache[$class] = null;
        return null;
    }

    /**
     * Get MapOutputName attribute for class.
     *
     * @param class-string $class
     */
    private static function getMapOutputName(string $class): ?MapOutputName
    {
        // Check cache
        if (isset(self::$mapOutputNameCache[$class])) {
            return self::$mapOutputNameCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(MapOutputName::class);

        if ([] !== $attrs) {
            /** @var MapOutputName $mapOutputName */
            $mapOutputName = $attrs[0]->newInstance();
            self::$mapOutputNameCache[$class] = $mapOutputName;
            return $mapOutputName;
        }

        self::$mapOutputNameCache[$class] = null;
        return null;
    }

    /**
     * Get DataCollectionOf class for parameter.
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
            self::$dataCollectionOfCache[$class][$name] = $dataCollectionOf->dtoClass;
            return $dataCollectionOf->dtoClass;
        }

        self::$dataCollectionOfCache[$class][$name] = null;
        return null;
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

        $reflection = self::getReflection($class);
        $computed = [];

        foreach ($reflection->getMethods() as $reflectionMethod) {
            $attrs = $reflectionMethod->getAttributes(Computed::class);
            if ([] !== $attrs) {
                /** @var Computed $computedAttr */
                $computedAttr = $attrs[0]->newInstance();
                $name = $computedAttr->name ?? $reflectionMethod->getName();
                $computed[$name] = $computedAttr;
            }
        }

        self::$computedCache[$class] = $computed;
        return $computed;
    }

    /**
     * Check if class has Computed methods (feature flag).
     *
     * @param class-string $class
     * @phpstan-ignore method.unused
     */
    private static function hasComputed(string $class): bool
    {
        // Check cache
        if (isset(self::$hasComputedCache[$class])) {
            return self::$hasComputedCache[$class];
        }

        $computed = self::getComputedMethods($class);
        $hasComputed = [] !== $computed;

        self::$hasComputedCache[$class] = $hasComputed;
        return $hasComputed;
    }

    /**
     * Get all Lazy properties for a class.
     *
     * @param class-string $class
     * @return array<string, true>
     */
    private static function getLazyProperties(string $class): array
    {
        // Check cache
        if (isset(self::$lazyCache[$class])) {
            return self::$lazyCache[$class];
        }

        $reflection = self::getReflection($class);
        $lazy = [];

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $attrs = $reflectionProperty->getAttributes(Lazy::class);
            if ([] !== $attrs) {
                $lazy[$reflectionProperty->getName()] = true;
            }
        }

        // Also check constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor instanceof ReflectionMethod) {
            foreach ($constructor->getParameters() as $reflectionParameter) {
                $attrs = $reflectionParameter->getAttributes(Lazy::class);
                if ([] !== $attrs) {
                    $lazy[$reflectionParameter->getName()] = true;
                }
            }
        }

        self::$lazyCache[$class] = $lazy;
        return $lazy;
    }

    /**
     * Check if class has Lazy properties (feature flag).
     *
     * @param class-string $class
     * @phpstan-ignore method.unused
     */
    private static function hasLazy(string $class): bool
    {
        // Check cache
        if (isset(self::$hasLazyCache[$class])) {
            return self::$hasLazyCache[$class];
        }

        $lazy = self::getLazyProperties($class);
        $hasLazy = [] !== $lazy;

        self::$hasLazyCache[$class] = $hasLazy;
        return $hasLazy;
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
                $converter = new YamlConverter();
                return $converter->toArray($data);
            } catch (Throwable) {
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
     * Cast value to DateTime or DateTimeImmutable.
     *
     * @param class-string<DateTime|DateTimeImmutable> $dateTimeClass
     */
    private static function castToDateTime(string $dateTimeClass, mixed $value): DateTime|DateTimeImmutable
    {
        // If already the correct DateTime instance, return it
        if ($value instanceof $dateTimeClass) {
            return $value;
        }

        // Convert between DateTime and DateTimeImmutable
        if (DateTime::class === $dateTimeClass) {
            if ($value instanceof DateTimeImmutable) {
                return DateTime::createFromImmutable($value);
            }
            if ($value instanceof DateTimeInterface) {
                return DateTime::createFromInterface($value);
            }
        }

        if (DateTimeImmutable::class === $dateTimeClass) {
            if ($value instanceof DateTime) {
                return DateTimeImmutable::createFromMutable($value);
            }
            if ($value instanceof DateTimeInterface) {
                return DateTimeImmutable::createFromInterface($value);
            }
        }

        // Handle null or empty string
        if (null === $value || '' === $value) {
            throw new InvalidArgumentException('Cannot cast null or empty string to ' . $dateTimeClass);
        }

        // Cast from string or int (timestamp)
        if (is_string($value)) {
            try {
                return DateTime::class === $dateTimeClass
                    ? new DateTime($value)
                    : new DateTimeImmutable($value);
            } catch (Throwable $e) {
                throw new InvalidArgumentException(
                    'Cannot cast value to ' . $dateTimeClass . ': ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        if (is_int($value)) {
            try {
                $dateTime = DateTime::class === $dateTimeClass
                    ? new DateTime()
                    : new DateTimeImmutable();
                return $dateTime->setTimestamp($value);
            } catch (Throwable $e) {
                throw new InvalidArgumentException(
                    'Cannot cast timestamp to ' . $dateTimeClass . ': ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        throw new InvalidArgumentException('Cannot cast value to ' . $dateTimeClass);
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
     * @phpstan-ignore method.unused
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
     * Get feature flags for Normal mode.
     * Scans all properties/parameters once and caches which features are used.
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
     *     hasEnumSerialize: bool,
     *     hasDataCollectionOf: bool,
     *     hasMapInputName: bool,
     *     hasMapOutputName: bool,
     *     hasComputed: bool,
     *     hasLazy: bool,
     *     hasValidation: bool,
     *     hasAnyArrayAttribute: bool,
     *     hasAnyJsonAttribute: bool,
     * }
     */
    private static function getFeatureFlags(string $class): array
    {
        // Check cache
        if (isset(self::$featureFlags[$class])) {
            return self::$featureFlags[$class];
        }

        $reflection = self::getReflection($class);

        // PERFORMANCE: Check for #[NoAttributes] FIRST - skip ALL attribute processing
        if ([] !== $reflection->getAttributes(NoAttributes::class)) {
            // Return minimal flags - no attributes at all
            $flags = [
                'hasMapFrom' => false,
                'hasMapTo' => false,
                'hasHidden' => false,
                'hasHiddenFromArray' => false,
                'hasHiddenFromJson' => false,
                'hasVisible' => false,
                'hasCastWith' => false,
                'hasConvertEmptyToNull' => false,
                'hasEnumSerialize' => false,
                'hasDataCollectionOf' => false,
                'hasMapInputName' => false,
                'hasMapOutputName' => false,
                'hasComputed' => false,
                'hasLazy' => false,
                'hasOptional' => false,
                'hasValidation' => false,
                'hasConditionalValidation' => false,
                'hasConditionalProperties' => false,
                'hasRuleGroup' => false,
                'hasWithMessage' => false,
                'hasAnyArrayAttribute' => false,
                'hasAnyJsonAttribute' => false,
                'hasAutoCast' => false,
                'hasNoCasts' => false,
            ];

            // Cache and return immediately
            self::$featureFlags[$class] = $flags;
            return $flags;
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
            'hasEnumSerialize' => false,
            'hasDataCollectionOf' => false,
            'hasMapInputName' => false,
            'hasMapOutputName' => false,
            'hasComputed' => false,
            'hasLazy' => false,
            'hasOptional' => false,
            'hasValidation' => false,
            'hasConditionalValidation' => false,
            'hasConditionalProperties' => false,
            'hasRuleGroup' => false,
            'hasWithMessage' => false,
            'hasAnyArrayAttribute' => false,
            'hasAnyJsonAttribute' => false,
            'hasAutoCast' => false,
            'hasNoCasts' => false,
        ];

        // Check for class-level attributes
        if ([] !== $reflection->getAttributes(MapInputName::class)) {
            $flags['hasMapInputName'] = true;
        }
        if ([] !== $reflection->getAttributes(MapOutputName::class)) {
            $flags['hasMapOutputName'] = true;
        }

        // Check for #[NoCasts] - disables ALL casting
        if ([] !== $reflection->getAttributes(NoCasts::class)) {
            $flags['hasNoCasts'] = true;
            self::$noCastsCache[$class] = true;
        } else {
            self::$noCastsCache[$class] = false;
        }

        // Check for class-level #[AutoCast]
        if ([] !== $reflection->getAttributes(AutoCast::class)) {
            $flags['hasAutoCast'] = true;
            self::$classAutoCastCache[$class] = true;
        } else {
            self::$classAutoCastCache[$class] = false;
        }

        // Scan constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $reflectionParameter) {
                $paramName = $reflectionParameter->getName();

                if ([] !== $reflectionParameter->getAttributes(MapFrom::class)) {
                    $flags['hasMapFrom'] = true;
                }
                if ([] !== $reflectionParameter->getAttributes(CastWith::class)) {
                    $flags['hasCastWith'] = true;
                }
                if ([] !== $reflectionParameter->getAttributes(ConvertEmptyToNull::class)) {
                    $flags['hasConvertEmptyToNull'] = true;
                }

                // Optional - fill cache while scanning constructor parameters
                $optionalAttrs = $reflectionParameter->getAttributes(OptionalAttribute::class);
                if ([] !== $optionalAttrs) {
                    $flags['hasOptional'] = true;
                    if (!isset(self::$optionalCache[$class])) {
                        self::$optionalCache[$class] = [];
                    }
                    self::$optionalCache[$class][$paramName] = true;
                }

                // AutoCast - fill cache while scanning constructor parameters
                if ([] !== $reflectionParameter->getAttributes(AutoCast::class)) {
                    $flags['hasAutoCast'] = true;
                    if (!isset(self::$autoCastCache[$class])) {
                        self::$autoCastCache[$class] = [];
                    }
                    self::$autoCastCache[$class][$paramName] = true;
                }
            }
        }

        // Scan properties and fill caches while scanning
        foreach ($reflection->getProperties() as $reflectionProperty) {
            $propName = $reflectionProperty->getName();

            if ([] !== $reflectionProperty->getAttributes(MapTo::class)) {
                $flags['hasMapTo'] = true;
            }

            // Hidden - fill cache while scanning
            $hiddenAttrs = $reflectionProperty->getAttributes(Hidden::class);
            if ([] !== $hiddenAttrs) {
                $flags['hasHidden'] = true;
                if (!isset(self::$hiddenCache[$class])) {
                    self::$hiddenCache[$class] = [];
                }
                self::$hiddenCache[$class][$propName] = true;
            }

            // HiddenFromArray - fill cache while scanning
            $hiddenFromArrayAttrs = $reflectionProperty->getAttributes(HiddenFromArray::class);
            if ([] !== $hiddenFromArrayAttrs) {
                $flags['hasHiddenFromArray'] = true;
                if (!isset(self::$hiddenFromArrayCache[$class])) {
                    self::$hiddenFromArrayCache[$class] = [];
                }
                self::$hiddenFromArrayCache[$class][$propName] = true;
            }

            // HiddenFromJson - fill cache while scanning
            $hiddenFromJsonAttrs = $reflectionProperty->getAttributes(HiddenFromJson::class);
            if ([] !== $hiddenFromJsonAttrs) {
                $flags['hasHiddenFromJson'] = true;
                if (!isset(self::$hiddenFromJsonCache[$class])) {
                    self::$hiddenFromJsonCache[$class] = [];
                }
                self::$hiddenFromJsonCache[$class][$propName] = true;
            }

            if ([] !== $reflectionProperty->getAttributes(Visible::class)) {
                $flags['hasVisible'] = true;
            }
            if ([] !== $reflectionProperty->getAttributes(EnumSerialize::class)) {
                $flags['hasEnumSerialize'] = true;
            }
            if ([] !== $reflectionProperty->getAttributes(DataCollectionOf::class)) {
                $flags['hasDataCollectionOf'] = true;
            }

            // Lazy - fill cache while scanning
            $lazyAttrs = $reflectionProperty->getAttributes(Lazy::class);
            if ([] !== $lazyAttrs) {
                $flags['hasLazy'] = true;
                if (!isset(self::$lazyCache[$class])) {
                    self::$lazyCache[$class] = [];
                }
                self::$lazyCache[$class][$propName] = true;
            }

            // Optional - fill cache while scanning
            $optionalAttrs = $reflectionProperty->getAttributes(OptionalAttribute::class);
            if ([] !== $optionalAttrs) {
                $flags['hasOptional'] = true;
                if (!isset(self::$optionalCache[$class])) {
                    self::$optionalCache[$class] = [];
                }
                self::$optionalCache[$class][$propName] = true;
            }

            // Validation Attributes - scan and cache all validation rules
            $validationAttrs = $reflectionProperty->getAttributes(
                ValidationAttribute::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            if ([] !== $validationAttrs) {
                $flags['hasValidation'] = true;
                if (!isset(self::$validationRulesCache[$class])) {
                    self::$validationRulesCache[$class] = [];
                }
                if (!isset(self::$validationRulesCache[$class][$propName])) {
                    self::$validationRulesCache[$class][$propName] = [];
                }
                foreach ($validationAttrs as $attr) {
                    $instance = $attr->newInstance();
                    self::$validationRulesCache[$class][$propName][] = $instance;

                    // Check if this is a conditional validation attribute
                    if ($instance instanceof ConditionalValidationAttribute) {
                        $flags['hasConditionalValidation'] = true;
                    }
                }
            }

            // Check for Nullable meta-attribute
            $nullableAttrs = $reflectionProperty->getAttributes(
                Nullable::class
            );
            if ([] !== $nullableAttrs) {
                $flags['hasValidation'] = true;
                if (!isset(self::$validationNullableCache[$class])) {
                    self::$validationNullableCache[$class] = [];
                }
                self::$validationNullableCache[$class][$propName] = true;
            }

            // Check for Sometimes meta-attribute
            $sometimesAttrs = $reflectionProperty->getAttributes(
                Sometimes::class
            );
            if ([] !== $sometimesAttrs) {
                $flags['hasValidation'] = true;
                if (!isset(self::$validationSometimesCache[$class])) {
                    self::$validationSometimesCache[$class] = [];
                }
                self::$validationSometimesCache[$class][$propName] = true;
            }

            // Conditional Properties - scan and cache all conditional attributes
            $conditionalAttrs = $reflectionProperty->getAttributes(
                ConditionalProperty::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            if ([] !== $conditionalAttrs) {
                $flags['hasConditionalProperties'] = true;
                if (!isset(self::$conditionalPropertiesCache[$class])) {
                    self::$conditionalPropertiesCache[$class] = [];
                }
                if (!isset(self::$conditionalPropertiesCache[$class][$propName])) {
                    self::$conditionalPropertiesCache[$class][$propName] = [];
                }
                foreach ($conditionalAttrs as $attr) {
                    self::$conditionalPropertiesCache[$class][$propName][] = $attr->newInstance();
                }
            }

            // RuleGroup - scan and cache
            $ruleGroupAttrs = $reflectionProperty->getAttributes(RuleGroup::class);
            if ([] !== $ruleGroupAttrs) {
                $flags['hasRuleGroup'] = true;
                if (!isset(self::$ruleGroupCache[$class])) {
                    self::$ruleGroupCache[$class] = [];
                }
                self::$ruleGroupCache[$class][$propName] = $ruleGroupAttrs[0]->newInstance();
            }

            // WithMessage - scan and cache
            $withMessageAttrs = $reflectionProperty->getAttributes(WithMessage::class);
            if ([] !== $withMessageAttrs) {
                $flags['hasWithMessage'] = true;
                if (!isset(self::$withMessageCache[$class])) {
                    self::$withMessageCache[$class] = [];
                }
                self::$withMessageCache[$class][$propName] = $withMessageAttrs[0]->newInstance();
            }
        }

        // Scan methods for Computed and fill cache while scanning
        if (!isset(self::$computedCache[$class])) {
            self::$computedCache[$class] = [];
        }

        foreach ($reflection->getMethods() as $reflectionMethod) {
            $computedAttrs = $reflectionMethod->getAttributes(Computed::class);
            if ([] !== $computedAttrs) {
                $flags['hasComputed'] = true;

                // Fill computed cache while scanning
                /** @var Computed $computed */
                $computed = $computedAttrs[0]->newInstance();
                $name = $computed->name ?? $reflectionMethod->getName();
                self::$computedCache[$class][$name] = $computed;
            }
        }

        // Set combined flags for fast-path checks
        $flags['hasAnyArrayAttribute'] = $flags['hasMapTo'] || $flags['hasEnumSerialize'] ||
            $flags['hasHidden'] || $flags['hasHiddenFromArray'] ||
            $flags['hasLazy'] || $flags['hasComputed'] || $flags['hasOptional'] ||
            $flags['hasConditionalProperties'];

        $flags['hasAnyJsonAttribute'] = $flags['hasMapTo'] || $flags['hasEnumSerialize'] ||
            $flags['hasHidden'] || $flags['hasHiddenFromJson'] ||
            $flags['hasLazy'] || $flags['hasComputed'] || $flags['hasOptional'] ||
            $flags['hasConditionalProperties'];

        // Cache and return
        self::$featureFlags[$class] = $flags;
        return $flags;
    }

    /**
     * Create DTO in UltraFast mode (auto-detects attributes with feature flags).
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
                    sprintf(
                        'UltraFast mode only accepts arrays. Use #[ConverterMode] attribute on %s to enable JSON/XML/CSV support.',
                        $class
                    )
                );
            }
        }

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

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
            $wasProvided = false;
            $sourceKey = $paramName;

            // Step 1: Check for #[MapFrom] (only if flag is set)
            if ($flags['hasMapFrom']) {
                $mapFromAttrs = $reflectionParameter->getAttributes(MapFrom::class);
                if (!empty($mapFromAttrs)) {
                    /** @var MapFrom $mapFrom */
                    $mapFrom = $mapFromAttrs[0]->newInstance();
                    $sourceKey = $mapFrom->source;
                    $wasProvided = array_key_exists($sourceKey, $data);
                    $value = $data[$sourceKey] ?? null;
                } else {
                    $wasProvided = array_key_exists($paramName, $data);
                    $value = $data[$paramName] ?? null;
                }
            } else {
                $wasProvided = array_key_exists($paramName, $data);
                $value = $data[$paramName] ?? null;
            }

            // Step 2: Check for #[ConvertEmptyToNull] (only if flag is set)
            if ($flags['hasConvertEmptyToNull'] && ('' === $value || [] === $value) && self::shouldConvertEmptyToNull(
                $class,
                $paramName,
                $reflectionParameter
            )) {
                $value = null;
            }

            // Step 3: Apply casting (only if NOT #[NoCasts])
            if (!$flags['hasNoCasts']) {
                // Step 3a: Apply #[CastWith] if value is not null (only if flag is set)
                if ($flags['hasCastWith'] && null !== $value) {
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

                // Step 3b: Cast to enum if needed
                if (null !== $value) {
                    $type = $reflectionParameter->getType();
                    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (enum_exists($typeName)) {
                            $value = self::castToEnum($typeName, $value);
                        }
                    }
                }

                // Step 3c: Cast to DateTime/DateTimeImmutable if needed (automatic casting)
                if (null !== $value) {
                    $type = $reflectionParameter->getType();
                    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (DateTime::class === $typeName || DateTimeImmutable::class === $typeName) {
                            $value = self::castToDateTime($typeName, $value);
                        }
                    }
                }

                // Step 3d: Apply #[AutoCast] for native PHP types (only if flag is set)
                if ($flags['hasAutoCast'] && null !== $value) {
                    $type = $reflectionParameter->getType();
                    if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                        $typeName = $type->getName();
                        if (self::shouldAutoCast($class, $paramName)) {
                            $value = self::autoCastValue($value, $typeName);
                        }
                    }
                }
            }

            // Step 4: Wrap in Optional if needed (only if flag is set)
            if ($flags['hasOptional'] && isset(self::$optionalCache[$class][$paramName])) {
                // Wrap in Optional based on whether value was provided
                $value = $wasProvided ? Optional::of($value) : Optional::empty();
            }

            $args[] = $value;
        }

        // Create instance
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Validate data before creating DTO.
     *
     * @param class-string<LiteDto> $class
     * @param array<string, mixed>|string|object $data
     * @param array<string> $groups Validation groups to apply (empty = all rules)
     */
    public static function validate(
        string $class,
        mixed $data,
        array $groups = []
    ): ValidationResult
    {
        // PERFORMANCE: Check for #[NoValidation] FIRST - skip ALL validation
        if (!isset(self::$noValidationCache[$class])) {
            $reflection = self::getReflection($class);
            self::$noValidationCache[$class] = [] !== $reflection->getAttributes(NoValidation::class);
        }

        if (self::$noValidationCache[$class]) {
            // Convert data to array if needed (for consistency)
            if (!is_array($data)) {
                $converterMode = self::hasConverterMode($class);
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
            return ValidationResult::success($data);
        }

        // Convert data to array first
        if (!is_array($data)) {
            $converterMode = self::hasConverterMode($class);
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

        $arrayData = $data;

        // Hook: beforeValidation (allow modifying data before validation)
        $tempReflection = new ReflectionClass($class);
        $tempInstance = $tempReflection->newInstanceWithoutConstructor();
        if ($tempReflection->hasMethod('beforeValidation')) {
            $method = $tempReflection->getMethod('beforeValidation');
            $method->invokeArgs($tempInstance, [&$arrayData]);
        }

        // Get feature flags
        $flags = self::getFeatureFlags($class);

        // Fast-Path: No validation attributes
        if (!$flags['hasValidation']) {
            return ValidationResult::success($arrayData);
        }

        // Validate using cached rules
        $errors = [];
        $reflection = self::getReflection($class);

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $propName = $reflectionProperty->getName();

            // Skip if no validation rules for this property
            if (!isset(self::$validationRulesCache[$class][$propName])) {
                continue;
            }

            // Check RuleGroup filtering (only if flag is set and groups are specified)
            if ($flags['hasRuleGroup'] && [] !== $groups && isset(self::$ruleGroupCache[$class][$propName])) {
                $ruleGroup = self::$ruleGroupCache[$class][$propName];
                if (!$ruleGroup->belongsToAnyGroup($groups)) {
                    // Skip this property if it doesn't belong to any of the specified groups
                    continue;
                }
            }

            // Check if field is present in input
            $isPresent = array_key_exists($propName, $arrayData);

            // Check for Sometimes meta-attribute
            $hasSometimes = isset(self::$validationSometimesCache[$class][$propName]);
            if ($hasSometimes && !$isPresent) {
                // Skip validation if Sometimes is present and field is not in input
                continue;
            }

            // Get value from data
            $value = $arrayData[$propName] ?? null;

            // Check for Nullable meta-attribute
            $hasNullable = isset(self::$validationNullableCache[$class][$propName]);
            if ($hasNullable && null === $value) {
                // Skip validation if Nullable is present and value is null
                continue;
            }

            // Get custom messages for this property (only if flag is set)
            $customMessages = [];
            if ($flags['hasWithMessage'] && isset(self::$withMessageCache[$class][$propName])) {
                $customMessages = self::$withMessageCache[$class][$propName]->getMessages();
            }

            // Run all validation rules for this property
            foreach (self::$validationRulesCache[$class][$propName] as $rule) {
                $isValid = false;

                // Check if this is a conditional validation attribute (only if flag is set)
                if ($flags['hasConditionalValidation'] && $rule instanceof ConditionalValidationAttribute) {
                    $isValid = $rule->validateConditional($value, $propName, $arrayData);
                } else {
                    $isValid = $rule->validate($value, $propName);
                }

                if (!$isValid) {
                    if (!isset($errors[$propName])) {
                        $errors[$propName] = [];
                    }

                    // Use custom message if available
                    $errorMessage = $rule->getErrorMessage($propName);
                    if ([] !== $customMessages) {
                        // Extract rule name from rule class (lowercase class name without namespace)
                        $ruleName = strtolower((new ReflectionClass($rule))->getShortName());
                        if (isset($customMessages[$ruleName])) {
                            $errorMessage = $customMessages[$ruleName];
                        }
                    }

                    $errors[$propName][] = $errorMessage;
                }
            }
        }

        // Return result
        $result = [] === $errors
            ? ValidationResult::success($arrayData)
            : ValidationResult::failure($errors);

        // Hook: afterValidation (allow inspecting validation result)
        if ($tempReflection->hasMethod('afterValidation')) {
            $method = $tempReflection->getMethod('afterValidation');
            $method->invoke($tempInstance, $result);
        }

        return $result;
    }

    /** Validate an existing DTO instance. */
    public static function validateInstance(LiteDto $dto): ValidationResult
    {
        $class = $dto::class;

        // PERFORMANCE: Check for #[NoValidation] FIRST - skip ALL validation
        if (!isset(self::$noValidationCache[$class])) {
            $reflection = self::getReflection($class);
            self::$noValidationCache[$class] = [] !== $reflection->getAttributes(NoValidation::class);
        }

        if (self::$noValidationCache[$class]) {
            return ValidationResult::success([]);
        }

        // Get feature flags
        $flags = self::getFeatureFlags($class);

        // Fast-Path: No validation attributes
        if (!$flags['hasValidation']) {
            return ValidationResult::success([]);
        }

        // Validate using cached rules
        $errors = [];
        $reflection = self::getReflection($class);

        // Extract all data from DTO for conditional validation
        $allData = [];
        foreach ($reflection->getProperties() as $reflectionProperty) {
            $allData[$reflectionProperty->getName()] = $reflectionProperty->getValue($dto);
        }

        foreach ($reflection->getProperties() as $property) {
            $propName = $property->getName();

            // Skip if no validation rules for this property
            if (!isset(self::$validationRulesCache[$class][$propName])) {
                continue;
            }

            // Get value from DTO
            $value = $property->getValue($dto);

            // Check for Nullable meta-attribute
            $hasNullable = isset(self::$validationNullableCache[$class][$propName]);
            if ($hasNullable && null === $value) {
                // Skip validation if Nullable is present and value is null
                continue;
            }

            // Note: Sometimes is not checked in validateInstance() because the DTO already exists
            // and all properties are present. Sometimes only applies during initial validation.

            // Run all validation rules for this property
            foreach (self::$validationRulesCache[$class][$propName] as $rule) {
                $isValid = false;

                // Check if this is a conditional validation attribute (only if flag is set)
                if ($flags['hasConditionalValidation'] && $rule instanceof ConditionalValidationAttribute) {
                    $isValid = $rule->validateConditional($value, $propName, $allData);
                } else {
                    $isValid = $rule->validate($value, $propName);
                }

                if (!$isValid) {
                    if (!isset($errors[$propName])) {
                        $errors[$propName] = [];
                    }
                    $errors[$propName][] = $rule->getErrorMessage($propName);
                }
            }
        }

        // Return result
        if ([] === $errors) {
            return ValidationResult::success([]);
        }

        return ValidationResult::failure($errors);
    }

    /**
     * Get ValidateRequest attribute for a class (cached).
     *
     * @param class-string $class
     */
    public static function getValidateRequest(string $class): ?ValidateRequest
    {
        // Check cache
        if (isset(self::$validateRequestCache[$class])) {
            return self::$validateRequestCache[$class];
        }

        // Get reflection and check for attribute
        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(ValidateRequest::class);

        if ([] === $attrs) {
            self::$validateRequestCache[$class] = null;
            return null;
        }

        /** @var ValidateRequest $validateRequest */
        $validateRequest = $attrs[0]->newInstance();
        self::$validateRequestCache[$class] = $validateRequest;

        return $validateRequest;
    }

    /**
     * Check if a property should be included based on conditional attributes.
     *
     * @param class-string $class
     * @param array<string, mixed> $context
     */
    private static function shouldIncludeConditionalProperty(
        string $class,
        string $propertyName,
        mixed $value,
        object $dto,
        array $context
    ): bool {
        // Get cached conditional properties
        $conditionals = self::$conditionalPropertiesCache[$class] ?? [];

        // No conditional attributes for this property
        if (!isset($conditionals[$propertyName])) {
            return true;
        }

        // All conditional attributes must pass (AND logic)
        foreach ($conditionals[$propertyName] as $conditional) {
            if (!$conditional->shouldInclude($value, $dto, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if class has #[NoCasts] attribute (cached).
     *
     * @param class-string $class
     */
    private static function hasNoCasts(string $class): bool
    {
        // Check cache
        if (isset(self::$noCastsCache[$class])) {
            return self::$noCastsCache[$class];
        }

        // Get reflection and check for attribute
        $reflection = self::getReflection($class);
        $hasNoCasts = [] !== $reflection->getAttributes(NoCasts::class);

        self::$noCastsCache[$class] = $hasNoCasts;
        return $hasNoCasts;
    }

    /**
     * Check if property should be auto-casted (cached).
     *
     * @param class-string $class
     */
    private static function shouldAutoCast(string $class, string $propertyName): bool
    {
        // Not cached yet - should not happen if getFeatureFlags() was called
        return self::$autoCastCache[$class][$propertyName] ?? self::$classAutoCastCache[$class] ?? false;
    }

    /** Auto-cast value to native PHP type. */
    private static function autoCastValue(mixed $value, string $typeName): mixed
    {
        // Skip if already correct type
        if (match ($typeName) {
            'int' => is_int($value),
            'float' => is_float($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            default => false,
        }) {
            return $value;
        }

        // Cast to target type
        return match ($typeName) {
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            'bool' => (bool)$value,
            'array' => (array)$value,
            default => $value,
        };
    }

    /**
     * Call a protected hook method on a DTO instance using reflection.
     *
     * @param object|null $instance DTO instance
     * @param string $methodName Hook method name
     * @param array<mixed> $args Method arguments
     */
    private static function callHook(?object $instance, string $methodName, array $args = []): void
    {
        if (null === $instance) {
            return;
        }

        $reflection = new ReflectionClass($instance);
        if ($reflection->hasMethod($methodName)) {
            $method = $reflection->getMethod($methodName);
            $method->invoke($instance, ...$args);
        }
    }
}
