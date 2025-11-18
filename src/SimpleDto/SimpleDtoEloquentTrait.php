<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use BadMethodCallException;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\Attributes\LaravelModelFillable;
use event4u\DataHelpers\SimpleDto\Attributes\Map;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\Support\Traits\BaseEloquentTrait;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Trait providing Eloquent Model integration for SimpleDtos.
 *
 * This trait is optional and only used when Laravel/Eloquent is available.
 * It provides methods to convert between Dtos and Eloquent Models.
 *
 * Usage:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     use SimpleDtoEloquentTrait;
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
trait SimpleDtoEloquentTrait
{
    use BaseEloquentTrait;

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
        static::ensureEloquentIsInstalled();

        if (!($model instanceof Model)) {
            throw new InvalidArgumentException('Model must be an instance of Illuminate\Database\Eloquent\Model');
        }

        // Get all model attributes including relationships
        $data = $model->toArray();

        // Create Dto from array
        return static::fromArray($data);
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
        static::ensureEloquentIsInstalled();

        // If no model class provided, try to resolve from attribute
        if (null === $modelClass) {
            $modelClass = $this->resolveModelClass();
        }

        // Validate model class
        static::validateModelClass($modelClass);

        // Get primary key name and value
        $primaryKeyName = static::getPrimaryKeyName($modelClass);
        $primaryKeyValue = $this->findEloquentPrimaryKeyValue($primaryKeyName);

        // Try to load existing model from database
        $model = static::loadExistingModel($modelClass, $primaryKeyValue);

        // If no model found, create new instance
        if (!$model instanceof Model) {
            /** @var Model $model */
            $model = new $modelClass();
        }

        // Get DTO data and filter timestamps
        $data = $this->toArray();
        $data = static::filterEloquentTimestamps($data, $includeTimestamps);

        // Determine fillable properties
        $fillableProperties = $this->resolveFillableProperties($fillable);

        // Fill model with data
        static::fillModel($model, $data, $fillableProperties);

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
    protected function resolveModelClass(): string
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
     * Resolve fillable properties from parameter or #[LaravelModelFillable] attribute.
     *
     * Priority:
     * 1. If $fillable parameter is provided, use it
     * 2. If #[LaravelModelFillable] attribute is present, use properties marked with it
     * 3. Otherwise, return null to use model's own fillable/guarded
     *
     * @param array<string>|null $fillable Explicitly provided fillable properties
     *
     * @return array<string>|null Array of fillable property names, ['*'] for all, or null to use model's fillable/guarded
     */
    private function resolveFillableProperties(?array $fillable): ?array
    {
        // If fillable parameter is provided, use it
        if (null !== $fillable) {
            return $fillable;
        }

        // Check if class has #[LaravelModelFillable] attribute
        $reflection = new ReflectionClass($this);
        $classAttributes = $reflection->getAttributes(LaravelModelFillable::class);

        // If class has the attribute, all properties are fillable
        if ([] !== $classAttributes) {
            return ['*'];
        }

        // Check for properties with #[LaravelModelFillable] attribute
        $fillableProperties = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $propertyAttributes = $reflectionProperty->getAttributes(LaravelModelFillable::class);
            if (!empty($propertyAttributes)) {
                $fillableProperties[] = $reflectionProperty->getName();
            }
        }

        // If we found properties with the attribute, return them
        if ([] !== $fillableProperties) {
            return $fillableProperties;
        }

        // No fillable configuration found, use model's own fillable/guarded
        return null;
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
    protected function findEloquentPrimaryKeyValue(string $primaryKeyName): mixed
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
