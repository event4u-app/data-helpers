<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\HasEntity;
use event4u\DataHelpers\Support\EntityHelper;
use InvalidArgumentException;
use ReflectionClass;

if (!class_exists('Doctrine\ORM\EntityManagerInterface')) {
    trait SimpleDtoDoctrineTrait {}
} else {
    // No Doctrine-specific use statements needed - using FQN where needed
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
        // Get all entity attributes using EntityHelper
        $data = EntityHelper::toArray($entity);

        // Create Dto from array
        return static::fromArray($data);
    }

    /**
     * Convert the Dto to a Doctrine Entity instance.
     *
     * If no entity class is provided, it will try to resolve it from the #[HasEntity] attribute.
     *
     * @param class-string|null $entityClass The entity class name (optional if #[HasEntity] attribute is present)
     * @param bool $managed Whether the entity should be marked as managed (has ID)
     * @param bool $includeTimestamps Whether to include timestamp fields (created_at, updated_at, deleted_at) (default: false)
     * @return object The entity instance
     *
     * @throws InvalidArgumentException If no entity class is provided and no #[HasEntity] attribute is found
     * @throws InvalidArgumentException If the entity class does not exist
     * @throws BadMethodCallException If Doctrine ORM is not installed
     */
    public function toEntity(
        ?string $entityClass = null,
        bool $managed = false,
        bool $includeTimestamps = false
    ): object
    {
        // If no entity class provided, try to resolve from attribute
        if (null === $entityClass) {
            $entityClass = $this->resolveEntityClass();
        }

        // Check if entity class exists
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException(sprintf('Entity class %s does not exist', $entityClass));
        }

        // Create new entity instance
        $entity = new $entityClass();

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
    }
}
