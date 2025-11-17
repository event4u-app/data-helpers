<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Traits;

use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Trait for converting plain PHP objects to DTOs.
 *
 * This trait provides a toDto() method that can be used in plain PHP classes.
 *
 * Usage:
 * ```php
 * #[HasDto(ProductDto::class)]
 * class Product
 * {
 *     use ObjectMappingTrait;
 *
 *     public int $id;
 *     public string $name;
 *     public float $price;
 * }
 *
 * $product = new Product();
 * $product->id = 1;
 * $product->name = 'Laptop';
 * $product->price = 999.99;
 * $dto = $product->toDto(); // Uses ProductDto::class from attribute
 * $dto = $product->toDto(AdminProductDto::class); // Override with specific DTO
 * ```
 *
 * @phpstan-ignore trait.unused (Trait is intended for external use in plain PHP classes)
 */
trait ObjectMappingTrait
{
    /**
     * Convert the object to a DTO instance.
     *
     * If no DTO class is provided, it will try to resolve it from the #[HasDto] attribute.
     *
     * @param class-string|null $dtoClass The DTO class (optional if #[HasDto] attribute is present)
     * @return object The DTO instance
     *
     * @throws InvalidArgumentException If no DTO class is provided and no #[HasDto] attribute is found
     * @throws InvalidArgumentException If the DTO class does not exist
     * @throws InvalidArgumentException If the DTO class does not have a fromArray() method
     */
    public function toDto(?string $dtoClass = null): object
    {
        // If no DTO class provided, try to resolve from attribute
        if (null === $dtoClass) {
            $dtoClass = $this->resolveDtoClass();
        }

        // Check if DTO class exists
        if (!class_exists($dtoClass)) {
            throw new InvalidArgumentException(sprintf('DTO class %s does not exist', $dtoClass));
        }

        // Get data from object
        $data = $this->extractData();

        // Check if DTO has fromArray method
        if (!method_exists($dtoClass, 'fromArray')) {
            throw new InvalidArgumentException(
                sprintf('DTO class %s must have a fromArray() method', $dtoClass)
            );
        }

        // Create DTO from array
        return $dtoClass::fromArray($data);
    }

    /**
     * Resolve the DTO class from the #[HasDto] attribute.
     *
     * @return class-string
     *
     * @throws InvalidArgumentException If no #[HasDto] attribute is found
     */
    private function resolveDtoClass(): string
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(HasDto::class);

        if ([] === $attributes) {
            throw new InvalidArgumentException(
                sprintf(
                    'No DTO class provided and no #[HasDto] attribute found on %s. ' .
                    'Either provide a DTO class as parameter or add #[HasDto(YourDto::class)] attribute to the class.',
                    $reflection->getName()
                )
            );
        }

        /** @var HasDto $hasDto */
        $hasDto = $attributes[0]->newInstance();

        return $hasDto->dtoClass;
    }

    /**
     * Extract data from plain PHP object.
     *
     * Converts collections to DataCollection instances for better handling.
     *
     * @return array<string, mixed>
     */
    private function extractData(): array
    {
        $reflection = new ReflectionClass($this);
        $data = [];

        // Get all public properties
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $data[$propertyName] = $reflectionProperty->getValue($this);
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
                        $data[$propertyName] = $reflectionMethod->invoke($this);
                    } catch (Throwable) {
                        // Skip if getter throws exception
                    }
                }
            }
        }

        // Convert array collections to DataCollection
        return $this->convertArrayCollectionsToDataCollection($data);
    }

    /**
     * Convert array collections to DataCollection instances.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertArrayCollectionsToDataCollection(array $data): array
    {
        foreach ($data as $key => $value) {
            // Check if value is an array of arrays (collection)
            if (is_array($value) && [] !== $value && $this->isCollection($value)) {
                $data[$key] = DataCollection::make($value);
            }
        }

        return $data;
    }

    /**
     * Check if array is a collection (array of arrays or objects).
     *
     * @param array<mixed> $value
     */
    private function isCollection(array $value): bool
    {
        if ([] === $value) {
            return false;
        }

        // Check if first element is an array or object
        $first = reset($value);
        return is_array($first) || is_object($first);
    }
}
