<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Traits;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Base trait providing shared plain PHP object integration logic.
 *
 * This trait contains all the common logic shared between SimpleDtoObjectTrait
 * and LiteDtoObjectTrait to eliminate code duplication.
 *
 * @internal This trait is not meant to be used directly. Use SimpleDtoObjectTrait or LiteDtoObjectTrait instead.
 * @phpstan-ignore trait.unused (Base trait, used by SimpleDtoObjectTrait and LiteDtoObjectTrait)
 */
trait BaseObjectTrait
{
    /**
     * Extract data from a plain PHP object.
     *
     * @param object $object The plain PHP object
     * @return array<string, mixed> The extracted data
     */
    protected static function extractObjectData(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $data = [];

        // Get all public properties
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $data[$propertyName] = $reflectionProperty->getValue($object);
        }

        // Also try to get properties via getter methods
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();

            // Check for getter methods (get*, is*, has*)
            if (preg_match('/^(get|is|has)([A-Z].*)$/', $methodName, $matches)) {
                $propertyName = lcfirst($matches[2]);

                // Only add if not already present and method has no parameters
                if (!isset($data[$propertyName]) && 0 === $reflectionMethod->getNumberOfParameters()) {
                    try {
                        $data[$propertyName] = $reflectionMethod->invoke($object);
                    } catch (Throwable) {
                        // Skip if getter throws exception
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Validate that an object class exists.
     *
     * @param class-string $objectClass The object class to validate
     * @throws InvalidArgumentException If the object class does not exist
     */
    protected static function validateObjectClass(string $objectClass): void
    {
        if (!class_exists($objectClass)) {
            throw new InvalidArgumentException(sprintf('Object class %s does not exist', $objectClass));
        }
    }

    /**
     * Check if object class is an Eloquent Model.
     *
     * @param class-string $objectClass The object class to check
     * @return bool True if the object class is an Eloquent Model
     */
    protected static function isEloquentModel(string $objectClass): bool
    {
        return class_exists('Illuminate\Database\Eloquent\Model') &&
            is_subclass_of($objectClass, 'Illuminate\Database\Eloquent\Model');
    }

    /**
     * Check if object class is a Doctrine Entity.
     *
     * @param class-string $objectClass The object class to check
     * @return bool True if the object class is a Doctrine Entity
     */
    protected static function isDoctrineEntity(string $objectClass): bool
    {
        return class_exists('Doctrine\ORM\EntityManagerInterface');
    }

    /**
     * Filter out timestamp fields from data.
     *
     * @param array<string, mixed> $data The data to filter
     * @param bool $includeTimestamps Whether to include timestamps
     * @return array<string, mixed> The filtered data
     */
    protected static function filterObjectTimestamps(array $data, bool $includeTimestamps): array
    {
        if ($includeTimestamps) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key): bool => !in_array($key, ['created_at', 'updated_at', 'deleted_at'], true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Create a plain PHP object instance and set properties.
     *
     * @param class-string $objectClass The object class
     * @param array<string, mixed> $data The data to set
     * @return object The created object
     */
    protected static function createPlainObject(string $objectClass, array $data): object
    {
        // Special handling for stdClass - just cast array to object
        if ('stdClass' === $objectClass) {
            return (object)$data;
        }

        // Create new instance for plain PHP object
        $reflection = new ReflectionClass($objectClass);
        $object = $reflection->newInstanceWithoutConstructor();

        // Set properties on object
        foreach ($data as $key => $value) {
            // Try to set via public property
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);

                if ($property->isPublic()) {
                    $property->setValue($object, $value);
                    continue;
                }
            }

            // Try to set via setter method
            $setterMethod = 'set' . ucfirst($key);
            if ($reflection->hasMethod($setterMethod)) {
                $method = $reflection->getMethod($setterMethod);

                if ($method->isPublic() && 1 === $method->getNumberOfParameters()) {
                    $method->invoke($object, $value);
                }
            }
        }

        return $object;
    }

    /**
     * Extract EntityManager from additional parameters.
     *
     * @param array<int|string, mixed> $additionalParams The additional parameters
     * @return EntityManagerInterface|null The EntityManager or null if not found
     */
    protected static function extractEntityManager(array $additionalParams): ?EntityManagerInterface
    {
        foreach ($additionalParams as $param) {
            if ($param instanceof EntityManagerInterface) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Resolve the object class from the #[HasObject] attribute.
     *
     * This method must be implemented by the concrete trait (SimpleDtoObjectTrait or LiteDtoObjectTrait)
     * because the attribute class is different for each DTO type.
     *
     * @return class-string
     * @throws InvalidArgumentException If no #[HasObject] attribute is found
     */
    abstract protected function resolveObjectClass(): string;
}

