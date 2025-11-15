<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Traits;

use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\Support\EntityHelper;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Trait for converting Models/Entities to DTOs.
 *
 * This trait provides a toDto() method that can be used in both
 * Laravel Eloquent Models and Symfony Doctrine Entities.
 *
 * Usage with Laravel Model:
 * ```php
 * #[HasDto(UserDto::class)]
 * class User extends Model
 * {
 *     use DtoMappingTrait;
 *
 *     protected $fillable = ['name', 'email'];
 * }
 *
 * $user = User::find(1);
 * $dto = $user->toDto(); // Uses UserDto::class from attribute
 * $dto = $user->toDto(AdminDto::class); // Override with specific DTO
 * ```
 *
 * Usage with Symfony Entity:
 * ```php
 * #[HasDto(UserDto::class)]
 * class User
 * {
 *     use DtoMappingTrait;
 *
 *     private int $id;
 *     private string $name;
 *     private string $email;
 * }
 *
 * $user = $entityManager->find(User::class, 1);
 * $dto = $user->toDto(); // Uses UserDto::class from attribute
 * ```
 *
 * @phpstan-ignore trait.unused (Trait is intended for external use in Models/Entities)
 */
trait DtoMappingTrait
{
    /**
     * Convert the Model/Entity to a DTO instance.
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

        // Get data from Model/Entity
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
     * Extract data from Model/Entity.
     *
     * Converts collections to DataCollection instances for better handling.
     *
     * @return array<string, mixed>
     */
    private function extractData(): array
    {
        // Check if this is an Eloquent Model
        if ($this instanceof Model) {
            $data = $this->toArray();

            // Convert array collections to DataCollection
            return $this->convertArrayCollectionsToDataCollection($data);
        }

        // Otherwise, treat as Doctrine Entity
        $data = EntityHelper::toArray($this);

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
