<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\ValueTransformer;
use event4u\DataHelpers\Support\ArrayableHelper;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Handles automatic field mapping with snake_case to camelCase conversion.
 */
class AutoMapper
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $refClassCache = [];

    /** @var array<class-string, array<int, string>> */
    private static array $propertyNamesCache = [];

    /**
     * Get cached ReflectionClass instance.
     *
     * @return ReflectionClass<object>
     */
    private static function getReflection(object $object): ReflectionClass
    {
        $class = $object::class;

        return self::$refClassCache[$class] ??= new ReflectionClass($object);
    }

    /**
     * Get cached property names for an object.
     *
     * @return array<int, string>
     */
    private static function getPropertyNames(object $object): array
    {
        $class = $object::class;

        if (!isset(self::$propertyNamesCache[$class])) {
            $reflection = self::getReflection($object);
            $properties = $reflection->getProperties();
            self::$propertyNamesCache[$class] = array_map(
                fn(ReflectionProperty $prop): string => $prop->getName(),
                $properties
            );
        }

        return self::$propertyNamesCache[$class];
    }

    /**
     * Automatically map fields from source to target with optional snake_case → camelCase conversion.
     *
     * - Top-level only (deep=false): maps only direct properties/keys
     * - Deep (deep=true): recursively maps nested structures
     * - Unknown/unsupported targets are coerced to array
     * - skipNull, reindexWildcard, hooks, trimValues, caseInsensitiveReplace behave as in map()
     *
     * @param array<string, mixed> $hooks Optional hooks propagated to this mapping
     */
    public static function autoMap(
        mixed $source,
        mixed $target,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
        bool $deep = false,
    ): mixed {
        // Coerce JSON string sources to arrays when possible
        if (is_string($source)) {
            $decoded = json_decode($source, true);
            if (is_array($decoded)) {
                $source = $decoded;
            }
        }

        // Ensure target is a supported type for mutation
        if (!is_array($target) && !is_object($target)) {
            $target = [];
        }

        $pairs = [];

        if ($deep) {
            // Build mapping pairs from deep flattened source paths (use wildcard for numeric indices)
            foreach (self::flattenSourcePaths($source, true) as $path => $value) {
                if ($skipNull && null === $value) {
                    continue;
                }

                // Build target path: keep segments; if target is object, prefer camelCase for first segment when property exists
                $segments = explode('.', (string)$path);
                if (is_object($target) && isset($segments[0])) {
                    $first = $segments[0];
                    if ('*' !== $first) {
                        $camel = ValueTransformer::toCamelCase($first);
                        if (ValueTransformer::objectHasProperty($target, $camel)) {
                            $segments[0] = $camel;
                        }
                    }
                }

                // Store path directly (will be used by mapSimpleInternal)
                $pairs[implode('.', $segments)] = (string)$path;
            }
        } else {
            // Derive simple mapping: ['name' => 'name', 'email' => 'email', ...]
            foreach (self::topLevelPairs($source) as $key => $value) {
                if (!is_string($key)) {
                    // only map string keys automatically
                    continue;
                }
                if ($skipNull && null === $value) {
                    continue;
                }

                $targetKey = $key;

                // If target is object, try camel-case bridge when property exists
                if (is_object($target)) {
                    $camel = ValueTransformer::toCamelCase($key);
                    // Only prefer camel if the property exists (avoids creating unexpected props)
                    if (ValueTransformer::objectHasProperty($target, $camel)) {
                        $targetKey = $camel;
                    }
                }

                // Store key directly (will be used by mapSimpleInternal)
                $pairs[$targetKey] = $key;
            }
        }

        // Delegate to mapWithRawPaths() - AutoMapper uses raw paths without {{ }}
        return DataMapper::mapWithRawPaths(
            $source,
            $target,
            $pairs,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Get top-level key-value pairs from mixed data.
     *
     * @return array<int|string, mixed>
     */
    private static function topLevelPairs(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (ArrayableHelper::isArrayable($data)) {
            return ArrayableHelper::toArray($data);
        }

        if ($data instanceof JsonSerializable) {
            $serialized = $data->jsonSerialize();

            return is_array($serialized) ? $serialized : [];
        }

        if (is_object($data)) {
            // Use reflection to get public properties
            $reflection = new ReflectionClass($data);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
            $result = [];
            foreach ($properties as $property) {
                $result[$property->getName()] = $property->getValue($data);
            }

            return $result;
        }

        return [];
    }

    /**
     * Get all keys/properties from target.
     *
     * @return array<int, string>
     * @phpstan-ignore method.unused
     */
    private static function getTargetKeys(mixed $target): array
    {
        if (is_array($target)) {
            return array_keys($target);
        }

        if (is_object($target)) {
            return self::getPropertyNames($target);
        }

        return [];
    }

    /**
     * Find matching target key for source key (with snake_case → camelCase conversion).
     *
     * @param array<int, string> $targetKeys
     * @phpstan-ignore method.unused
     */
    private static function findMatchingTargetKey(string $sourceKey, array $targetKeys): ?string
    {
        // Direct match
        if (in_array($sourceKey, $targetKeys, true)) {
            return $sourceKey;
        }

        // Try camelCase conversion
        $camelCase = ValueTransformer::toCamelCase($sourceKey);
        if (in_array($camelCase, $targetKeys, true)) {
            return $camelCase;
        }

        return null;
    }

    /**
     * Get nested target for deep mapping.
     *
     * @phpstan-ignore method.unused
     */
    private static function getNestedTarget(mixed $target, string $key): mixed
    {
        if (is_array($target)) {
            return $target[$key] ?? [];
        }

        if (is_object($target) && property_exists($target, $key)) {
            $reflection = new ReflectionProperty($target, $key);

            return $reflection->getValue($target);
        }

        return [];
    }

    /**
     * Flatten a source structure into dot-notation paths. Numeric indices are replaced by '*'
     * to allow wildcard-based mapping of lists.
     *
     * @return array<string, mixed> Map of path => leaf value
     */
    public static function flattenSourcePaths(mixed $data, bool $useWildcards = true, string $prefix = ''): array
    {
        $result = [];

        // Scalars and null: treat as leaf value
        if (!is_array($data) && !is_object($data)) {
            return [
                $prefix => $data,
            ];
        }

        // Convert to array-like structure
        if (ArrayableHelper::isArrayable($data)) {
            $data = ArrayableHelper::toArray($data);
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif (is_object($data)) {
            $reflection = self::getReflection($data);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
            $arrayData = [];
            foreach ($properties as $property) {
                $arrayData[$property->getName()] = $property->getValue($data);
            }
            $data = $arrayData;
        }

        if (!is_array($data)) {
            return [
                $prefix => $data,
            ];
        }

        foreach ($data as $key => $value) {
            $segment = $useWildcards && is_int($key) ? '*' : (string)$key;
            $path = '' === $prefix ? $segment : $prefix . '.' . $segment;

            if (is_array($value) || is_object($value)) {
                $nested = self::flattenSourcePaths($value, $useWildcards, $path);
                $result = array_merge($result, $nested);
            } else {
                $result[$path] = $value;
            }
        }

        return $result;
    }
}
