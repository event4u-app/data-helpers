<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\Attributes\LaravelModelFillable;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

if (!class_exists('Illuminate\Database\Eloquent\Model')) {
    trait SimpleDtoEloquentTrait {}
} else {
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
    public static function fromModel(\Illuminate\Database\Eloquent\Model $model): static
    {
        // Get all model attributes including relationships
        $data = $model->toArray();

        // Create Dto from array
        return static::fromArray($data);
    }

    /**
     * Convert the Dto to an Eloquent Model instance.
     *
     * Creates a new model instance and fills it with Dto data.
     * Does NOT save the model to the database.
     *
     * If no model class is provided, it will try to resolve it from the #[HasModel] attribute.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model>|null $modelClass The Eloquent Model class (optional if #[HasModel] attribute is present)
     * @param bool $exists Whether the model should be marked as existing (default: false)
     * @param array<string>|null $fillable Array of property names to temporarily make fillable, or ['*'] to make all fillable (default: null = use model's fillable/guarded)
     * @param bool $includeTimestamps Whether to include timestamp fields (created_at, updated_at, deleted_at) (default: false)
     *
     * @return \Illuminate\Database\Eloquent\Model The model instance
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
    ): \Illuminate\Database\Eloquent\Model
    {
        // If no model class provided, try to resolve from attribute
        if (null === $modelClass) {
            $modelClass = $this->resolveModelClass();
        }

        // Check if model class exists
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException(sprintf('Model class %s does not exist', $modelClass));
        }

        // Check if model class is an Eloquent Model
        if (!is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            throw new InvalidArgumentException(
                sprintf('Model class %s must extend ', $modelClass) . \Illuminate\Database\Eloquent\Model::class
            );
        }

        // Create new model instance
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelClass();

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
        $fillableProperties = $this->resolveFillableProperties($fillable);

        // Temporarily set fillable if needed
        if (null !== $fillableProperties) {
            // Handle empty fillable array - don't fill anything
            if (empty($fillableProperties)) {
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
     * @return class-string<\Illuminate\Database\Eloquent\Model>
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

        /** @var class-string<\Illuminate\Database\Eloquent\Model> */
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
    }
}
