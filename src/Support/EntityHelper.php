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
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $refClassCache = [];

    /** @var array<class-string, array<string, bool>> */
    private static array $propertyExistsCache = [];

    /**
     * Get cached ReflectionClass instance.
     *
     * @return ReflectionClass<object>
     */
    private static function getReflection(object $entity): ReflectionClass
    {
        $class = $entity::class;

        return self::$refClassCache[$class] ??= new ReflectionClass($entity);
    }

    /**
     * Check if property exists (cached).
     */
    public static function hasProperty(object $entity, string $property): bool
    {
        $class = $entity::class;

        if (!isset(self::$propertyExistsCache[$class])) {
            self::$propertyExistsCache[$class] = [];
        }

        if (!array_key_exists($property, self::$propertyExistsCache[$class])) {
            $reflection = self::getReflection($entity);
            self::$propertyExistsCache[$class][$property] = $reflection->hasProperty($property);
        }

        return self::$propertyExistsCache[$class][$property];
    }

    /** Check if value is a Laravel Eloquent Model. */
    public static function isEloquentModel(mixed $value): bool
    {
        return class_exists('\Illuminate\Database\Eloquent\Model')
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
        $reflection = self::getReflection($value);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'Doctrine\ORM\Mapping\Entity')
                || str_contains($name, 'Doctrine\ODM\\')) {
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

    /** Check if value is any supported entity/model type. */
    public static function isEntity(mixed $value): bool
    {
        return self::isEloquentModel($value) || self::isDoctrineEntity($value);
    }

    /**
     * Check if a property on an entity is a relation.
     *
     * For Eloquent: Checks if method exists and returns a Relation instance
     * For Doctrine: Checks for OneToMany, ManyToOne, OneToOne, ManyToMany annotations/attributes
     */
    public static function isRelation(object $entity, string $property): bool
    {
        if (self::isEloquentModel($entity)) {
            return self::isEloquentRelation($entity, $property);
        }

        if (self::isDoctrineEntity($entity)) {
            return self::isDoctrineRelation($entity, $property);
        }

        return false;
    }

    /**
     * Check if property is an Eloquent relation.
     */
    private static function isEloquentRelation(object $model, string $property): bool
    {
        // Check if method exists (relations are methods)
        if (!method_exists($model, $property)) {
            return false;
        }

        // Check if the method is defined in the model class (not inherited from Model base class)
        // This is a heuristic: relations are typically defined in the model class itself
        $reflection = self::getReflection($model);
        if (!$reflection->hasMethod($property)) {
            return false;
        }

        $method = $reflection->getMethod($property);

        // Relations are public methods defined in the model class (not in Eloquent\Model)
        if (!$method->isPublic()) {
            return false;
        }

        // Check if method is defined in the model class itself (not inherited from base Model)
        $declaringClass = $method->getDeclaringClass()->getName();
        if ($declaringClass === 'Illuminate\Database\Eloquent\Model') {
            return false;
        }

        // Check return type hint if available
        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            $typeName = $returnType->getName();
            if (str_contains($typeName, 'Relation')) {
                return true;
            }
        }

        // If no return type, we can't be sure it's a relation without calling it
        // To avoid false positives, return false
        return false;
    }

    /**
     * Check if property is a Doctrine relation.
     */
    private static function isDoctrineRelation(object $entity, string $property): bool
    {
        if (!self::hasProperty($entity, $property)) {
            return false;
        }

        $reflection = self::getReflection($entity);
        $reflectionProperty = $reflection->getProperty($property);
        $attributes = $reflectionProperty->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'OneToMany')
                || str_contains($name, 'ManyToOne')
                || str_contains($name, 'OneToOne')
                || str_contains($name, 'ManyToMany')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the related model class for a relation.
     *
     * @return class-string|null
     */
    public static function getRelationModelClass(object $entity, string $property): ?string
    {
        if (self::isEloquentModel($entity)) {
            return self::getEloquentRelationModelClass($entity, $property);
        }

        if (self::isDoctrineEntity($entity)) {
            return self::getDoctrineRelationModelClass($entity, $property);
        }

        return null;
    }

    /**
     * Get the related model class for an Eloquent relation.
     *
     * Tries in this order:
     * 1. PHPDoc @return annotation with generic type (fast, no DB needed)
     * 2. Instantiate relation and call getRelated() (needs DB, catches errors)
     * 3. Parse method body for Model::class (fragile but works without DB)
     *
     * @return class-string|null
     */
    private static function getEloquentRelationModelClass(object $model, string $property): ?string
    {
        if (!method_exists($model, $property)) {
            return null;
        }

        $reflection = self::getReflection($model);
        if (!$reflection->hasMethod($property)) {
            return null;
        }

        $method = $reflection->getMethod($property);

        // Strategy 1: Try to get from PHPDoc @return annotation (fastest, no DB)
        $docComment = $method->getDocComment();
        if ($docComment) {
            // Look for @return HasMany<ModelClass> or @return \Illuminate\Database\Eloquent\Relations\HasMany<ModelClass>
            if (preg_match('/@return\s+.*?<([^>]+)>/', $docComment, $matches)) {
                $className = trim($matches[1]);
                // Remove leading backslash if present
                $className = ltrim($className, '\\');

                // Try to resolve relative class name
                if (!class_exists($className)) {
                    // Try with model's namespace
                    $modelNamespace = $reflection->getNamespaceName();
                    $fullClassName = $modelNamespace . '\\' . $className;
                    if (class_exists($fullClassName)) {
                        return $fullClassName;
                    }
                }

                if (class_exists($className)) {
                    return $className;
                }
            }
        }

        // Strategy 2: Try to instantiate relation (needs DB, may fail)
        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            $typeName = $returnType->getName();

            // Check if it's a Relation type (HasMany, BelongsTo, etc.)
            if (class_exists($typeName) && str_contains($typeName, 'Relation')) {
                try {
                    $relation = $model->$property();
                    if (class_exists('\Illuminate\Database\Eloquent\Relations\Relation')
                        && $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        /** @phpstan-ignore method.nonObject */
                        return $relation->getRelated()::class;
                    }
                } catch (\Throwable) {
                    // Continue to method body parsing
                }
            }
        }

        // Strategy 3: Parse method body for Model::class (fragile but works without DB)
        try {
            $fileName = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if ($fileName && $startLine && $endLine) {
                $source = file_get_contents($fileName);
                if (false === $source) {
                    return null;
                }

                $lines = explode("\n", $source);
                $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

                // Look for patterns like:
                // - $this->hasMany(Department::class)
                // - $this->belongsTo(Company::class)
                // - $this->hasOne(Profile::class)
                if (preg_match(
                    '/(?:hasMany|belongsTo|hasOne|belongsToMany|morphMany|morphTo|hasManyThrough)\s*\(\s*([A-Za-z_\\\\]+)::class/',
                    $methodBody,
                    $matches
                )) {
                    $className = trim($matches[1]);
                    // Remove leading backslash if present
                    $className = ltrim($className, '\\');

                    // Try to resolve relative class name
                    if (!class_exists($className)) {
                        // Try with model's namespace
                        $modelNamespace = $reflection->getNamespaceName();
                        $fullClassName = $modelNamespace . '\\' . $className;
                        if (class_exists($fullClassName)) {
                            return $fullClassName;
                        }
                    }

                    if (class_exists($className)) {
                        return $className;
                    }
                }
            }
        } catch (\Throwable) {
            // Parsing failed, return null
        }

        // Cannot determine - return null
        return null;
    }

    /**
     * Get the related model class for a Doctrine relation.
     *
     * @return class-string|null
     */
    private static function getDoctrineRelationModelClass(object $entity, string $property): ?string
    {
        if (!self::hasProperty($entity, $property)) {
            return null;
        }

        $reflection = self::getReflection($entity);
        $reflectionProperty = $reflection->getProperty($property);
        $attributes = $reflectionProperty->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'OneToMany')
                || str_contains($name, 'ManyToOne')
                || str_contains($name, 'OneToOne')
                || str_contains($name, 'ManyToMany')) {
                // Get the targetEntity from attribute arguments
                $args = $attribute->getArguments();
                if (isset($args['targetEntity'])) {
                    return $args['targetEntity'];
                }
                if (isset($args[0])) {
                    return $args[0];
                }
            }
        }

        return null;
    }

    /**
     * Check if a relation is a "to-many" relation (returns collection).
     */
    public static function isToManyRelation(object $entity, string $property): bool
    {
        if (self::isEloquentModel($entity)) {
            return self::isEloquentToManyRelation($entity, $property);
        }

        if (self::isDoctrineEntity($entity)) {
            return self::isDoctrineToManyRelation($entity, $property);
        }

        return false;
    }

    /**
     * Check if Eloquent relation is to-many (HasMany, BelongsToMany, MorphMany, etc.).
     */
    private static function isEloquentToManyRelation(object $model, string $property): bool
    {
        if (!method_exists($model, $property)) {
            return false;
        }

        $reflection = self::getReflection($model);
        if (!$reflection->hasMethod($property)) {
            return false;
        }

        $method = $reflection->getMethod($property);

        // Try to get from PHPDoc @return annotation
        $docComment = $method->getDocComment();
        if ($docComment) {
            // Check for to-many relation types in @return
            if (preg_match('/@return\s+.*?(HasMany|BelongsToMany|MorphMany|HasManyThrough)/', $docComment)) {
                return true;
            }
        }

        // Check return type hint
        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            $typeName = $returnType->getName();
            if (str_contains($typeName, 'HasMany')
                || str_contains($typeName, 'BelongsToMany')
                || str_contains($typeName, 'MorphMany')
                || str_contains($typeName, 'HasManyThrough')) {
                return true;
            }
        }

        // If we can't determine from PHPDoc or return type, use naming convention
        // Relations ending with 's' are typically to-many (e.g., 'departments', 'users')
        return str_ends_with($property, 's');
    }

    /**
     * Check if Doctrine relation is to-many (OneToMany, ManyToMany).
     */
    private static function isDoctrineToManyRelation(object $entity, string $property): bool
    {
        if (!self::hasProperty($entity, $property)) {
            return false;
        }

        $reflection = self::getReflection($entity);
        $reflectionProperty = $reflection->getProperty($property);
        $attributes = $reflectionProperty->getAttributes();

        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (str_contains($name, 'OneToMany') || str_contains($name, 'ManyToMany')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert entity/model to array.
     *
     * @return array<string, mixed>
     */
    public static function toArray(mixed $entity): array
    {
        if (self::isEloquentModel($entity)) {
            /** @phpstan-ignore method.nonObject */
            return $entity->toArray();
        }

        if (self::isDoctrineEntity($entity) && is_object($entity)) {
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
            /** @phpstan-ignore method.nonObject */
            return $entity->getAttributes();
        }

        if (self::isDoctrineEntity($entity) && is_object($entity)) {
            return self::doctrineEntityToArray($entity);
        }

        return [];
    }

    /** Check if entity has an attribute/property. */
    public static function hasAttribute(mixed $entity, int|string $key): bool
    {
        if (self::isEloquentModel($entity)) {
            /** @phpstan-ignore method.nonObject */
            return $entity->offsetExists($key);
        }

        if (self::isDoctrineEntity($entity)) {
            $getter = 'get' . ucfirst((string)$key);
            if (is_object($entity) && method_exists($entity, $getter)) {
                return true;
            }

            if (is_object($entity)) {
                return self::hasProperty($entity, (string)$key);
            }
        }

        return false;
    }

    /** Get attribute/property value from entity. */
    public static function getAttribute(mixed $entity, string $key): mixed
    {
        if (self::isEloquentModel($entity)) {
            /** @phpstan-ignore method.nonObject */
            return $entity->getAttribute($key);
        }

        if (self::isDoctrineEntity($entity) && is_object($entity)) {
            // Try getter method first
            $getter = 'get' . ucfirst($key);
            if (method_exists($entity, $getter)) {
                return $entity->{$getter}();
            }

            // Try direct property access
            if (self::hasProperty($entity, $key)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($key);

                return $property->getValue($entity);
            }
        }

        // Support for DTOs with public properties
        if (is_object($entity) && self::hasProperty($entity, $key)) {
            $reflection = self::getReflection($entity);
            $property = $reflection->getProperty($key);

            return $property->getValue($entity);
        }

        return null;
    }

    /** Set attribute/property value on entity. */
    public static function setAttribute(mixed $entity, string $key, mixed $value): void
    {
        if (!is_object($entity)) {
            return;
        }

        if (self::isEloquentModel($entity)) {
            // Check if this is a relation and value is an array
            if (is_array($value) && self::isRelation($entity, $key)) {
                /** @var array<int, array<string, mixed>> $value */
                self::setEloquentRelation($entity, $key, $value);

                return;
            }

            /** @phpstan-ignore-next-line method.notFound */
            $entity->setAttribute($key, $value);

            return;
        }

        if (self::isDoctrineEntity($entity)) {
            // Check if this is a relation and value is an array
            if (is_array($value) && self::isRelation($entity, $key)) {
                /** @var array<int, array<string, mixed>> $value */
                self::setDoctrineRelation($entity, $key, $value);

                return;
            }

            // Try setter method first
            // Convert snake_case to camelCase for setter
            $camelCaseKey = str_replace('_', '', ucwords($key, '_'));
            $setter = 'set' . $camelCaseKey;
            if (method_exists($entity, $setter)) {
                // Cast value to match setter parameter type
                $castedValue = self::castValueForSetter($entity, $setter, $value);
                $entity->{$setter}($castedValue);

                return;
            }

            // Try direct property access
            if (self::hasProperty($entity, $key)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($key);
                $property->setValue($entity, $value);
            }

            return;
        }

        // Handle DTOs with typed array properties (e.g., /** @var array<int, DepartmentDto> */)
        // Check if key contains dot notation (e.g., "departments.0")
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $firstSegment = $segments[0];

            // Check if the first segment is a typed array property with numeric index
            // (e.g., "departments.0" but not "config.theme")
            if (self::hasProperty($entity, $firstSegment) && is_numeric($segments[1] ?? '')) {
                $arrayItemClass = self::getArrayItemClass($entity, $firstSegment);
                if (null !== $arrayItemClass && class_exists($arrayItemClass)) {
                    // Get or create the array
                    $reflection = self::getReflection($entity);
                    $property = $reflection->getProperty($firstSegment);
                    $currentValue = $property->getValue($entity);
                    $array = is_array($currentValue) ? $currentValue : [];

                    // Get the array index (e.g., "0" from "departments.0")
                    $index = (int) $segments[1];

                    // Create or get the DTO instance at this index
                    $existingItem = $array[$index] ?? null;
                    if (!is_object($existingItem)) {
                        $array[$index] = new $arrayItemClass();
                    }

                    // If there are more segments (e.g., "departments.0.name"), set the nested property
                    if (count($segments) > 2) {
                        $remainingPath = implode('.', array_slice($segments, 2));
                        self::setAttribute($array[$index], $remainingPath, $value);
                    } else {
                        // Set the entire object (shouldn't happen in normal mapping)
                        $array[$index] = $value;
                    }

                    // Set the updated array back
                    $property->setValue($entity, $array);

                    return;
                }
            }
        }

        // Handle DTOs with typed array properties when setting the entire array at once
        if (is_array($value) && self::hasProperty($entity, $key)) {
            $arrayItemClass = self::getArrayItemClass($entity, $key);
            if (null !== $arrayItemClass && class_exists($arrayItemClass)) {
                // Create instances of the typed class for each array item
                $typedArray = [];
                foreach ($value as $itemData) {
                    if (!is_array($itemData)) {
                        continue;
                    }

                    $typedItem = new $arrayItemClass();
                    foreach ($itemData as $field => $fieldValue) {
                        // Recursively set attributes (handles nested DTOs)
                        self::setAttribute($typedItem, $field, $fieldValue);
                    }
                    $typedArray[] = $typedItem;
                }

                // Set the typed array
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($key);
                $property->setValue($entity, $typedArray);

                return;
            }
        }

        // Fallback: handle nested property access for DTOs (e.g., "config.theme" or "profile.name")
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $firstSegment = $segments[0];

            if (self::hasProperty($entity, $firstSegment)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($firstSegment);
                $currentValue = $property->getValue($entity);

                // If current value is an object, recursively set the nested property
                if (is_object($currentValue)) {
                    $remainingPath = implode('.', array_slice($segments, 1));
                    self::setAttribute($currentValue, $remainingPath, $value);

                    return;
                }

                // If current value is not an array, initialize it
                if (!is_array($currentValue)) {
                    $currentValue = [];
                }

                // Set the nested value in the array
                $remainingPath = implode('.', array_slice($segments, 1));
                $currentValue = \event4u\DataHelpers\DataMutator::set($currentValue, $remainingPath, $value);

                // Set the updated array back
                $property->setValue($entity, $currentValue);

                return;
            }
        }

        // Fallback: set property directly if it exists
        if (self::hasProperty($entity, $key)) {
            $reflection = self::getReflection($entity);
            $property = $reflection->getProperty($key);
            $property->setValue($entity, $value);
        }
    }

    /** Unset attribute/property from entity. */
    public static function unsetAttribute(mixed $entity, int|string $key): void
    {
        if (self::isEloquentModel($entity)) {
            /** @phpstan-ignore method.nonObject */
            $entity->offsetUnset($key);

            return;
        }

        if (self::isDoctrineEntity($entity) && is_object($entity)) {
            // Try setter with null
            $setter = 'set' . ucfirst((string)$key);
            if (method_exists($entity, $setter)) {
                $entity->{$setter}(null);

                return;
            }

            // Try direct property access
            if (self::hasProperty($entity, (string)$key)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty((string)$key);
                $property->setValue($entity, null);
            }
        }
    }

    /**
     * Unset all attributes from entity (for wildcard unset).
     *
     * @param array<int, string> $segments
     */
    public static function unsetFromEntity(mixed $entity, array $segments): void
    {
        if (!self::isEntity($entity)) {
            return;
        }

        if (self::isEloquentModel($entity)) {
            self::unsetFromEloquentModel($entity, $segments);

            return;
        }

        // For Doctrine entities, unset via setAttribute with null
        foreach ($segments as $segment) {
            if ('*' !== $segment) {
                self::unsetAttribute($entity, $segment);
            }
        }
    }

    /**
     * Recursively unset from Eloquent models.
     *
     * @param array<int, string> $segments
     */
    private static function unsetFromEloquentModel(mixed $model, array $segments): void
    {
        if (!self::isEloquentModel($model)) {
            return;
        }

        $segment = array_shift($segments);
        if (null === $segment) {
            return;
        }

        if ('*' === $segment) {
            /** @phpstan-ignore method.nonObject */
            $attributes = $model->getAttributes();
            if ([] === $segments) {
                foreach (array_keys($attributes) as $key) {
                    /** @phpstan-ignore method.nonObject */
                    $model->offsetUnset($key);
                }

                return;
            }

            foreach ($attributes as $value) {
                if (is_array($value)) {
                    // Handle nested arrays
                    continue;
                }
                if (self::isEloquentModel($value)) {
                    self::unsetFromEloquentModel($value, $segments);
                } elseif (CollectionHelper::isLaravelCollection($value)) {
                    // Handle nested collections
                    continue;
                }
            }

            return;
        }

        if ([] === $segments) {
            /** @phpstan-ignore method.nonObject */
            $model->offsetUnset($segment);

            return;
        }

        /** @phpstan-ignore method.nonObject */
        $value = $model->getAttribute($segment);
        if (is_array($value)) {
            // Handle nested arrays
            return;
        }

        if (self::isEloquentModel($value)) {
            self::unsetFromEloquentModel($value, $segments);
        } elseif (CollectionHelper::isLaravelCollection($value)) {
            // Handle nested collections
            return;
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
        $reflection = self::getReflection($entity);

        // Get all properties
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
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

    /**
     * Set Eloquent relation by creating models and using setRelation().
     *
     * @param array<int, array<string, mixed>> $value Array of data for related models
     */
    private static function setEloquentRelation(object $model, string $relationName, array $value): void
    {
        $modelClass = self::getRelationModelClass($model, $relationName);
        if (null === $modelClass) {
            return;
        }

        $isToMany = self::isToManyRelation($model, $relationName);

        if ($isToMany) {
            // HasMany, BelongsToMany, etc. - create collection of models
            $models = [];
            foreach ($value as $itemData) {
                if (!is_array($itemData)) {
                    continue;
                }

                $relatedModel = new $modelClass();

                // Separate relation fields from regular fields
                $relationFields = [];
                $regularFields = [];

                foreach ($itemData as $field => $fieldValue) {
                    if (!is_string($field)) {
                        continue;
                    }

                    if (is_array($fieldValue) && self::isRelation($relatedModel, $field)) {
                        $relationFields[$field] = $fieldValue;
                    } else {
                        $regularFields[$field] = $fieldValue;
                    }
                }

                // Set regular fields first
                foreach ($regularFields as $field => $fieldValue) {
                    /** @phpstan-ignore-next-line method.notFound */
                    $relatedModel->setAttribute($field, $fieldValue);
                }

                // Set relation fields using setAttribute (which calls setEloquentRelation recursively)
                foreach ($relationFields as $field => $fieldValue) {
                    self::setAttribute($relatedModel, $field, $fieldValue);
                }

                $models[] = $relatedModel;
            }

            // Use setRelation to set the collection without database
            if (class_exists('\Illuminate\Support\Collection')) {
                /** @phpstan-ignore-next-line staticMethod.notFound */
                $collection = \Illuminate\Support\Collection::make($models);
                /** @phpstan-ignore-next-line method.notFound */
                $model->setRelation($relationName, $collection);
            }
        } else {
            // BelongsTo, HasOne - create single model
            if (empty($value)) {
                return;
            }

            // If value is array of arrays, take first item
            $itemData = isset($value[0]) && is_array($value[0]) ? $value[0] : $value;

            $relatedModel = new $modelClass();

            // Separate relation fields from regular fields
            $relationFields = [];
            $regularFields = [];

            foreach ($itemData as $field => $fieldValue) {
                if (!is_string($field)) {
                    continue;
                }

                if (is_array($fieldValue) && self::isRelation($relatedModel, $field)) {
                    $relationFields[$field] = $fieldValue;
                } else {
                    $regularFields[$field] = $fieldValue;
                }
            }

            // Set regular fields first
            foreach ($regularFields as $field => $fieldValue) {
                /** @phpstan-ignore-next-line method.notFound */
                $relatedModel->setAttribute($field, $fieldValue);
            }

            // Set relation fields using setAttribute (which calls setEloquentRelation recursively)
            foreach ($relationFields as $field => $fieldValue) {
                self::setAttribute($relatedModel, $field, $fieldValue);
            }

            /** @phpstan-ignore-next-line method.notFound */
            $model->setRelation($relationName, $relatedModel);
        }
    }

    /**
     * Set Doctrine relation by creating entities and setting the collection/reference.
     *
     * @param array<int, array<string, mixed>> $value Array of data for related entities
     */
    private static function setDoctrineRelation(object $entity, string $relationName, array $value): void
    {
        $entityClass = self::getRelationModelClass($entity, $relationName);
        if (null === $entityClass) {
            return;
        }

        $isToMany = self::isToManyRelation($entity, $relationName);

        if ($isToMany) {
            // OneToMany, ManyToMany - create collection of entities
            $entities = [];
            foreach ($value as $itemData) {
                if (!is_array($itemData)) {
                    continue;
                }

                $relatedEntity = new $entityClass();
                foreach ($itemData as $field => $fieldValue) {
                    // Convert snake_case to camelCase for setter
                    $camelCaseField = str_replace('_', '', ucwords($field, '_'));
                    $setter = 'set' . $camelCaseField;
                    if (method_exists($relatedEntity, $setter)) {
                        // Cast value to match setter parameter type
                        $castedValue = self::castValueForSetter($relatedEntity, $setter, $fieldValue);
                        $relatedEntity->{$setter}($castedValue);
                    }
                }
                $entities[] = $relatedEntity;
            }

            // Set the collection using setter or direct property access
            $setter = 'set' . ucfirst($relationName);
            if (method_exists($entity, $setter)) {
                // Try to create Doctrine ArrayCollection if available
                if (class_exists('\Doctrine\Common\Collections\ArrayCollection')) {
                    /** @phpstan-ignore new.nonObject */
                    $collection = new \Doctrine\Common\Collections\ArrayCollection($entities);
                    $entity->{$setter}($collection);
                } else {
                    $entity->{$setter}($entities);
                }
            } elseif (self::hasProperty($entity, $relationName)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($relationName);
                if (class_exists('\Doctrine\Common\Collections\ArrayCollection')) {
                    /** @phpstan-ignore new.nonObject */
                    $collection = new \Doctrine\Common\Collections\ArrayCollection($entities);
                    $property->setValue($entity, $collection);
                } else {
                    $property->setValue($entity, $entities);
                }
            }
        } else {
            // ManyToOne, OneToOne - create single entity
            if (empty($value)) {
                return;
            }

            // If value is array of arrays, take first item
            $itemData = isset($value[0]) && is_array($value[0]) ? $value[0] : $value;

            $relatedEntity = new $entityClass();
            foreach ($itemData as $field => $fieldValue) {
                if (!is_string($field)) {
                    continue;
                }

                $setter = 'set' . ucfirst($field);
                if (method_exists($relatedEntity, $setter)) {
                    $relatedEntity->{$setter}($fieldValue);
                }
            }

            // Set the entity using setter or direct property access
            $setter = 'set' . ucfirst($relationName);
            if (method_exists($entity, $setter)) {
                $entity->{$setter}($relatedEntity);
            } elseif (self::hasProperty($entity, $relationName)) {
                $reflection = self::getReflection($entity);
                $property = $reflection->getProperty($relationName);
                $property->setValue($entity, $relatedEntity);
            }
        }
    }

    /**
     * Cast value to match the parameter type of a setter method.
     */
    /**
     * Get the class name of array items from PHPDoc or property type.
     *
     * Supports formats:
     * - @var array<int, ClassName>
     * - @var array<ClassName>
     * - @var ClassName[]
     *
     * @param object $entity The entity/DTO instance
     * @param string $propertyName The property name
     * @return string|null The fully qualified class name or null if not found
     */
    private static function getArrayItemClass(object $entity, string $propertyName): ?string
    {
        try {
            $reflection = self::getReflection($entity);
            if (!$reflection->hasProperty($propertyName)) {
                return null;
            }

            $property = $reflection->getProperty($propertyName);

            // Try to get from PHPDoc
            $docComment = $property->getDocComment();
            if (false !== $docComment) {
                // Match: @var array<int, ClassName> or @var array<string, ClassName>
                if (preg_match('/@var\s+array<(?:int|string),\s*([^>]+)>/', $docComment, $matches)) {
                    $className = trim($matches[1]);

                    return self::resolveClassName($className, $reflection->getNamespaceName());
                }

                // Match: @var array<ClassName>
                if (preg_match('/@var\s+array<([^>]+)>/', $docComment, $matches)) {
                    $className = trim($matches[1]);

                    return self::resolveClassName($className, $reflection->getNamespaceName());
                }

                // Match: @var ClassName[]
                if (preg_match('/@var\s+([^\[\]]+)\[\]/', $docComment, $matches)) {
                    $className = trim($matches[1]);

                    return self::resolveClassName($className, $reflection->getNamespaceName());
                }
            }

            return null;
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Resolve class name to fully qualified class name.
     *
     * @param string $className The class name (can be relative or fully qualified)
     * @param string $namespace The namespace of the entity
     * @return string The fully qualified class name
     */
    private static function resolveClassName(string $className, string $namespace): string
    {
        // Already fully qualified
        if (str_starts_with($className, '\\')) {
            return ltrim($className, '\\');
        }

        // Try to resolve relative class name
        if (!str_contains($className, '\\')) {
            // Same namespace
            return $namespace . '\\' . $className;
        }

        return $className;
    }

    private static function castValueForSetter(object $entity, string $setter, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            $reflection = new \ReflectionMethod($entity, $setter);
            $parameters = $reflection->getParameters();

            if (empty($parameters)) {
                return $value;
            }

            $parameter = $parameters[0];
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                return $value;
            }

            $typeName = $type->getName();

            // Cast based on type
            return match ($typeName) {
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'array' => is_array($value) ? $value : [$value],
                default => $value,
            };
        } catch (\ReflectionException) {
            return $value;
        }
    }
}
