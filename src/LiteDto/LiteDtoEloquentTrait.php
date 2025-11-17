<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use BadMethodCallException;
use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use event4u\DataHelpers\LiteDto\Attributes\Map;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Trait providing Eloquent Model integration for LiteDtos.
 *
 * This trait is optional and only used when Laravel/Eloquent is available.
 * It provides methods to convert between Dtos and Eloquent Models.
 *
 * Usage:
 * ```php
 * class UserDto extends LiteDto
 * {
 *     use LiteDtoEloquentTrait;
 *
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * // Create Dto from Model
 * $user = User::find(1);
 * $dto = UserDto::fromModel($user);
 *
 * // Create Model from Dto
 * $model = $dto->toModel(User::class);
 * $model->save();
 * ```
 *
 * @requires illuminate/database
 * @phpstan-ignore trait.unused (Optional trait, only used when Laravel/Eloquent is installed)
 */
trait LiteDtoEloquentTrait
{
    /**
     * Create a Dto instance from an Eloquent Model.
     *
     * Extracts all attributes from the model and creates a Dto instance.
     * Supports relationships and accessors via the model's toArray() method.
     *
     * @param object $model The Eloquent Model instance
     *
     *
     * @throws InvalidArgumentException If the model does not have a toArray() method
     * @throws BadMethodCallException If Laravel/Eloquent is not installed
     */
    public static function fromModel(object $model): static
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            throw new BadMethodCallException(
                'Laravel Eloquent is not installed. Please install illuminate/database package.'
            );
        }

        if (!($model instanceof Model)) {
            throw new InvalidArgumentException('Model must be an instance of Illuminate\Database\Eloquent\Model');
        }

        // Get all model attributes including relationships
        $data = $model->toArray();

        // Create Dto from array
        return static::from($data);
    }

    /**
     * Convert the Dto to an Eloquent Model instance.
     *
     * If the DTO contains a primary key value, loads the existing model from the database
     * and updates it with DTO data. Otherwise creates a new model instance.
     * Does NOT save the model to the database.
     *
     * If no model class is provided, it will try to resolve it from the #[HasModel] attribute.
     *
     * @param class-string|null $modelClass The Eloquent Model class (optional if #[HasModel] attribute is present)
     * @param bool $exists Whether the model should be marked as existing (default: false)
     * @param array<string>|null $fillable Array of property names to temporarily make fillable, or ['*'] to make all fillable (default: null = use model's fillable/guarded)
     * @param bool $includeTimestamps Whether to include timestamp fields (created_at, updated_at, deleted_at) (default: false)
     *
     * @return object The model instance
     *
     * @throws InvalidArgumentException If no model class is provided and no #[HasModel] attribute is found
     * @throws InvalidArgumentException If the model class does not exist or is not an Eloquent Model
     * @throws BadMethodCallException If Laravel/Eloquent is not installed
     */
    public function toModel(
        ?string $modelClass = null,
        bool $exists = false,
        ?array $fillable = null,
        bool $includeTimestamps = false
    ): object
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            throw new BadMethodCallException(
                'Laravel Eloquent is not installed. Please install illuminate/database package.'
            );
        }

        // If no model class provided, try to resolve from attribute
        if (null === $modelClass) {
            $modelClass = $this->resolveModelClass();
        }

        // Check if model class exists
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException(sprintf('Model class %s does not exist', $modelClass));
        }

        // Check if model class is an Eloquent Model
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException(
                sprintf('Model class %s must extend ', $modelClass) . Model::class
            );
        }

        // Create temporary model instance to get primary key name
        /** @var Model $tempModel */
        $tempModel = new $modelClass();
        $primaryKeyName = $tempModel->getKeyName();

        // Try to find primary key value in DTO (considering mapping attributes)
        $primaryKeyValue = $this->findEloquentPrimaryKeyValue($primaryKeyName);

        // Try to load existing model from database if primary key exists in DTO
        $model = null;
        if (null !== $primaryKeyValue) {
            try {
                /** @var class-string<Model> $modelClass */
                /** @var Model|null $model */
                /** @phpstan-ignore-next-line staticMethod.notFound */
                $model = $modelClass::find($primaryKeyValue);
            } catch (Throwable) {
                // If database query fails (e.g., no connection, table doesn't exist), create new instance
                $model = null;
            }
        }

        // If no model found, create new instance
        if (null === $model) {
            /** @var Model $model */
            $model = new $modelClass();
        }

        // Get DTO data and filter out timestamp fields if not included
        $data = $this->toArray();
        if (!$includeTimestamps) {
            $data = array_filter(
                $data,
                fn($key): bool => !in_array($key, ['created_at', 'updated_at', 'deleted_at'], true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Determine fillable properties
        $fillableProperties = $fillable;

        // Temporarily set fillable if needed
        if (null !== $fillableProperties) {
            // Handle empty fillable array - don't fill anything
            if ([] === $fillableProperties) {
                // Don't call fill() at all - model will remain empty
            } else {
                $originalFillable = $model->getFillable();
                $originalGuarded = $model->getGuarded();

                // Set fillable properties
                if (['*'] === $fillableProperties) {
                    $model->unguard();
                } else {
                    $model->fillable($fillableProperties);
                }

                // Fill model with Dto data
                $model->fill($data);

                // Restore original fillable/guarded
                if (['*'] === $fillableProperties) {
                    $model->reguard();
                } else {
                    $model->fillable($originalFillable);
                    $model->guard($originalGuarded);
                }
            }
        } else {
            // Fill model with Dto data using model's own fillable/guarded
            $model->fill($data);
        }

        // Mark as existing if requested
        if ($exists) {
            $model->exists = $exists;
        }

        return $model;
    }

    /**
     * Resolve the Model class from the #[HasModel] attribute.
     *
     * @return class-string<Model>
     *
     * @throws InvalidArgumentException If no #[HasModel] attribute is found
     */
    private function resolveModelClass(): string
    {
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(HasModel::class);

        if ([] === $attributes) {
            throw new InvalidArgumentException(
                sprintf(
                    'No Model class provided and no #[HasModel] attribute found on %s. ' .
                    'Either provide a Model class as parameter or add #[HasModel(YourModel::class)] attribute to the DTO class.',
                    $reflection->getName()
                )
            );
        }

        /** @var HasModel $hasModel */
        $hasModel = $attributes[0]->newInstance();

        /** @var class-string<Model> */
        return $hasModel->modelClass;
    }

    /**
     * Find the primary key value in the DTO, considering mapping attributes.
     *
     * This method searches for the primary key value by:
     * 1. Checking if a DTO property maps TO the primary key name (via #[MapTo] or #[Map])
     * 2. Checking if a DTO property has the same name as the primary key
     * 3. Checking the toArray() output for the primary key
     *
     * @param string $primaryKeyName The primary key field name from the Model
     * @return mixed The primary key value, or null if not found
     */
    private function findEloquentPrimaryKeyValue(string $primaryKeyName): mixed
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
