<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use Throwable;

/**
 * Base trait providing shared Eloquent Model integration logic.
 *
 * This trait contains all the common logic shared between SimpleDtoEloquentTrait
 * and LiteDtoEloquentTrait to eliminate code duplication.
 *
 * @internal This trait is not meant to be used directly. Use SimpleDtoEloquentTrait or LiteDtoEloquentTrait instead.
 */
trait BaseEloquentTrait
{
    /**
     * Check if Laravel Eloquent is installed.
     *
     * @throws BadMethodCallException If Laravel/Eloquent is not installed
     */
    protected static function ensureEloquentIsInstalled(): void
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            throw new BadMethodCallException(
                'Laravel Eloquent is not installed. Please install illuminate/database package.'
            );
        }
    }

    /**
     * Validate that the model class exists and is an Eloquent Model.
     *
     * @param class-string $modelClass The model class to validate
     *
     * @throws InvalidArgumentException If the model class does not exist or is not an Eloquent Model
     */
    protected static function validateModelClass(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException(sprintf('Model class %s does not exist', $modelClass));
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException(
                sprintf('Model class %s must extend ', $modelClass) . Model::class
            );
        }
    }

    /**
     * Try to load an existing model from the database by primary key.
     *
     * @param class-string $modelClass The model class
     * @param mixed $primaryKeyValue The primary key value
     *
     * @return Model|null The loaded model or null if not found
     */
    protected static function loadExistingModel(string $modelClass, mixed $primaryKeyValue): ?Model
    {
        if (null === $primaryKeyValue) {
            return null;
        }

        try {
            /** @var class-string<Model> $modelClass */
            /** @var Model|null $model */
            /** @phpstan-ignore-next-line staticMethod.notFound */
            $model = $modelClass::find($primaryKeyValue);

            return $model;
        } catch (Throwable) {
            // If database query fails (e.g., no connection, table doesn't exist), return null
            return null;
        }
    }

    /**
     * Get the primary key name from a model class.
     *
     * @param class-string $modelClass The model class
     *
     * @return string The primary key name
     */
    protected static function getPrimaryKeyName(string $modelClass): string
    {
        /** @var Model $tempModel */
        $tempModel = new $modelClass();

        return $tempModel->getKeyName();
    }

    /**
     * Filter out timestamp fields from data array.
     *
     * @param array<string, mixed> $data The data array
     * @param bool $includeTimestamps Whether to include timestamp fields
     *
     * @return array<string, mixed> The filtered data array
     */
    protected static function filterEloquentTimestamps(array $data, bool $includeTimestamps): array
    {
        if ($includeTimestamps) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key): bool => !in_array($key, ['created_at', 'updated_at', 'deleted_at'], true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Filter out nested arrays/objects that Laravel's fill() can't handle.
     * Only keep scalar values and null.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed> The filtered data array with only scalar values and null
     */
    protected static function filterScalarValues(array $data): array
    {
        return array_filter(
            $data,
            fn($value): bool => is_scalar($value) || null === $value,
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Fill a model with data, temporarily setting fillable properties if needed.
     *
     * @param Model $model The model to fill
     * @param array<string, mixed> $data The data to fill
     * @param array<string>|null $fillable Array of property names to temporarily make fillable, or ['*'] to make all fillable
     */
    protected static function fillModel(Model $model, array $data, ?array $fillable): void
    {
        // Filter out nested arrays/objects
        $fillableData = static::filterScalarValues($data);

        // If no fillable specified, use model's own fillable/guarded
        if (null === $fillable) {
            $model->fill($fillableData);

            return;
        }

        // Handle empty fillable array - don't fill anything
        if ([] === $fillable) {
            return;
        }

        // Temporarily set fillable properties
        $originalFillable = $model->getFillable();
        $originalGuarded = $model->getGuarded();

        try {
            if (['*'] === $fillable) {
                $model->unguard();
            } else {
                $model->fillable($fillable);
            }

            $model->fill($fillableData);
        } finally {
            // Always restore original fillable/guarded
            if (['*'] === $fillable) {
                $model->reguard();
            } else {
                $model->fillable($originalFillable);
                $model->guard($originalGuarded);
            }
        }
    }

    /**
     * Resolve the Model class from the #[HasModel] attribute.
     *
     * This method must be implemented by the concrete trait (SimpleDtoEloquentTrait or LiteDtoEloquentTrait)
     * because the attribute class is different for each DTO type.
     *
     * @return class-string<Model>
     *
     * @throws InvalidArgumentException If no #[HasModel] attribute is found
     */
    abstract protected function resolveModelClass(): string;

    /**
     * Find the primary key value in the DTO, considering mapping attributes.
     *
     * This method must be implemented by the concrete trait (SimpleDtoEloquentTrait or LiteDtoEloquentTrait)
     * because the logic is different for each DTO type.
     *
     * @param string $primaryKeyName The primary key name
     *
     * @return mixed The primary key value or null if not found
     */
    abstract protected function findEloquentPrimaryKeyValue(string $primaryKeyName): mixed;
}

