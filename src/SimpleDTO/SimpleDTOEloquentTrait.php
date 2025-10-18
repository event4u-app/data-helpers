<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Trait providing Eloquent Model integration for SimpleDTOs.
 *
 * This trait is optional and only used when Laravel/Eloquent is available.
 * It provides methods to convert between DTOs and Eloquent Models.
 *
 * Usage:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     use SimpleDTOEloquentTrait;
 *
 *     public function __construct(
 *         public readonly string $name,
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * // Create DTO from Model
 * $user = User::find(1);
 * $dto = UserDTO::fromModel($user);
 *
 * // Create Model from DTO
 * $model = $dto->toModel(User::class);
 * $model->save();
 * ```
 *
 * @requires illuminate/database
 */
trait SimpleDTOEloquentTrait
{
    /**
     * Create a DTO instance from an Eloquent Model.
     *
     * Extracts all attributes from the model and creates a DTO instance.
     * Supports relationships and accessors via the model's toArray() method.
     *
     * @param Model $model The Eloquent Model instance
     *
     * @return static
     *
     * @throws InvalidArgumentException If the model does not have a toArray() method
     */
    public static function fromModel(Model $model): static
    {
        // Get all model attributes including relationships
        $data = $model->toArray();

        // Create DTO from array
        return static::fromArray($data);
    }

    /**
     * Convert the DTO to an Eloquent Model instance.
     *
     * Creates a new model instance and fills it with DTO data.
     * Does NOT save the model to the database.
     *
     * @param class-string<Model> $modelClass The Eloquent Model class
     * @param bool $exists Whether the model should be marked as existing (default: false)
     *
     * @return Model The model instance
     *
     * @throws InvalidArgumentException If the model class does not exist or is not an Eloquent Model
     */
    public function toModel(string $modelClass, bool $exists = false): Model
    {
        // Check if model class exists
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        // Check if model class is an Eloquent Model
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException(
                "Model class {$modelClass} must extend " . Model::class
            );
        }

        // Create new model instance
        /** @var Model $model */
        $model = new $modelClass();

        // Fill model with DTO data
        $model->fill($this->toArray());

        // Mark as existing if requested
        if ($exists) {
            $model->exists = $exists;
        }

        return $model;
    }
}

