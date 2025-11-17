<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\HasObject;
use event4u\DataHelpers\Support\Traits\BaseObjectTrait;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Trait providing plain PHP object integration for SimpleDtos.
 *
 * This trait provides methods to convert between Dtos and plain PHP objects.
 *
 * Usage:
 * ```php
 * class ProductDto extends SimpleDto
 * {
 *     use SimpleDtoObjectTrait;
 *
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly float $price,
 *     ) {}
 * }
 *
 * // Create Dto from object
 * $product = new Product();
 * $product->name = 'Laptop';
 * $product->price = 999.99;
 * $dto = ProductDto::fromObject($product);
 *
 * // Create object from Dto
 * $object = $dto->toObject(Product::class);
 * ```
 *
 * @phpstan-ignore trait.unused (Optional trait for plain PHP object integration)
 */
trait SimpleDtoObjectTrait
{
    use BaseObjectTrait;

    /**
     * Create a Dto instance from a plain PHP object.
     *
     * Extracts all public properties from the object and creates a Dto instance.
     *
     * @param object $object The plain PHP object
     * @return static The Dto instance
     */
    public static function fromObject(object $object): static
    {
        $data = static::extractObjectData($object);
        return static::fromArray($data);
    }

    /**
     * Convert the Dto to a plain PHP object instance.
     *
     * If the object class is an Eloquent Model or Doctrine Entity, delegates to toModel() or toEntity().
     * Otherwise creates a plain PHP object instance.
     *
     * If no object class is provided, creates a stdClass object or tries to resolve from #[HasObject] attribute.
     *
     * @param class-string|null $objectClass The object class (optional - defaults to stdClass if no #[HasObject] attribute)
     * @param bool $includeTimestamps Whether to include timestamp fields (created_at, updated_at, deleted_at) (default: false)
     * @param mixed ...$additionalParams Additional parameters to pass to toModel() or toEntity() (e.g., EntityManager for Doctrine)
     * @return object The object instance
     *
     * @throws InvalidArgumentException If the object class does not exist
     */
    public function toObject(
        ?string $objectClass = null,
        bool $includeTimestamps = false,
        mixed ...$additionalParams
    ): object
    {
        // If no object class provided, try to resolve from attribute or use stdClass
        if (null === $objectClass) {
            try {
                $objectClass = $this->resolveObjectClass();
            } catch (InvalidArgumentException) {
                // No attribute found - use stdClass
                $objectClass = 'stdClass';
            }
        }

        // Validate object class
        static::validateObjectClass($objectClass);

        // Check if object class is an Eloquent Model and toModel() method exists
        if (static::isEloquentModel($objectClass) &&
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            method_exists($this, 'toModel')) {
            return $this->toModel($objectClass, false, null, $includeTimestamps);
        }

        // Check if object class is a Doctrine Entity and toEntity() method exists
        if (static::isDoctrineEntity($objectClass) &&
            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
            method_exists($this, 'toEntity')) {
            $entityManager = static::extractEntityManager($additionalParams);
            return $this->toEntity($objectClass, false, $includeTimestamps, $entityManager);
        }

        // Get DTO data and filter timestamps
        $data = $this->toArray();
        $data = static::filterObjectTimestamps($data, $includeTimestamps);

        // Create plain PHP object
        return static::createPlainObject($objectClass, $data);
    }

    /**
     * Resolve the object class from the #[HasObject] attribute.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasObject] attribute is found
     */
    protected function resolveObjectClass(): string
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(HasObject::class);

        if ([] === $attributes) {
            throw new InvalidArgumentException(
                sprintf(
                    'No object class provided and no #[HasObject] attribute found on %s. ' .
                    'Either provide an object class as parameter or add #[HasObject(YourClass::class)] attribute to the DTO.',
                    $reflection->getName()
                )
            );
        }

        /** @var HasObject $hasObject */
        $hasObject = $attributes[0]->newInstance();

        return $hasObject->objectClass;
    }
}
