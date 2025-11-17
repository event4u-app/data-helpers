<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\SimpleDto\Attributes\HasEntity;
use event4u\DataHelpers\SimpleDto\Attributes\Map;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\Support\EntityHelper;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Trait providing Doctrine Entity integration for SimpleDtos.
 *
 * This trait is optional and only used when Doctrine ORM is available.
 *
 * @phpstan-ignore trait.unused (Optional trait, only used when Doctrine is installed)
 */
trait SimpleDtoDoctrineTrait
{
    /**
     * Create a Dto instance from a Doctrine Entity.
     *
     * @param object $entity Doctrine entity instance
     * @throws BadMethodCallException If Doctrine ORM is not installed
     */
    public static function fromEntity(object $entity): static
    {
        if (!interface_exists('Doctrine\ORM\EntityManagerInterface')) {
            throw new BadMethodCallException('Doctrine ORM is not installed. Please install doctrine/orm package.');
        }

        // Get all entity attributes using EntityHelper
        $data = EntityHelper::toArray($entity);

        // Create Dto from array
        return static::fromArray($data);
    }

    /**
     * Convert the Dto to a Doctrine Entity instance.
     *
     * If the DTO contains primary key values and an EntityManager is provided,
     * loads the existing entity from the database and updates it with DTO data.
     * Otherwise creates a new entity instance.
     *
     * If no entity class is provided, it will try to resolve it from the #[HasEntity] attribute.
     *
     * @param class-string|null $entityClass The entity class name (optional if #[HasEntity] attribute is present)
     * @param bool $managed Whether the entity should be marked as managed (has ID)
     * @param bool $includeTimestamps Whether to include timestamp fields (created_at, updated_at, deleted_at) (default: false)
     * @param EntityManagerInterface|null $entityManager Optional EntityManager to load existing entity from database
     * @return object The entity instance
     *
     * @throws InvalidArgumentException If no entity class is provided and no #[HasEntity] attribute is found
     * @throws InvalidArgumentException If the entity class does not exist
     * @throws BadMethodCallException If Doctrine ORM is not installed
     */
    public function toEntity(
        ?string $entityClass = null,
        bool $managed = false,
        bool $includeTimestamps = false,
        ?object $entityManager = null
    ): object
    {
        if (!interface_exists('Doctrine\ORM\EntityManagerInterface')) {
            throw new BadMethodCallException('Doctrine ORM is not installed. Please install doctrine/orm package.');
        }

        // If no entity class provided, try to resolve from attribute
        if (null === $entityClass) {
            $entityClass = $this->resolveEntityClass();
        }

        // Check if entity class exists
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('Entity class %s does not exist', $entityClass));
        }

        // Try to load existing entity from database if EntityManager is provided
        $entity = null;
        if (null !== $entityManager) {
            /** @var EntityManagerInterface $entityManager */
            $metadata = $entityManager->getClassMetadata($entityClass);
            $identifierFields = $metadata->getIdentifier();

            // Check if all identifier fields are present in DTO (considering mapping attributes)
            $identifierValues = [];
            $allIdentifiersPresent = true;
            foreach ($identifierFields as $identifierField) {
                $value = $this->findDoctrinePrimaryKeyValue($identifierField);
                if (null !== $value) {
                    $identifierValues[$identifierField] = $value;
                } else {
                    $allIdentifiersPresent = false;
                    break;
                }
            }

            // Load entity if all identifiers are present
            if ($allIdentifiersPresent && [] !== $identifierValues) {
                try {
                    // For composite keys, pass array; for single key, pass value directly
                    $identifier = count($identifierValues) === 1 ? reset($identifierValues) : $identifierValues;
                    $entity = $entityManager->find($entityClass, $identifier);
                } catch (Throwable) {
                    // If database query fails (e.g., no connection, table doesn't exist), create new instance
                    $entity = null;
                }
            }
        }

        // If no entity found, create new instance
        if (null === $entity) {
            $entity = new $entityClass();
        }

        // Get Dto data
        $data = $this->toArray();

        // Filter out timestamp fields if not included
        if (!$includeTimestamps) {
            $data = array_filter(
                $data,
                fn($key): bool => !in_array($key, ['created_at', 'updated_at', 'deleted_at'], true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Fill entity with Dto data using EntityHelper
        foreach ($data as $key => $value) {
            EntityHelper::setAttribute($entity, $key, $value);
        }

        return $entity;
    }

    /**
     * Resolve the Entity class from the #[HasEntity] attribute.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasEntity] attribute is found
     */
    private function resolveEntityClass(): string
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(HasEntity::class);

        if ([] === $attributes) {
            throw new InvalidArgumentException(
                sprintf(
                    'No Entity class provided and no #[HasEntity] attribute found on %s. ' .
                    'Either provide an Entity class as parameter or add #[HasEntity(YourEntity::class)] attribute to the DTO class.',
                    $reflection->getName()
                )
            );
        }

        /** @var HasEntity $hasEntity */
        $hasEntity = $attributes[0]->newInstance();

        return $hasEntity->entityClass;
    }

    /**
     * Find the primary key value in the DTO, considering mapping attributes.
     *
     * This method searches for the primary key value by:
     * 1. Checking if a DTO property maps TO the primary key name (via #[MapTo] or #[Map])
     * 2. Checking if a DTO property has the same name as the primary key
     * 3. Checking the toArray() output for the primary key
     *
     * @param string $primaryKeyName The primary key field name from the Entity
     * @return mixed The primary key value, or null if not found
     */
    private function findDoctrinePrimaryKeyValue(string $primaryKeyName): mixed
    {
        $reflection = new ReflectionClass($this);

        // First, check all properties to see if any map TO the primary key name
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();

            // Check #[MapTo] attribute
            $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
            if (!empty($mapToAttrs)) {
                /** @var MapTo $mapTo */
                $mapTo = $mapToAttrs[0]->newInstance();
                if ($mapTo->target === $primaryKeyName) {
                    return $reflectionProperty->getValue($this);
                }
            }

            // Check #[Map] attribute
            $mapAttrs = $reflectionProperty->getAttributes(Map::class);
            if (!empty($mapAttrs)) {
                /** @var Map $map */
                $map = $mapAttrs[0]->newInstance();
                $mapKey = is_array($map->key) ? $map->key[0] : $map->key;
                if ($mapKey === $primaryKeyName) {
                    return $reflectionProperty->getValue($this);
                }
            }

            // Check if property name matches primary key name
            if ($propertyName === $primaryKeyName) {
                return $reflectionProperty->getValue($this);
            }
        }

        // Fallback: check toArray() output
        $data = $this->toArray();
        return $data[$primaryKeyName] ?? null;
    }
}
