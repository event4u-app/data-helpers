<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Error;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
use event4u\DataHelpers\SimpleDto\Attributes\CastWith;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\Attributes\EnumSerialize;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;
use event4u\DataHelpers\SimpleDto\Attributes\NotImmutable;
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;
use event4u\DataHelpers\SimpleDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\SimpleDto\Attributes\RuleGroup;
use event4u\DataHelpers\SimpleDto\Attributes\ValidateRequest;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Nullable;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Sometimes;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;
use event4u\DataHelpers\SimpleDto\Attributes\WithMessage;
use event4u\DataHelpers\SimpleDto\Casts\ArrayCast;
use event4u\DataHelpers\SimpleDto\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDto\Casts\CollectionCast;
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
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalValidationAttribute;
use event4u\DataHelpers\SimpleDto\Contracts\ValidationAttribute;
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\Support\Optional;
use event4u\DataHelpers\Support\StringFormatDetector;
use event4u\DataHelpers\Validation\ValidationResult;
use InvalidArgumentException;
use JsonException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use UnitEnum;

/**
 * High-performance engine for SimpleDto.
 *
 * Optimized for maximum speed (~0.3μs per operation) with minimal overhead.
 * Uses aggressive caching and direct property access.
 */
final class SimpleEngine
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
     * Cache for casts() method results per class.
     *
     * @var array<class-string, array<string, mixed>>
     */
    private static array $castsCache = [];

    /**
     * Cache for Computed methods per class.
     *
     * @var array<class-string, array<string, Computed>>
     */
    private static array $computedCache = [];

    /**
     * Cache for computed property values per instance.
     *
     * @var array<class-string, array<int, array<string, mixed>>>
     */
    private static array $computedValuesCache = [];

    /**
     * Cache for included lazy computed properties per instance.
     *
     * @var array<int, array<int, string>>
     */
    private static array $includedComputedCache = [];

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
     *     hasNotImmutable: bool,
     *     classNotImmutable: bool,
     *     notImmutableProperties: array<string, bool>,
     * }>
     */
    private static array $featureFlags = [];

    /**
     * Create DTO from data.
     *
     * @param class-string $class
     * @param array<string, mixed>|string|object $data
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function createFromData(
        string $class,
        mixed $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): object {
        return self::createFromDataInternal($class, $data, $template, $filters, $pipeline);
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

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

            // Fast path: No attributes that affect toArray()
            if (!$flags['hasAnyArrayAttribute']) {
                return get_object_vars($dto);
            }

            // Slow path: Process attributes
            $reflection = self::getReflection($class);
            $data = get_object_vars($dto);

            // Get included computed properties for lazy handling
            $objectId = spl_object_id($dto);
            $includedComputed = self::$includedComputedCache[$objectId] ?? [];

            // Clear the included computed cache after reading it (one-time use)
            if (isset(self::$includedComputedCache[$objectId])) {
                unset(self::$includedComputedCache[$objectId]);
            }

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
                    $lazyProperties = self::$lazyCache[$class] ?? [];
                    // Only skip lazy properties that are not explicitly included
                    foreach ($lazyProperties as $lazyName => $lazyValue) {
                        if (!in_array($lazyName, $includedComputed, true)) {
                            $toSkip[$lazyName] = $lazyValue;
                        }
                    }
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

                // Handle Lazy values (only if flag is set)
                if ($flags['hasLazy'] && $value instanceof \event4u\DataHelpers\Support\Lazy) {
                    // Unwrap lazy value (already checked in toSkip)
                    $value = $value->get();
                }

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

                // Get output name (check for #[MapTo] attribute first, then #[MapOutputName])
                $outputName = $name;
                $outputPath = null; // For dot notation support
                $hasMapToOnProperty = false;

                if ($flags['hasMapTo']) {
                    $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                    if (!empty($mapToAttrs)) {
                        $hasMapToOnProperty = true;
                        /** @var MapTo $mapTo */
                        $mapTo = $mapToAttrs[0]->newInstance();
                        $outputName = $mapTo->target;

                        // Check if it's dot notation (e.g., 'user.email')
                        if (str_contains($outputName, '.')) {
                            $outputPath = explode('.', $outputName);
                            $outputName = null; // Will be handled later
                        }
                    }
                }

                // If no MapTo on this property, check for class-level MapOutputName
                if (!$hasMapToOnProperty && null === $outputPath && $flags['hasMapOutputName']) {
                    $mapOutputName = self::getMapOutputName($class);
                    if ($mapOutputName instanceof MapOutputName) {
                        $outputName = $mapOutputName->convention->transform($name);
                    }
                }

                // Handle enums with #[EnumSerialize] (only if flag is set)
                if ($flags['hasEnumSerialize'] && ($value instanceof BackedEnum || $value instanceof UnitEnum)) {
                    $mode = self::getEnumSerializeMode($class, $name, $reflectionProperty);
                    $value = self::serializeEnum($value, $mode);
                }

                // Apply output casts FIRST (only if NOT #[NoCasts])
                $convertedValue = $value;
                if (!$flags['hasNoCasts']) {
                    $convertedValue = self::applyOutputCast($class, $name, $value, $data);
                }

                // Then convert value only for complex types (nested DTOs, arrays)
                // But skip if already converted by cast
                if ($convertedValue === $value && (is_object($value) || is_array($value))) {
                    $convertedValue = self::convertValue($value, $class, $name, $reflectionProperty);
                }

                // Handle dot notation output path (e.g., 'user.email' -> ['user' => ['email' => value]])
                if (null !== $outputPath) {
                    // Build nested array structure
                    $current = &$result;
                    $pathLength = count($outputPath);

                    for ($i = 0; $pathLength - 1 > $i; $i++) {
                        $key = $outputPath[$i];
                        if (!isset($current[$key]) || !is_array($current[$key])) {
                            $current[$key] = [];
                        }
                        $current = &$current[$key];
                    }

                    // Set the final value
                    $current[$outputPath[$pathLength - 1]] = $convertedValue;
                } else {
                    // Simple key-value assignment
                    $result[$outputName] = $convertedValue;
                }
            }

            // Add computed properties - only if flag is set
            if ($flags['hasComputed']) {
                $computedMethods = self::getComputedMethods($class);
                foreach ($computedMethods as $name => $computed) {
                    // Skip lazy computed properties unless explicitly included
                    if ($computed->lazy && !in_array($name, $includedComputed, true)) {
                        continue;
                    }

                    // Get method name (use original method name, not custom name)
                    $methodName = null;
                    $methodReflection = null;
                    foreach ($reflection->getMethods() as $method) {
                        $attrs = $method->getAttributes(Computed::class);
                        if ([] !== $attrs) {
                            /** @var Computed $attr */
                            $attr = $attrs[0]->newInstance();
                            $attrName = $attr->name ?? $method->getName();
                            if ($attrName === $name) {
                                $methodName = $method->getName();
                                $methodReflection = $method;
                                break;
                            }
                        }
                    }

                    // Check if computed property is hidden (Hidden, HiddenFromArray)
                    if ($methodReflection) {
                        $hiddenAttrs = $methodReflection->getAttributes(Hidden::class);
                        $hiddenFromArrayAttrs = $methodReflection->getAttributes(HiddenFromArray::class);
                        if ([] !== $hiddenAttrs || [] !== $hiddenFromArrayAttrs) {
                            continue;
                        }
                    }

                    if ($methodName && $reflection->hasMethod($methodName)) {
                        $method = $reflection->getMethod($methodName);
                        if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                            // Check if value is cached
                            if ($computed->cache && isset(self::$computedValuesCache[$class][$objectId][$name])) {
                                $result[$name] = self::$computedValuesCache[$class][$objectId][$name];
                            } else {
                                try {
                                    $value = $method->invoke($dto);
                                    $result[$name] = $value;

                                    // Cache the value if caching is enabled
                                    if ($computed->cache) {
                                        if (!isset(self::$computedValuesCache[$class])) {
                                            self::$computedValuesCache[$class] = [];
                                        }
                                        if (!isset(self::$computedValuesCache[$class][$objectId])) {
                                            self::$computedValuesCache[$class][$objectId] = [];
                                        }
                                        self::$computedValuesCache[$class][$objectId][$name] = $value;
                                    }
                                } catch (Throwable) {
                                    // If computation fails, return null instead of throwing
                                    $result[$name] = null;
                                }
                            }
                        }
                    }
                }
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

        // Get feature flags (cached after first call)
        $flags = self::getFeatureFlags($class);

            // Fast path: No attributes that affect toJsonArray()
            if (!$flags['hasAnyJsonAttribute']) {
                return get_object_vars($dto);
            }

            // Slow path: Process attributes
            $reflection = self::getReflection($class);
            $data = get_object_vars($dto);

            // Get included computed properties for lazy handling
            $objectId = spl_object_id($dto);
            $includedComputed = self::$includedComputedCache[$objectId] ?? [];

            // Clear the included computed cache after reading it (one-time use)
            if (isset(self::$includedComputedCache[$objectId])) {
                unset(self::$includedComputedCache[$objectId]);
            }

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
                    $lazyProperties = self::$lazyCache[$class] ?? [];
                    // Only skip lazy properties that are not explicitly included
                    foreach ($lazyProperties as $lazyName => $lazyValue) {
                        if (!in_array($lazyName, $includedComputed, true)) {
                            $toSkip[$lazyName] = $lazyValue;
                        }
                    }
                }
            }

            $result = [];

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                // Skip Hidden/HiddenFromJson properties (only if skip set exists)
                if (null !== $toSkip && isset($toSkip[$name])) {
                    continue;
                }

                $value = $data[$name];

                // Handle Lazy values (only if flag is set)
                if ($flags['hasLazy'] && $value instanceof \event4u\DataHelpers\Support\Lazy) {
                    // Unwrap lazy value (already checked in toSkip)
                    $value = $value->get();
                }

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

                // Apply output casts FIRST (only if NOT #[NoCasts])
                $convertedValue = $value;
                if (!$flags['hasNoCasts']) {
                    $convertedValue = self::applyOutputCast($class, $name, $value, $data);
                }

                // Then convert value only for complex types (nested DTOs, arrays)
                // But skip if already converted by cast
                if ($convertedValue === $value && (is_object($value) || is_array($value))) {
                    $convertedValue = self::convertValue($value, $class, $name, $reflectionProperty);
                }

                $result[$outputName] = $convertedValue;
            }

            // Add computed properties - only if flag is set
            if ($flags['hasComputed']) {
                $computedMethods = self::getComputedMethods($class);
                foreach ($computedMethods as $name => $computed) {
                    // Skip lazy computed properties unless explicitly included
                    if ($computed->lazy && !in_array($name, $includedComputed, true)) {
                        continue;
                    }

                    // Get method name (use original method name, not custom name)
                    $methodName = null;
                    $methodReflection = null;
                    foreach ($reflection->getMethods() as $method) {
                        $attrs = $method->getAttributes(Computed::class);
                        if ([] !== $attrs) {
                            /** @var Computed $attr */
                            $attr = $attrs[0]->newInstance();
                            $attrName = $attr->name ?? $method->getName();
                            if ($attrName === $name) {
                                $methodName = $method->getName();
                                $methodReflection = $method;
                                break;
                            }
                        }
                    }

                    // Check if computed property is hidden (Hidden, HiddenFromJson)
                    if ($methodReflection) {
                        $hiddenAttrs = $methodReflection->getAttributes(Hidden::class);
                        $hiddenFromJsonAttrs = $methodReflection->getAttributes(HiddenFromJson::class);
                        if ([] !== $hiddenAttrs || [] !== $hiddenFromJsonAttrs) {
                            continue;
                        }
                    }

                    if ($methodName && $reflection->hasMethod($methodName)) {
                        $method = $reflection->getMethod($methodName);
                        if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                            // Check if value is cached
                            if ($computed->cache && isset(self::$computedValuesCache[$class][$objectId][$name])) {
                                $result[$name] = self::$computedValuesCache[$class][$objectId][$name];
                            } else {
                                try {
                                    $value = $method->invoke($dto);
                                    $result[$name] = $value;

                                    // Cache the value if caching is enabled
                                    if ($computed->cache) {
                                        if (!isset(self::$computedValuesCache[$class])) {
                                            self::$computedValuesCache[$class] = [];
                                        }
                                        if (!isset(self::$computedValuesCache[$class][$objectId])) {
                                            self::$computedValuesCache[$class][$objectId] = [];
                                        }
                                        self::$computedValuesCache[$class][$objectId][$name] = $value;
                                    }
                                } catch (Throwable) {
                                    // If computation fails, return null instead of throwing
                                    $result[$name] = null;
                                }
                            }
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
        if (self::shouldConvertValueToNull($class, $name, $param, $value)) {
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
                        /** @var class-string<SimpleDto> $dataCollectionOfClass */
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

                // Check if it's a SimpleDto (nested DTO)
                if (!$type->isBuiltin() && is_subclass_of($typeName, SimpleDto::class)) {
                    // Single nested DTO
                    /** @var class-string<SimpleDto> $typeName */
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
     * Get casts() method results for class.
     *
     * @param class-string $class
     * @return array<string, mixed>
     */
    private static function getCastsForClass(string $class): array
    {
        // Check cache
        if (isset(self::$castsCache[$class])) {
            return self::$castsCache[$class];
        }

        $casts = [];

        // Check if class has casts() method
        if (method_exists($class, 'casts')) {
            try {
                // Try to call it statically first (for static methods)
                $casts = $class::casts();
            } catch (Error) {
                // If that fails, try with reflection (for non-static methods)
                try {
                    $reflection = self::getReflection($class);
                    if ($reflection->hasMethod('casts')) {
                        $castsMethod = $reflection->getMethod('casts');

                        // Create instance without calling constructor
                        $instance = $reflection->newInstanceWithoutConstructor();

                        $casts = $castsMethod->invoke($instance);
                    }
                } catch (Throwable) {
                    // If all fails, return empty array
                    $casts = [];
                }
            }
        }

        self::$castsCache[$class] = $casts;
        return $casts;
    }

    /**
     * Get mapping configuration for a class.
     *
     * Returns an array mapping property names to their source keys.
     *
     * @param class-string $class
     * @return array<string, string|array<int, string>>
     */
    public static function getMappingConfig(string $class): array
    {
        $config = [];
        $reflection = self::getReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return $config;
        }

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $paramName = $reflectionParameter->getName();

            // Check for #[MapFrom] attribute
            $mapFromAttrs = $reflectionParameter->getAttributes(MapFrom::class);
            if (!empty($mapFromAttrs)) {
                /** @var MapFrom $mapFrom */
                $mapFrom = $mapFromAttrs[0]->newInstance();
                $config[$paramName] = $mapFrom->source;
                continue;
            }

            // Check for class-level #[MapInputName]
            $mapInputName = self::getMapInputName($class);
            if ($mapInputName instanceof MapInputName) {
                $config[$paramName] = $mapInputName->convention->transform($paramName);
                continue;
            }

            // No mapping - use parameter name
            $config[$paramName] = $paramName;
        }

        return $config;
    }

    /**
     * Clear mapping cache for a class.
     *
     * @param class-string $class
     */
    public static function clearMappingCache(string $class): void
    {
        unset(self::$fromMappingCache[$class]);
        unset(self::$mapInputNameCache[$class]);
        unset(self::$mapOutputNameCache[$class]);
    }

    /**
     * Check if a computed property has a cached value.
     *
     * @param object $dto The DTO instance
     * @param string $name The name of the computed property
     * @return bool True if the computed property has a cached value
     */
    public static function hasComputedCache(object $dto, string $name): bool
    {
        $class = $dto::class;

        // Check if computed cache exists for this class and property
        if (!isset(self::$computedValuesCache[$class])) {
            return false;
        }

        $objectId = spl_object_id($dto);
        return isset(self::$computedValuesCache[$class][$objectId][$name]);
    }

    /**
     * Include lazy computed properties in the next toArray() or toJson() call.
     *
     * This method stores the specified lazy computed properties for inclusion
     * in the next serialization call. It also clears any cached values for these
     * properties to ensure they are recomputed.
     *
     * @param object $dto The DTO instance
     * @param array<int, string> $names The names of the lazy computed properties to include
     * @return object The same instance with the lazy computed properties marked for inclusion
     */
    public static function includeComputed(object $dto, array $names): object
    {
        $class = $dto::class;
        $objectId = spl_object_id($dto);

        // Store the included computed property names for this instance
        if (!isset(self::$includedComputedCache[$objectId])) {
            self::$includedComputedCache[$objectId] = [];
        }

        foreach ($names as $name) {
            self::$includedComputedCache[$objectId][] = $name;

            // Clear cached value for this property to force recomputation
            if (isset(self::$computedValuesCache[$class][$objectId][$name])) {
                unset(self::$computedValuesCache[$class][$objectId][$name]);
            }
        }

        return $dto;
    }

    /**
     * Check if a DTO instance has included computed properties.
     *
     * This is used by FastPath to determine if the DTO can use the fast path at runtime.
     *
     * @param int $objectId The object ID of the DTO instance
     * @return bool True if the DTO has included computed properties
     */
    public static function hasIncludedComputed(int $objectId): bool
    {
        return isset(self::$includedComputedCache[$objectId]) && !empty(self::$includedComputedCache[$objectId]);
    }

    /**
     * Clear the computed property cache for a DTO instance.
     *
     * @param object $dto The DTO instance
     * @param string|null $property Specific property to clear, or null to clear all
     */
    public static function clearComputedCache(object $dto, ?string $property = null): void
    {
        $class = $dto::class;
        $objectId = spl_object_id($dto);

        if (null === $property) {
            // Clear all computed values for this instance
            if (isset(self::$computedValuesCache[$class][$objectId])) {
                unset(self::$computedValuesCache[$class][$objectId]);
            }
        } elseif (isset(self::$computedValuesCache[$class][$objectId][$property])) {
            // Clear specific computed value
            unset(self::$computedValuesCache[$class][$objectId][$property]);
        }
    }

    /**
     * Include all lazy properties in the next toArray() or toJson() call.
     *
     * @param object $dto The DTO instance
     * @return object The same instance with all lazy properties marked for inclusion
     */
    public static function includeAllLazy(object $dto): object
    {
        $class = $dto::class;

        // Get all lazy property names
        $lazyProperties = array_keys(self::$lazyCache[$class] ?? []);

        // Get all lazy computed property names
        $computedMethods = self::getComputedMethods($class);
        foreach ($computedMethods as $name => $computed) {
            if ($computed->lazy) {
                $lazyProperties[] = $name;
            }
        }

        // Include all lazy properties
        if ([] !== $lazyProperties) {
            return self::includeComputed($dto, $lazyProperties);
        }

        return $dto;
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
     * Check if value should be converted to null based on ConvertEmptyToNull attribute.
     *
     * @param class-string $class
     */
    private static function shouldConvertValueToNull(
        string $class,
        string $name,
        ReflectionParameter $param,
        mixed $value
    ): bool
    {
        // Check for #[ConvertEmptyToNull] attribute on parameter
        $attrs = $param->getAttributes(ConvertEmptyToNull::class);

        // If not on parameter, check for class-level attribute
        if ([] === $attrs) {
            $reflection = self::getReflection($class);
            $attrs = $reflection->getAttributes(ConvertEmptyToNull::class);

            if ([] === $attrs) {
                return false;
            }
        }

        /** @var ConvertEmptyToNull $attr */
        $attr = $attrs[0]->newInstance();

        // Always convert empty strings and arrays
        if ('' === $value || [] === $value) {
            return true;
        }

        // Convert string zero if enabled
        if ($attr->convertStringZero && '0' === $value) {
            return true;
        }

        // Convert numeric zero values if enabled
        if ($attr->convertZero && (0 === $value || 0.0 === $value)) {
            return true;
        }
        // Convert false if enabled
        return $attr->convertFalse && false === $value;
    }

    /**
     * Check if parameter should convert empty to null (legacy method for cache).
     *
     * @param class-string $class
     * @deprecated Use shouldConvertValueToNull instead
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
                    if (class_exists($className) && is_subclass_of($className, SimpleDto::class)) {
                        return $className;
                    }
                }

                if (preg_match('/@var\s+([^\[\]]+)\[\]/', $docComment, $matches)) {
                    $className = trim($matches[1]);
                    if (class_exists($className) && is_subclass_of($className, SimpleDto::class)) {
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
     * Check if a property is mutable (can be modified after construction).
     *
     * A property is mutable if:
     * - The class has #[NotImmutable] attribute (all properties mutable)
     * - The specific property has #[NotImmutable] attribute
     *
     * Performance: Uses cached feature flags, zero overhead after first scan.
     *
     * @param class-string $class
     */
    public static function isPropertyMutable(string $class, string $propertyName): bool
    {
        $flags = self::getFeatureFlags($class);

        // If class doesn't use NotImmutable at all, return false immediately
        if (!$flags['hasNotImmutable']) {
            return false;
        }

        // If class has #[NotImmutable], ALL properties are mutable
        if ($flags['classNotImmutable']) {
            return true;
        }

        // Otherwise, check if specific property is marked as NotImmutable
        return isset($flags['notImmutableProperties'][$propertyName]);
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
                'hasNotImmutable' => false,
                'classNotImmutable' => false,
                'notImmutableProperties' => [],
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
            'hasNotImmutable' => false,
            'classNotImmutable' => false,
            'notImmutableProperties' => [],
        ];

        // Check for class-level attributes
        if ([] !== $reflection->getAttributes(MapInputName::class)) {
            $flags['hasMapInputName'] = true;
        }
        if ([] !== $reflection->getAttributes(MapOutputName::class)) {
            $flags['hasMapOutputName'] = true;
        }
        if ([] !== $reflection->getAttributes(ConvertEmptyToNull::class)) {
            $flags['hasConvertEmptyToNull'] = true;
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

        // Check for class-level #[NotImmutable]
        if ([] !== $reflection->getAttributes(NotImmutable::class)) {
            $flags['hasNotImmutable'] = true;
            $flags['classNotImmutable'] = true;
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

                // NotImmutable - fill cache while scanning constructor parameters
                if ([] !== $reflectionParameter->getAttributes(NotImmutable::class)) {
                    $flags['hasNotImmutable'] = true;
                    $flags['notImmutableProperties'][$paramName] = true;
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

            // NotImmutable - fill cache while scanning properties
            if ([] !== $reflectionProperty->getAttributes(NotImmutable::class)) {
                $flags['hasNotImmutable'] = true;
                $flags['notImmutableProperties'][$propName] = true;
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

        // Check if class has casts() method
        $hasCasts = false;
        if (!$flags['hasNoCasts']) {
            $casts = self::getCastsForClass($class);
            $hasCasts = [] !== $casts;
        }

        // Set combined flags for fast-path checks
        $flags['hasAnyArrayAttribute'] = $flags['hasMapTo'] || $flags['hasMapOutputName'] || $flags['hasEnumSerialize'] ||
            $flags['hasHidden'] || $flags['hasHiddenFromArray'] ||
            $flags['hasLazy'] || $flags['hasComputed'] || $flags['hasOptional'] ||
            $flags['hasConditionalProperties'] || $hasCasts;

        $flags['hasAnyJsonAttribute'] = $flags['hasMapTo'] || $flags['hasMapOutputName'] || $flags['hasEnumSerialize'] ||
            $flags['hasHidden'] || $flags['hasHiddenFromJson'] ||
            $flags['hasLazy'] || $flags['hasComputed'] || $flags['hasOptional'] ||
            $flags['hasConditionalProperties'] || $hasCasts;

        // Cache and return
        self::$featureFlags[$class] = $flags;
        return $flags;
    }

    /**
     * Internal method to create DTO from data.
     *
     * @param class-string $class
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     */
    private static function createFromDataInternal(
        string $class,
        mixed $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): object {
        // Performance: Only apply DataMapper if at least one parameter is provided
        // This avoids unnecessary overhead when no mapping/filtering is needed
        $hasTemplate = null !== $template && [] !== $template;
        $hasFilters = null !== $filters && [] !== $filters;
        $hasPipeline = null !== $pipeline && [] !== $pipeline;

        // Track if template was applied (for priority handling)
        $templateApplied = false;

        if ($hasTemplate || $hasFilters || $hasPipeline) {
            $data = self::applyDataMapper($data, $template, $filters, $pipeline);
            $templateApplied = $hasTemplate; // Template has highest priority
        }

        // Check if ConverterMode is enabled
        $converterMode = self::hasConverterMode($class);

        // Parse data if not array and ConverterMode is enabled
        if (!is_array($data)) {
            if ($converterMode) {
                $data = self::parseWithConverter($data);
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'SimpleDto only accepts arrays by default. Use #[ConverterMode] attribute on %s to enable JSON/XML/CSV support.',
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

            // Step 1: Determine source key for this parameter
            // Skip #[MapFrom] if template was applied (template has highest priority)
            $sourceKey = null;

            if (!$templateApplied) {
                // Check for #[MapFrom] attribute (highest priority after template)
                if ($flags['hasMapFrom']) {
                    $mapFromAttrs = $reflectionParameter->getAttributes(MapFrom::class);
                    if (!empty($mapFromAttrs)) {
                        /** @var MapFrom $mapFrom */
                        $mapFrom = $mapFromAttrs[0]->newInstance();
                        $sourceKey = $mapFrom->source; // Can be string or array
                    }
                }

                // If no MapFrom, check for class-level MapInputName
                if (null === $sourceKey && $flags['hasMapInputName']) {
                    $mapInputName = self::getMapInputName($class);
                    if ($mapInputName instanceof MapInputName) {
                        // Transform property name to input naming convention
                        $sourceKey = $mapInputName->convention->transform($paramName);
                    }
                }
            }

            // If still no source key, use parameter name
            if (null === $sourceKey) {
                $sourceKey = $paramName;
            }

            // Now resolve the value from data using the source key(s)
            $sources = is_array($sourceKey) ? $sourceKey : [$sourceKey];
            $wasProvided = false;
            $value = null;

            foreach ($sources as $key) {
                // Support dot notation for nested properties
                if (str_contains($key, '.')) {
                    $keys = explode('.', $key);
                    $tempValue = $data;
                    $found = true;
                    foreach ($keys as $nestedKey) {
                        if (is_array($tempValue) && array_key_exists($nestedKey, $tempValue)) {
                            $tempValue = $tempValue[$nestedKey];
                        } else {
                            $found = false;
                            break;
                        }
                    }
                    if ($found) {
                        $wasProvided = true;
                        $value = $tempValue;
                        break;
                    }
                } elseif (array_key_exists($key, $data)) {
                    $wasProvided = true;
                    $value = $data[$key];
                    break;
                }
            }

            // Step 2: Check for #[ConvertEmptyToNull] (only if flag is set)
            if ($flags['hasConvertEmptyToNull'] && self::shouldConvertValueToNull(
                $class,
                $paramName,
                $reflectionParameter,
                $value
            )) {
                $value = null;
            }

            // Step 3: Apply casting (only if NOT #[NoCasts])
            if (!$flags['hasNoCasts']) {
                // Step 3a: Apply casts (check casts() method first, then #[CastWith] attribute)
                if (null !== $value) {
                    $casterClass = null;
                    $casterInstance = null;

                    // First check for casts() method
                    $casts = self::getCastsForClass($class);
                    if ([] !== $casts && isset($casts[$paramName])) {
                        $castDef = $casts[$paramName];
                        // Handle string cast definitions (e.g., 'hashed', 'hashed:argon2id', 'encrypted')
                        if (is_string($castDef)) {
                            [$castType, $castArgs] = str_contains($castDef, ':')
                                ? explode(':', $castDef, 2)
                                : [$castDef, null];

                            // Check if castType is a class name (contains backslash)
                            if (str_contains($castType, '\\') && class_exists($castType)) {
                                // Full class name with optional args
                                $casterInstance = $castArgs ? new $castType($castArgs) : new $castType();
                            } else {
                                // Map string cast types to classes
                                $casterClass = match($castType) {
                                    'array' => ArrayCast::class,
                                    'json' => JsonCast::class,
                                    'boolean', 'bool' => BooleanCast::class,
                                    'integer', 'int' => IntegerCast::class,
                                    'float', 'double' => FloatCast::class,
                                    'string' => StringCast::class,
                                    'datetime' => DateTimeCast::class,
                                    'timestamp' => TimestampCast::class,
                                    'decimal' => DecimalCast::class,
                                    'collection' => CollectionCast::class,
                                    'enum' => EnumCast::class,
                                    'dto' => DtoCast::class,
                                    'hashed' => HashedCast::class,
                                    'encrypted' => EncryptedCast::class,
                                    default => null,
                                };

                                if ($casterClass && class_exists($casterClass)) {
                                    $casterInstance = $castArgs ? new $casterClass($castArgs) : new $casterClass();
                                }
                            }
                        }
                        // Handle object cast definitions (e.g., new HashedCast())
                        elseif (is_object($castDef)) {
                            $casterInstance = $castDef;
                        }
                    }

                    // Then check for #[CastWith] attribute (only if no casts() method cast found)
                    if (null === $casterInstance && $flags['hasCastWith']) {
                        $castWithAttrs = $reflectionParameter->getAttributes(CastWith::class);
                        if (!empty($castWithAttrs)) {
                            /** @var CastWith $castWith */
                            $castWith = $castWithAttrs[0]->newInstance();
                            $casterClass = $castWith->casterClass;

                            if (class_exists($casterClass)) {
                                $casterInstance = new $casterClass();
                            }
                        }
                    }

                    // Apply the caster if found
                    if (null !== $casterInstance) {
                        // Check if it implements CastsAttributes interface
                        if ($casterInstance instanceof CastsAttributes) {
                            $value = $casterInstance->get($value, $data);
                        }
                        // Otherwise check for static cast() method
                        elseif (method_exists($casterInstance, 'cast')) {
                            $value = $casterInstance::cast($value);
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

                // Step 3c: Cast nested DTOs (automatic casting)
                if (null !== $value) {
                    $type = $reflectionParameter->getType();
                    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                        $typeName = $type->getName();

                        // Cast to nested SimpleDto
                        if (is_subclass_of($typeName, SimpleDto::class)) {
                            // Skip if already an instance of the target type
                            if (!$value instanceof $typeName && (is_array($value) || is_object($value) || is_string(
                                $value
                            ))) {
                                /** @var class-string<SimpleDto> $typeName */
                                $value = $typeName::from($value);
                            }
                        }
                        // Cast to DateTime/DateTimeImmutable
                        elseif (DateTime::class === $typeName || DateTimeImmutable::class === $typeName) {
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

            // Step 4.5: Wrap in Lazy if needed (only if flag is set)
            // Wrap in Lazy if not already a Lazy instance
            if ($flags['hasLazy'] && isset(self::$lazyCache[$class][$paramName]) && !($value instanceof \event4u\DataHelpers\Support\Lazy)) {
                $value = \event4u\DataHelpers\Support\Lazy::value($value);
            }

            // Step 5: Add to args
            // If value was not provided and parameter has default value, use the default
            // Otherwise use the (possibly casted) value
            if (!$wasProvided && $reflectionParameter->isDefaultValueAvailable()) {
                $args[] = $reflectionParameter->getDefaultValue();
            } else {
                $args[] = $value;
            }
        }

        // Create instance
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Validate data before creating DTO.
     *
     * @param class-string<SimpleDto> $class
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
                            'SimpleDto only accepts arrays in standard mode. Use #[ConverterMode] attribute on %s to enable JSON/XML/CSV support.',
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
                        'SimpleDto only accepts arrays in standard mode. Use #[ConverterMode] attribute on %s to enable JSON/XML/CSV support.',
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
    public static function validateInstance(SimpleDto $dto): ValidationResult
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

    /** Auto-cast value to native PHP type with smart casting. */
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

        // Cast to target type with smart casting
        return match ($typeName) {
            'int' => self::castToInt($value),
            'float' => self::castToFloat($value),
            'string' => self::castToString($value),
            'bool' => self::castToBool($value),
            'array' => self::castToArray($value),
            default => $value,
        };
    }

    /** Smart cast to integer (handles whitespace and non-numeric strings). */
    private static function castToInt(mixed $value): ?int
    {
        // Don't cast arrays or objects to int - return null to trigger TypeError
        if (is_array($value) || is_object($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            // Return null for empty or non-numeric strings
            if ('' === $value || !is_numeric($value)) {
                return null;
            }
        }

        return (int)$value;
    }

    /** Smart cast to float (handles whitespace and non-numeric strings). */
    private static function castToFloat(mixed $value): ?float
    {
        // Don't cast arrays or objects to float - return null to trigger TypeError
        if (is_array($value) || is_object($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            // Return null for empty or non-numeric strings
            if ('' === $value || !is_numeric($value)) {
                return null;
            }
        }

        return (float)$value;
    }

    /** Smart cast to string. */
    private static function castToString(mixed $value): ?string
    {
        // Don't cast arrays to string - return null to trigger TypeError
        if (is_array($value)) {
            return null;
        }

        // Allow objects with __toString() method
        if (is_object($value) && !method_exists($value, '__toString')) {
            return null;
        }

        return (string)$value;
    }

    /** Smart cast to boolean (handles string values like 'true', 'false', 'yes', 'no', etc.). */
    private static function castToBool(mixed $value): ?bool
    {
        // Don't cast arrays or objects to bool - return null to trigger TypeError
        if (is_array($value) || is_object($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            $lower = strtolower($value);

            // Handle common boolean string representations
            if (in_array($lower, ['true', 'yes', 'on', '1'], true)) {
                return true;
            }

            if (in_array($lower, ['false', 'no', 'off', '0', ''], true)) {
                return false;
            }

            // Unknown string - return null to trigger TypeError
            return null;
        }

        return (bool)$value;
    }

    /** Smart cast to array (handles JSON strings and objects). */
    private static function castToArray(mixed $value): ?array
    {
        // If it's a JSON string, try to decode it
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                } catch (JsonException) {
                    // Invalid JSON - return null to trigger TypeError
                    return null;
                }
            }

            // Non-JSON string - return null to trigger TypeError
            return null;
        }

        // Cast objects to arrays
        if (is_object($value)) {
            return (array)$value;
        }

        // Non-string, non-array, non-object - return null to trigger TypeError
        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Apply output cast to a value (for toArray/toJson).
     *
     * @param class-string $class
     * @param array<string, mixed> $data
     */
    private static function applyOutputCast(string $class, string $propertyName, mixed $value, array $data): mixed
    {
        // First check for casts() method
        $casts = self::getCastsForClass($class);
        if ([] !== $casts && isset($casts[$propertyName])) {
            $castDef = $casts[$propertyName];

            // Handle string cast definitions
            if (is_string($castDef)) {
                $casterInstance = null;
                [$castType, $castArgs] = str_contains($castDef, ':')
                    ? explode(':', $castDef, 2)
                    : [$castDef, null];

                // Check if castType is a class name (contains backslash)
                if (str_contains($castType, '\\') && class_exists($castType)) {
                    $casterInstance = $castArgs ? new $castType($castArgs) : new $castType();
                } else {
                    // Map string cast types to classes
                    $casterClass = match($castType) {
                        'array' => ArrayCast::class,
                        'json' => JsonCast::class,
                        'boolean', 'bool' => BooleanCast::class,
                        'integer', 'int' => IntegerCast::class,
                        'float', 'double' => FloatCast::class,
                        'string' => StringCast::class,
                        'datetime' => DateTimeCast::class,
                        'timestamp' => TimestampCast::class,
                        'decimal' => DecimalCast::class,
                        'collection' => CollectionCast::class,
                        'enum' => EnumCast::class,
                        'dto' => DtoCast::class,
                        'hashed' => HashedCast::class,
                        'encrypted' => EncryptedCast::class,
                        default => null,
                    };

                    if ($casterClass && class_exists($casterClass)) {
                        $casterInstance = $castArgs ? new $casterClass($castArgs) : new $casterClass();
                    }
                }

                // Apply the caster if found
                // Check if it implements CastsAttributes interface
                if (isset($casterInstance) && $casterInstance instanceof CastsAttributes) {
                    return $casterInstance->set($value, $data);
                }
            }
            // Handle object cast definitions
            elseif (is_object($castDef)) {
                if ($castDef instanceof CastsAttributes) {
                    return $castDef->set($value, $data);
                }
            }
        }

        // Then check for #[CastWith] attribute
        $reflection = self::getReflection($class);
        foreach ($reflection->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getName() === $propertyName) {
                $castWithAttrs = $reflectionProperty->getAttributes(CastWith::class);
                if (!empty($castWithAttrs)) {
                    /** @var CastWith $castWith */
                    $castWith = $castWithAttrs[0]->newInstance();
                    $casterInstance = $castWith->args ? new $castWith->caster(
                        $castWith->args
                    ) : new $castWith->caster();

                    if ($casterInstance instanceof CastsAttributes) {
                        return $casterInstance->set($value, $data);
                    }
                }
                break;
            }
        }

        return $value;
    }

    /**
     * Apply DataMapper with template, filters, and pipeline.
     *
     * @param mixed $data Source data
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     * @return array<string, mixed>
     */
    private static function applyDataMapper(
        mixed $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): array {
        // If no template but filters or pipeline are set, create auto-template
        // that maps all source keys 1:1 (identity mapping)
        if (null === $template && is_array($data)) {
            $template = [];
            foreach (array_keys($data) as $key) {
                $template[$key] = sprintf('{{ %s }}', $key);
            }
        }

        $mapper = DataMapper::source($data);

        // Apply template if defined
        if (null !== $template && [] !== $template) {
            $mapper = $mapper->template($template);
        }

        // Apply property filters if defined
        if (null !== $filters && [] !== $filters) {
            $mapper = $mapper->setFilters($filters);
        }

        // Apply pipeline filters if defined
        if (null !== $pipeline && [] !== $pipeline) {
            $mapper = $mapper->pipeline($pipeline);
        }

        // Map and get result
        $result = $mapper->map()->getTarget();

        // Ensure result is array
        if (!is_array($result)) {
            throw new InvalidArgumentException('DataMapper result must be an array');
        }

        return $result;
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
