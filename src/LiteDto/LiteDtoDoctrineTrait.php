<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\LiteDto\Attributes\HasEntity;
use event4u\DataHelpers\LiteDto\Attributes\Map;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\Support\Traits\BaseDoctrineTrait;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Trait providing Doctrine Entity integration for LiteDtos.
 *
 * This trait is optional and only used when Doctrine ORM is available.
 *
 * @phpstan-ignore trait.unused (Optional trait, only used when Doctrine is installed)
 */
trait LiteDtoDoctrineTrait
{
    use BaseDoctrineTrait;

    /**
     * Create a Dto instance from a Doctrine Entity.
     *
     * @param object $entity Doctrine entity instance
     * @throws BadMethodCallException If Doctrine ORM is not installed
     */
    public static function fromEntity(object $entity): static
    {
        static::ensureDoctrineIsInstalled();

        // Get all entity attributes using EntityHelper
        $data = static::entityToArray($entity);

        // Create Dto from array
        return static::from($data);
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
        static::ensureDoctrineIsInstalled();

        // If no entity class provided, try to resolve from attribute
        if (null === $entityClass) {
            $entityClass = $this->resolveEntityClass();
        }

        // Validate entity class
        static::validateEntityClass($entityClass);

        // Try to load existing entity from database if EntityManager is provided
        $entity = null;
        $entityExistsInDb = false;
        if (null !== $entityManager && $entityManager instanceof EntityManagerInterface) {
            $identifierInfo = $this->getIdentifierFieldsAndValues($entityManager, $entityClass);

            // Load entity if all identifiers are present
            if ($identifierInfo['allPresent'] && [] !== $identifierInfo['values']) {
                // For composite keys, pass array; for single key, pass value directly
                $identifier = count($identifierInfo['values']) === 1
                    ? reset($identifierInfo['values'])
                    : $identifierInfo['values'];
                $entity = static::loadExistingEntity($entityManager, $entityClass, $identifier);
                $entityExistsInDb = null !== $entity;
            }
        }

        // If no entity found, create new instance
        if (null === $entity) {
            $entity = new $entityClass();
        }

        // Get Dto data and filter timestamps
        // If entity exists in DB, only use explicitly set properties to avoid overwriting DB values with defaults
        if ($entityExistsInDb) {
            $data = $this->toArrayOnlyExplicitlySet();
        } else {
            $data = $this->toArray();
        }
        $data = static::filterDoctrineTimestamps($data, $includeTimestamps);

        // Fill entity with Dto data
        $this->setEntityProperties($entity, $data);

        return $entity;
    }

    /**
     * Resolve the Entity class from the #[HasEntity] attribute.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasEntity] attribute is found
     */
    protected function resolveEntityClass(): string
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
    protected function findDoctrinePrimaryKeyValue(string $primaryKeyName): mixed
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
