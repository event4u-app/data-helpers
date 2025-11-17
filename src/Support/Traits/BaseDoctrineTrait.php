<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Traits;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\Support\EntityHelper;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Base trait providing shared Doctrine Entity integration logic.
 *
 * This trait contains all the common logic shared between SimpleDtoDoctrineTrait
 * and LiteDtoDoctrineTrait to eliminate code duplication.
 *
 * @internal This trait is not meant to be used directly. Use SimpleDtoDoctrineTrait or LiteDtoDoctrineTrait instead.
 * @phpstan-ignore trait.unused (Base trait, used by SimpleDtoDoctrineTrait and LiteDtoDoctrineTrait)
 */
trait BaseDoctrineTrait
{
    /**
     * Check if Doctrine ORM is installed.
     *
     * @throws BadMethodCallException If Doctrine ORM is not installed
     */
    protected static function ensureDoctrineIsInstalled(): void
    {
        if (!interface_exists('Doctrine\ORM\EntityManagerInterface')) {
            throw new BadMethodCallException('Doctrine ORM is not installed. Please install doctrine/orm package.');
        }
    }

    /**
     * Validate that the entity class exists.
     *
     * @param class-string $entityClass The entity class to validate
     *
     * @throws InvalidArgumentException If the entity class does not exist
     */
    protected static function validateEntityClass(string $entityClass): void
    {
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('Entity class %s does not exist', $entityClass));
        }
    }

    /**
     * Convert an entity to an array using EntityHelper.
     *
     * @param object $entity The entity to convert
     *
     * @return array<string, mixed> The entity data as array
     */
    protected static function entityToArray(object $entity): array
    {
        return EntityHelper::toArray($entity);
    }

    /**
     * Filter out timestamp fields from data array.
     *
     * @param array<string, mixed> $data The data array
     * @param bool $includeTimestamps Whether to include timestamp fields
     *
     * @return array<string, mixed> The filtered data array
     */
    protected static function filterDoctrineTimestamps(array $data, bool $includeTimestamps): array
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
     * Try to load an existing entity from the database using EntityManager.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param class-string $entityClass The entity class
     * @param mixed $identifierValues The identifier values (single value or array for composite keys)
     *
     * @return object|null The loaded entity or null if not found
     */
    protected static function loadExistingEntity(
        EntityManagerInterface $entityManager,
        string $entityClass,
        mixed $identifierValues
    ): ?object {
        if ([] === $identifierValues || null === $identifierValues) {
            return null;
        }

        try {
            return $entityManager->find($entityClass, $identifierValues);
        } catch (\Throwable) {
            // If database query fails, return null
            return null;
        }
    }

    /**
     * Get identifier fields and values from DTO.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param class-string $entityClass The entity class
     *
     * @return array{fields: array<string>, values: array<string, mixed>, allPresent: bool}
     */
    protected function getIdentifierFieldsAndValues(
        EntityManagerInterface $entityManager,
        string $entityClass
    ): array {
        $metadata = $entityManager->getClassMetadata($entityClass);
        $identifierFields = $metadata->getIdentifier();

        $identifierValues = [];
        $allIdentifiersPresent = true;

        foreach ($identifierFields as $identifierField) {
            $value = $this->findDoctrinePrimaryKeyValue($identifierField);
            if (null !== $value) {
                $identifierValues[$identifierField] = $value;
            } else {
                $allIdentifiersPresent = false;
            }
        }

        return [
            'fields' => $identifierFields,
            'values' => $identifierValues,
            'allPresent' => $allIdentifiersPresent,
        ];
    }

    /**
     * Set properties on an entity from DTO data.
     *
     * @param object $entity The entity to set properties on
     * @param array<string, mixed> $data The data to set
     */
    protected function setEntityProperties(object $entity, array $data): void
    {
        foreach ($data as $key => $value) {
            EntityHelper::setAttribute($entity, $key, $value);
        }
    }

    /**
     * Resolve the Entity class from the #[HasEntity] attribute.
     *
     * This method must be implemented by the concrete trait (SimpleDtoDoctrineTrait or LiteDtoDoctrineTrait)
     * because the attribute class is different for each DTO type.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasEntity] attribute is found
     */
    abstract protected function resolveEntityClass(): string;

    /**
     * Find the primary key value in the DTO, considering mapping attributes.
     *
     * This method must be implemented by the concrete trait (SimpleDtoDoctrineTrait or LiteDtoDoctrineTrait)
     * because the logic is different for each DTO type.
     *
     * @param string $primaryKeyName The primary key name
     *
     * @return mixed The primary key value or null if not found
     */
    abstract protected function findDoctrinePrimaryKeyValue(string $primaryKeyName): mixed;
}

