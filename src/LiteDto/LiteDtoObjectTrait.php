<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use event4u\DataHelpers\LiteDto\Attributes\HasObject;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Trait providing plain PHP object integration for LiteDtos.
 *
 * This trait provides methods to convert between Dtos and plain PHP objects.
 *
 * Usage:
 * ```php
 * class ProductDto extends LiteDto
 * {
 *     use LiteDtoObjectTrait;
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
trait LiteDtoObjectTrait
{
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

        return static::from($data);
    }

    /**
     * Convert the Dto to a plain PHP object instance.
     *
     * If no object class is provided, it will try to resolve it from the #[HasObject] attribute.
     *
     * @param class-string|null $objectClass The object class (optional if #[HasObject] attribute is present)
     * @return object The object instance
     *
     * @throws InvalidArgumentException If no object class is provided and no #[HasObject] attribute is found
     * @throws InvalidArgumentException If the object class does not exist
     */
    public function toObject(?string $objectClass = null): object
    {
        // If no object class provided, try to resolve from attribute
        if (null === $objectClass) {
            $objectClass = $this->resolveObjectClass();
        }

        // Check if object class exists
        if (!class_exists($objectClass)) {
            throw new InvalidArgumentException(sprintf('Object class %s does not exist', $objectClass));
        }

        // Create new instance
        $reflection = new ReflectionClass($objectClass);
        $object = $reflection->newInstanceWithoutConstructor();

        // Get DTO data
        $data = $this->toArray();

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
     * Resolve the object class from the #[HasObject] attribute.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasObject] attribute is found
     */
    private function resolveObjectClass(): string
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
