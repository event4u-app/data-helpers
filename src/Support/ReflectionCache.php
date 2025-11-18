<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute;

/**
 * Caches Reflection objects for better performance.
 *
 * Reflection operations are expensive, so we cache ReflectionClass,
 * ReflectionProperty, ReflectionMethod and ReflectionAttribute instances
 * to avoid repeated lookups.
 *
 * Phase 11a Note: This cache remains in-memory only (not persistent) because:
 * - Reflection objects cannot be serialized
 * - They are lightweight and fast to recreate
 * - ConstructorMetadata provides persistent caching for the expensive metadata extraction
 */
final class ReflectionCache
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classes = [];

    /** @var array<class-string, array<string, null|ReflectionProperty>> */
    private static array $properties = [];

    /** @var array<class-string, array<string, ReflectionMethod>> */
    private static array $methods = [];

    /** @var array<class-string, bool> Track if all methods have been loaded for a class */
    private static array $allMethodsLoaded = [];

    /** @var array<class-string, array<string, array<string, object>>> */
    private static array $propertyAttributes = [];

    /** @var array<class-string, array<string, array<string, object>>> */
    private static array $methodAttributes = [];

    /** @var array<class-string, array<string, object>> */
    private static array $classAttributes = [];

    /** @var array<class-string, array<string, array{type: ?string, isBuiltin: bool, allowsNull: bool}>> */
    private static array $propertyTypes = [];

    /** @var array<class-string, array<int, string>> */
    private static array $constructorParameters = [];

    /**
     * Get cached ReflectionClass for an object or class name.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return ReflectionClass<object>
     */
    public static function getClass(object|string $objectOrClass): ReflectionClass
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return self::$classes[$class] ??= new ReflectionClass($class);
    }

    /**
     * Get cached ReflectionProperty for an object's property.
     *
     * Returns null if the property doesn't exist.
     *
     * @param object $object Object instance
     * @param string $name Property name
     * @return ReflectionProperty|null
     */
    public static function getProperty(object $object, string $name): ?ReflectionProperty
    {
        $class = $object::class;

        // Check if we have cached properties for this class
        if (!isset(self::$properties[$class])) {
            self::$properties[$class] = [];
        }

        // Check if we've already looked up this property (including negative lookups)
        if (array_key_exists($name, self::$properties[$class])) {
            return self::$properties[$class][$name];
        }

        // Look up the property
        $refClass = self::getClass($object);
        if (!$refClass->hasProperty($name)) {
            // Cache negative lookup
            self::$properties[$class][$name] = null;

            return null;
        }

        $property = $refClass->getProperty($name);
        self::$properties[$class][$name] = $property;

        return $property;
    }

    /**
     * Check if a class has a specific property.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $name Property name
     * @return bool
     */
    public static function hasProperty(object|string $objectOrClass, string $name): bool
    {
        if (is_object($objectOrClass)) {
            return null !== self::getProperty($objectOrClass, $name);
        }

        $refClass = self::getClass($objectOrClass);

        return $refClass->hasProperty($name);
    }

    /**
     * Get cached ReflectionMethod for a class method.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $name Method name
     * @return ReflectionMethod|null
     */
    public static function getMethod(object|string $objectOrClass, string $name): ?ReflectionMethod
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (!isset(self::$methods[$class])) {
            self::$methods[$class] = [];
        }

        if (array_key_exists($name, self::$methods[$class])) {
            return self::$methods[$class][$name];
        }

        $refClass = self::getClass($objectOrClass);
        if (!$refClass->hasMethod($name)) {
            return null;
        }

        $method = $refClass->getMethod($name);
        self::$methods[$class][$name] = $method;

        return $method;
    }

    /**
     * Get all public methods for a class.
     *
     * Phase 8: Fixed to properly track when all methods have been loaded
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return array<string, ReflectionMethod>
     */
    public static function getMethods(object|string $objectOrClass): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        // Phase 8: Check if all methods have been loaded, not just if the array exists
        if (isset(self::$allMethodsLoaded[$class])) {
            return self::$methods[$class];
        }

        $refClass = self::getClass($objectOrClass);
        $methods = [];

        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[$method->getName()] = $method;
        }

        self::$methods[$class] = $methods;
        self::$allMethodsLoaded[$class] = true; // Phase 8: Mark that all methods have been loaded

        return $methods;
    }

    /**
     * Get all properties for a class.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return array<string, ReflectionProperty>
     */
    public static function getProperties(object|string $objectOrClass): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$properties[$class]) && !empty(self::$properties[$class])) {
            return array_filter(self::$properties[$class], fn($p) => null !== $p);
        }

        $refClass = self::getClass($objectOrClass);
        $properties = [];

        foreach ($refClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[$property->getName()] = $property;
        }

        self::$properties[$class] = $properties;

        return $properties;
    }

    /**
     * Get cached attributes for a property.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $propertyName Property name
     * @return array<string, object>
     */
    public static function getPropertyAttributes(object|string $objectOrClass, string $propertyName): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$propertyAttributes[$class][$propertyName])) {
            return self::$propertyAttributes[$class][$propertyName];
        }

        if (!isset(self::$propertyAttributes[$class])) {
            self::$propertyAttributes[$class] = [];
        }

        // Ensure $objectOrClass is an object for getProperty
        if (!is_object($objectOrClass)) {
            $objectOrClass = self::getClass($objectOrClass)->newInstanceWithoutConstructor();
        }

        $property = self::getProperty($objectOrClass, $propertyName);
        if (null === $property) {
            self::$propertyAttributes[$class][$propertyName] = [];

            return [];
        }

        $attributes = [];
        foreach ($property->getAttributes() as $attribute) {
            try {
                $attributes[$attribute->getName()] = $attribute->newInstance();
            } catch (\Error $e) {
                // Skip attributes that can't be instantiated (e.g., wrong target)
                continue;
            }
        }

        self::$propertyAttributes[$class][$propertyName] = $attributes;

        return $attributes;
    }

    /**
     * Get cached attributes for a method.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $methodName Method name
     * @return array<string, object>
     */
    public static function getMethodAttributes(object|string $objectOrClass, string $methodName): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$methodAttributes[$class][$methodName])) {
            return self::$methodAttributes[$class][$methodName];
        }

        if (!isset(self::$methodAttributes[$class])) {
            self::$methodAttributes[$class] = [];
        }

        $method = self::getMethod($objectOrClass, $methodName);
        if (null === $method) {
            self::$methodAttributes[$class][$methodName] = [];

            return [];
        }

        $attributes = [];
        foreach ($method->getAttributes() as $attribute) {
            try {
                $attributes[$attribute->getName()] = $attribute->newInstance();
            } catch (\Error $e) {
                // Skip attributes that can't be instantiated (e.g., wrong target)
                continue;
            }
        }

        self::$methodAttributes[$class][$methodName] = $attributes;

        return $attributes;
    }

    /**
     * Get cached attributes for a class.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return array<string, object>
     */
    public static function getClassAttributes(object|string $objectOrClass): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$classAttributes[$class])) {
            return self::$classAttributes[$class];
        }

        $refClass = self::getClass($objectOrClass);
        $attributes = [];

        foreach ($refClass->getAttributes() as $attribute) {
            try {
                $attributes[$attribute->getName()] = $attribute->newInstance();
            } catch (\Error $e) {
                // Skip attributes that can't be instantiated (e.g., wrong target)
                continue;
            }
        }

        self::$classAttributes[$class] = $attributes;

        return $attributes;
    }

    /**
     * Get cached property type information.
     *
     * Phase 3 Enhancement: Cache property types for faster type checking.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $propertyName Property name
     * @return array{type: ?string, isBuiltin: bool, allowsNull: bool}|null Null if property doesn't exist
     */
    public static function getPropertyType(object|string $objectOrClass, string $propertyName): ?array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$propertyTypes[$class][$propertyName])) {
            return self::$propertyTypes[$class][$propertyName];
        }

        // Ensure we have an object for getProperty
        $object = is_object($objectOrClass) ? $objectOrClass : self::getClass($objectOrClass)->newInstanceWithoutConstructor();

        $property = self::getProperty($object, $propertyName);
        if (null === $property) {
            return null;
        }

        $type = $property->getType();
        $typeInfo = [
            'type' => null,
            'isBuiltin' => false,
            'allowsNull' => true,
        ];

        if (null !== $type) {
            if ($type instanceof \ReflectionNamedType) {
                $typeInfo['type'] = $type->getName();
                $typeInfo['isBuiltin'] = $type->isBuiltin();
                $typeInfo['allowsNull'] = $type->allowsNull();
            } elseif ($type instanceof \ReflectionUnionType) {
                // For union types, store as string representation
                $typeInfo['type'] = (string)$type;
                $typeInfo['isBuiltin'] = false;
                $typeInfo['allowsNull'] = $type->allowsNull();
            }
        }

        if (!isset(self::$propertyTypes[$class])) {
            self::$propertyTypes[$class] = [];
        }
        self::$propertyTypes[$class][$propertyName] = $typeInfo;

        return $typeInfo;
    }

    /**
     * Get cached constructor parameter names.
     *
     * Phase 3 Enhancement: Cache constructor parameters for faster DTO instantiation.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return array<int, string> Parameter names in order
     */
    public static function getConstructorParameters(object|string $objectOrClass): array
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        if (isset(self::$constructorParameters[$class])) {
            return self::$constructorParameters[$class];
        }

        $refClass = self::getClass($objectOrClass);
        $constructor = $refClass->getConstructor();

        if (null === $constructor) {
            self::$constructorParameters[$class] = [];

            return [];
        }

        /** @var array<int, string> $parameters */
        $parameters = [];
        foreach ($constructor->getParameters() as $param) {
            $parameters[] = $param->getName();
        }

        self::$constructorParameters[$class] = $parameters;

        return $parameters;
    }

    /**
     * Clear all cached reflection data.
     *
     * Phase 3 & 8: Also clear property types, constructor parameters, and allMethodsLoaded tracker
     *
     * Useful for testing or when dealing with dynamic class loading.
     */
    public static function clear(): void
    {
        self::$classes = [];
        self::$properties = [];
        self::$methods = [];
        self::$allMethodsLoaded = []; // Phase 8
        self::$propertyAttributes = [];
        self::$methodAttributes = [];
        self::$classAttributes = [];
        self::$propertyTypes = []; // Phase 3
        self::$constructorParameters = []; // Phase 3
    }

    /**
     * Clear cached data for a specific class.
     *
     * Phase 3 & 8: Also clear property types, constructor parameters, and allMethodsLoaded tracker
     *
     * @param class-string $class Class name
     */
    public static function clearClass(string $class): void
    {
        unset(
            self::$classes[$class],
            self::$properties[$class],
            self::$methods[$class],
            self::$allMethodsLoaded[$class], // Phase 8
            self::$propertyAttributes[$class],
            self::$methodAttributes[$class],
            self::$classAttributes[$class],
            self::$propertyTypes[$class], // Phase 3
            self::$constructorParameters[$class] // Phase 3
        );
    }

    /**
     * Get cache statistics.
     *
     * Phase 3: Added property types and constructor parameters to statistics
     *
     * @return array{
     *     classes: int,
     *     properties: int,
     *     methods: int,
     *     propertyAttributes: int,
     *     methodAttributes: int,
     *     classAttributes: int,
     *     propertyTypes: int,
     *     constructorParameters: int,
     *     estimatedMemory: int
     * }
     */
    public static function getStats(): array
    {
        // Estimate memory usage (Reflection objects can't be serialized)
        $memory = count(self::$classes) * 1000 // ~1KB per ReflectionClass
            + array_sum(array_map('count', self::$properties)) * 500 // ~500B per ReflectionProperty
            + array_sum(array_map('count', self::$methods)) * 500 // ~500B per ReflectionMethod
            + array_sum(array_map(fn($c) => array_sum(array_map('count', $c)), self::$propertyAttributes)) * 200
            + array_sum(array_map(fn($c) => array_sum(array_map('count', $c)), self::$methodAttributes)) * 200
            + array_sum(array_map('count', self::$classAttributes)) * 200
            + array_sum(array_map('count', self::$propertyTypes)) * 100 // Phase 3
            + array_sum(array_map('count', self::$constructorParameters)) * 50; // Phase 3

        return [
            'classes' => count(self::$classes),
            'properties' => array_sum(array_map('count', self::$properties)),
            'methods' => array_sum(array_map('count', self::$methods)),
            'propertyAttributes' => array_sum(array_map(fn($c) => array_sum(array_map('count', $c)), self::$propertyAttributes)),
            'methodAttributes' => array_sum(array_map(fn($c) => array_sum(array_map('count', $c)), self::$methodAttributes)),
            'classAttributes' => array_sum(array_map('count', self::$classAttributes)),
            'propertyTypes' => array_sum(array_map('count', self::$propertyTypes)), // Phase 3
            'constructorParameters' => array_sum(array_map('count', self::$constructorParameters)), // Phase 3
            'estimatedMemory' => $memory,
        ];
    }
}

