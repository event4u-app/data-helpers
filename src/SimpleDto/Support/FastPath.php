<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use ReflectionClass;
use Throwable;

/**
 * Phase 7: Fast Path Optimization
 *
 * Provides fast path for simple DTOs without attributes, mapping, or runtime modifications.
 * Can achieve 30-50% performance improvement for simple DTOs.
 *
 * A "simple DTO" is one without:
 * - Class-level attributes (e.g., #[AutoCast], #[MapOutputName])
 * - Property attributes (e.g., #[Hidden], #[Visible], #[Lazy], #[Computed], #[MapFrom], #[MapTo])
 * - Method attributes (e.g., #[Computed] on methods)
 * - Optional or Lazy wrapper types
 * - Method overrides (casts(), template(), filters(), rules(), computed())
 * - Runtime modifications (only(), except(), with(), withContext(), etc.)
 *
 * IMPORTANT: Custom Attributes
 * ----------------------------
 * If you create custom attributes, they MUST be in one of these namespaces to be detected:
 * - event4u\DataHelpers\SimpleDto\Attributes\*
 * - event4u\DataHelpers\SimpleDto\Contracts\*
 *
 * Attributes outside these namespaces will NOT be detected and may cause incorrect behavior!
 *
 * Example of a custom attribute that WILL be detected:
 * ```php
 * namespace event4u\DataHelpers\SimpleDto\Attributes;
 *
 * #[Attribute]
 * class MyCustomAttribute {}
 * ```
 */
class FastPath
{
    /**
     * Cache for fast path eligibility per class.
     *
     * @var array<string, bool>
     */
    private static array $eligibilityCache = [];

    /**
     * Internal properties that should be excluded from toArray.
     *
     * @var array<string, true>
     */
    private const INTERNAL_PROPERTIES = [
        'onlyProperties' => true,
        'exceptProperties' => true,
        'visibilityContext' => true,
        'computedCache' => true,
        'includedComputed' => true,
        'includedLazy' => true,
        'includeAllLazy' => true,
        'wrapKey' => true,
        'objectVarsCache' => true,
        'castedProperties' => true,
        'conditionalContext' => true,
        'additionalData' => true,
        'sortingEnabled' => true,
        'sortDirection' => true,
        'nestedSort' => true,
        'sortCallback' => true,
    ];

    /**
     * Check if a DTO class can use the fast path.
     *
     * @param class-string $class
     */
    public static function canUseFastPath(string $class): bool
    {
        // Check cache first
        if (isset(self::$eligibilityCache[$class])) {
            return self::$eligibilityCache[$class];
        }

        try {
            $reflection = new ReflectionClass($class);

            // Check 1: No class-level #[AutoCast]
            if (self::hasAutoCastAttribute($reflection)) {
                return self::$eligibilityCache[$class] = false;
            }

            // Check 2: No property attributes
            if (self::hasPropertyAttributes($reflection)) {
                return self::$eligibilityCache[$class] = false;
            }

            // Check 3: No method overrides (casts, template)
            if (self::hasMethodOverrides($reflection)) {
                return self::$eligibilityCache[$class] = false;
            }

            // All checks passed - eligible for fast path
            return self::$eligibilityCache[$class] = true;
        } catch (Throwable) {
            // On error, fall back to normal path
            return self::$eligibilityCache[$class] = false;
        }
    }

    /**
     * Check if a DTO instance can use the fast path at runtime.
     *
     * This checks for runtime modifications like only(), except(), with(), etc.
     *
     * Note: We use (array) cast to get ALL properties including private ones from traits.
     * This is more reliable than ReflectionClass::getProperties() which doesn't include
     * trait properties by default.
     */
    public static function canUseFastPathAtRuntime(object $dto): bool
    {
        // Check SimpleEngine's static caches for runtime modifications
        $objectId = spl_object_id($dto);

        // Check if includeComputed() was called (stored in SimpleEngine's cache)
        if (SimpleEngine::hasIncludedComputed($objectId)) {
            return false;
        }

        // Cast to array to get ALL properties (including private from traits)
        // Format: "\0ClassName\0propertyName" for private, "\0*\0propertyName" for protected
        $allVars = (array)$dto;

        // Properties that indicate runtime modifications
        $checkProperties = [
            'onlyProperties',
            'exceptProperties',
            'additionalData',
            'wrapKey',
            'sortingEnabled',
            'visibilityContext',
            'includedComputed',
            'includedLazy',
            'includeAllLazy',
            'conditionalContext',
        ];

        foreach ($allVars as $key => $value) {
            // Extract property name (remove class prefix for private/protected)
            $propertyName = $key;
            if (str_contains($key, "\0")) {
                // Private: "\0ClassName\0propertyName" or Protected: "\0*\0propertyName"
                $parts = explode("\0", $key);
                $propertyName = end($parts);
            }

            // Skip if not a check property
            if (!in_array($propertyName, $checkProperties, true)) {
                continue;
            }

            // Check if property is set to a non-null/non-false value
            if (null !== $value && false !== $value) {
                // Special case: empty arrays are OK EXCEPT for onlyProperties
                // only([]) has semantic meaning (show nothing)
                if ([] === $value && 'onlyProperties' !== $propertyName) {
                    continue;
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Fast path for toArray() - skips all trait overhead.
     *
     * NOTE: This method does NOT recursively convert nested DTOs to arrays.
     * That conversion is handled by SimpleDtoTrait::processDataForSerialization()
     * which calls convertToArrayRecursive() after toArray().
     *
     * @return array<string, mixed>
     */
    public static function fastToArray(object $dto): array
    {
        $data = get_object_vars($dto);

        // Remove internal properties
        $result = [];
        foreach ($data as $key => $value) {
            if (!isset(self::INTERNAL_PROPERTIES[$key])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if class has any class-level attributes that require special handling.
     *
     * Phase 7: Check for ALL class-level attributes from our namespace, not just AutoCast.
     *
     * @param ReflectionClass<object> $reflection
     */
    private static function hasAutoCastAttribute(ReflectionClass $reflection): bool
    {
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();

            // Check if attribute is from our namespace
            if (str_starts_with($name, 'event4u\\DataHelpers\\SimpleDto\\Attributes\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if class has any property/method attributes or types that require special handling.
     *
     * Phase 7: Checks for:
     * 1. Any attribute from our namespace on properties (50+ attributes)
     * 2. Any attribute from our namespace on methods (#[Computed])
     * 3. Optional or Lazy wrapper types
     *
     * Phase 8: Don't use ReflectionCache here because we only need attribute NAMES, not instances.
     * ReflectionCache tries to instantiate attributes which may fail for some attributes.
     *
     * @param ReflectionClass<object> $reflection
     */
    private static function hasPropertyAttributes(ReflectionClass $reflection): bool
    {
        // Check 1: Property attributes
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            // Phase 8: Use direct getAttributes() to get attribute NAMES only (don't instantiate)
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $name = $attribute->getName();

                // Check if attribute is from our namespace (event4u\DataHelpers\SimpleDto\Attributes)
                // This covers all 50+ attributes without maintaining a list
                if (str_starts_with($name, 'event4u\\DataHelpers\\SimpleDto\\Attributes\\')) {
                    return true;
                }

                // Also check for Contracts (ConditionalProperty, etc.)
                if (str_starts_with($name, 'event4u\\DataHelpers\\SimpleDto\\Contracts\\')) {
                    return true;
                }
            }

            // Check 2: Optional or Lazy wrapper types
            $type = $property->getType();
            if (null !== $type) {
                $typeString = (string)$type;
                // Check for Optional or Lazy in union types
                if (str_contains($typeString, 'Optional') || str_contains($typeString, 'Lazy')) {
                    return true;
                }
            }
        }

        // Check 3: Method attributes (e.g., #[Computed] on methods)
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            // Phase 8: Use direct getAttributes() to get attribute NAMES only (don't instantiate)
            $attributes = $method->getAttributes();

            foreach ($attributes as $attribute) {
                $name = $attribute->getName();

                // Check if attribute is from our namespace
                if (str_starts_with($name, 'event4u\\DataHelpers\\SimpleDto\\Attributes\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if class overrides any methods that require special handling.
     *
     * Phase 7: Check for all methods that can customize DTO behavior.
     *
     * @param ReflectionClass<object> $reflection
     */
    private static function hasMethodOverrides(ReflectionClass $reflection): bool
    {
        // Methods that indicate custom behavior
        $checkMethods = [
            'casts',      // Custom casting
            'template',   // DataMapper template
            'filters',    // DataMapper filters
            'rules',      // Validation rules
            'computed',   // Computed properties (old API)
        ];

        foreach ($checkMethods as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                // Check if method is declared in this class (not inherited from trait)
                if ($method->getDeclaringClass()->getName() === $reflection->getName()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear the eligibility cache.
     *
     * Useful for testing or when class definitions change.
     */
    public static function clearCache(): void
    {
        self::$eligibilityCache = [];
    }

    /**
     * Get cache statistics.
     *
     * @return array{total: int, eligible: int, ineligible: int}
     */
    public static function getStats(): array
    {
        $eligible = 0;
        $ineligible = 0;

        foreach (self::$eligibilityCache as $isEligible) {
            if ($isEligible) {
                $eligible++;
            } else {
                $ineligible++;
            }
        }

        return [
            'total' => count(self::$eligibilityCache),
            'eligible' => $eligible,
            'ineligible' => $ineligible,
        ];
    }
}
