<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

use ReflectionClass;
use ReflectionProperty;

/**
 * Helper for working with different entity/model types (Laravel Eloquent, Doctrine).
 */
class EntityHelper
{
    /**
     * Check if value is a Laravel Eloquent Model.
     */
    public static function isEloquentModel(mixed $value): bool
    {
        return class_exists(\Illuminate\Database\Eloquent\Model::class)
            && $value instanceof \Illuminate\Database\Eloquent\Model;
    }

    /**
     * Check if value is a Doctrine Entity.
     * Doctrine entities don't have a common base class, so we check for common patterns.
     */
    public static function isDoctrineEntity(mixed $value): bool
    {
        if (!is_object($value)) {
            return false;
        }

        // Check if class has Doctrine annotations/attributes
        $reflection = new ReflectionClass($value);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'Doctrine\\ORM\\Mapping\\Entity') ||
                str_contains($name, 'Doctrine\\ODM\\')) {
                return true;
            }
        }

        // Check for common Doctrine entity patterns
        // Entities typically have getId() method and private/protected properties
        if (method_exists($value, 'getId')) {
            $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);
            if (count($properties) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if value is any supported entity/model type.
     */
    public static function isEntity(mixed $value): bool
    {
        return self::isEloquentModel($value) || self::isDoctrineEntity($value);
    }

    /**
     * Convert entity/model to array.
     *
     * @return array<string, mixed>
     */
    public static function toArray(mixed $entity): array
    {
        if (self::isEloquentModel($entity)) {
            return $entity->toArray();
        }

        if (self::isDoctrineEntity($entity)) {
            return self::doctrineEntityToArray($entity);
        }

        return [];
    }

    /**
     * Get entity attributes/properties.
     *
     * @return array<string, mixed>
     */
    public static function getAttributes(mixed $entity): array
    {
        if (self::isEloquentModel($entity)) {
            return $entity->getAttributes();
        }

        if (self::isDoctrineEntity($entity)) {
            return self::doctrineEntityToArray($entity);
        }

        return [];
    }

    /**
     * Check if entity has an attribute/property.
     */
    public static function hasAttribute(mixed $entity, string|int $key): bool
    {
        if (self::isEloquentModel($entity)) {
            return $entity->offsetExists($key);
        }

        if (self::isDoctrineEntity($entity)) {
            $getter = 'get' . ucfirst((string)$key);
            if (method_exists($entity, $getter)) {
                return true;
            }

            $reflection = new ReflectionClass($entity);
            return $reflection->hasProperty((string)$key);
        }

        return false;
    }

    /**
     * Get attribute/property value from entity.
     */
    public static function getAttribute(mixed $entity, string $key): mixed
    {
        if (self::isEloquentModel($entity)) {
            return $entity->getAttribute($key);
        }

        if (self::isDoctrineEntity($entity)) {
            // Try getter method first
            $getter = 'get' . ucfirst($key);
            if (method_exists($entity, $getter)) {
                return $entity->$getter();
            }

            // Try direct property access
            $reflection = new ReflectionClass($entity);
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                return $property->getValue($entity);
            }
        }

        return null;
    }

    /**
     * Set attribute/property value on entity.
     */
    public static function setAttribute(mixed $entity, string $key, mixed $value): void
    {
        if (self::isEloquentModel($entity)) {
            $entity->setAttribute($key, $value);
            return;
        }

        if (self::isDoctrineEntity($entity)) {
            // Try setter method first
            $setter = 'set' . ucfirst($key);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
                return;
            }

            // Try direct property access
            $reflection = new ReflectionClass($entity);
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($entity, $value);
            }
        }
    }

    /**
     * Unset attribute/property from entity.
     */
    public static function unsetAttribute(mixed $entity, string|int $key): void
    {
        if (self::isEloquentModel($entity)) {
            $entity->offsetUnset($key);
            return;
        }

        if (self::isDoctrineEntity($entity)) {
            // Try setter with null
            $setter = 'set' . ucfirst((string)$key);
            if (method_exists($entity, $setter)) {
                $entity->$setter(null);
                return;
            }

            // Try direct property access
            $reflection = new ReflectionClass($entity);
            if ($reflection->hasProperty((string)$key)) {
                $property = $reflection->getProperty((string)$key);
                $property->setAccessible(true);
                $property->setValue($entity, null);
            }
        }
    }

    /**
     * Convert Doctrine entity to array using reflection.
     *
     * @return array<string, mixed>
     */
    private static function doctrineEntityToArray(object $entity): array
    {
        $result = [];
        $reflection = new ReflectionClass($entity);

        // Get all properties
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            $value = $property->getValue($entity);

            // Skip collections and relations for now (would cause recursion)
            if (CollectionHelper::isCollection($value)) {
                $value = CollectionHelper::toArray($value);
            } elseif (self::isEntity($value)) {
                // Skip nested entities to avoid recursion
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }
}

