<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\Support\EntityHelper;
use InvalidArgumentException;

/**
 * Trait providing Doctrine Entity integration for SimpleDTOs.
 *
 * This trait is optional and only used when Doctrine ORM is available.
 *
 * @phpstan-ignore trait.unused (Optional trait, only used when Doctrine is installed)
 */
trait SimpleDTODoctrineTrait
{
    /**
     * Create a DTO instance from a Doctrine Entity.
     *
     * @param object $entity Doctrine entity instance
     */
    public static function fromEntity(object $entity): static
    {
        // Get all entity attributes using EntityHelper
        $data = EntityHelper::toArray($entity);

        // Create DTO from array
        return static::fromArray($data);
    }

    /**
     * Convert the DTO to a Doctrine Entity instance.
     *
     * @param class-string $entityClass The entity class name
     * @param bool $managed Whether the entity should be marked as managed (has ID)
     * @return object The entity instance
     */
    public function toEntity(string $entityClass, bool $managed = false): object
    {
        // Check if entity class exists
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('Entity class %s does not exist', $entityClass));
        }

        // Create new entity instance
        $entity = new $entityClass();

        // Get DTO data
        $data = $this->toArray();

        // Fill entity with DTO data using EntityHelper
        foreach ($data as $key => $value) {
            EntityHelper::setAttribute($entity, $key, $value);
        }

        return $entity;
    }
}
