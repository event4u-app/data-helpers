<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

/**
 * Trait for lazy cast resolution in SimpleDTOs.
 *
 * This trait provides optimizations for cast application:
 * - Only apply casts when values are present
 * - Skip casts for unchanged properties
 * - Track which properties have been casted
 * - Optimize nested DTO casting
 */
trait SimpleDTOLazyCastTrait
{
    /**
     * Cache for properties that have been casted.
     *
     * @var array<string, bool>
     */
    private array $castedProperties = [];

    /**
     * Apply casts only to properties that are present in the data.
     *
     * This is an optimized version of applyCasts() that skips properties
     * that are not present in the input data.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $casts
     * @return array<string, mixed>
     */
    private static function applyLazyCasts(array $data, array $casts): array
    {
        foreach ($casts as $key => $cast) {
            // Skip if property is not present in data
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // Skip if value is null and cast is not required
            if (null === $data[$key] && !static::isCastRequired($cast)) {
                continue;
            }

            $data[$key] = static::castAttribute($key, $data[$key], $cast, $data);
        }

        return $data;
    }

    /**
     * Check if a cast is required even for null values.
     *
     * Some casts (like default values) should be applied even for null.
     *
     * @param string $cast
     * @return bool
     */
    private static function isCastRequired(string $cast): bool
    {
        // Add cast types that should be applied even for null values
        $requiredCasts = [
            'default',
            'required',
        ];

        foreach ($requiredCasts as $requiredCast) {
            if (str_starts_with($cast, $requiredCast)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply casts only to changed properties.
     *
     * This is useful when updating DTOs - only cast properties that have changed.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $originalData
     * @param array<string, string> $casts
     * @return array<string, mixed>
     */
    private static function applyChangedCasts(array $data, array $originalData, array $casts): array
    {
        foreach ($casts as $key => $cast) {
            // Skip if property is not present in data
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // Skip if value hasn't changed
            if (array_key_exists($key, $originalData) && $data[$key] === $originalData[$key]) {
                continue;
            }

            $data[$key] = static::castAttribute($key, $data[$key], $cast, $data);
        }

        return $data;
    }

    /**
     * Mark a property as casted.
     *
     * @param string $property
     */
    private function markPropertyAsCasted(string $property): void
    {
        $this->castedProperties[$property] = true;
    }

    /**
     * Check if a property has been casted.
     *
     * @param string $property
     * @return bool
     */
    private function isPropertyCasted(string $property): bool
    {
        return $this->castedProperties[$property] ?? false;
    }

    /**
     * Clear casted properties tracking.
     */
    private function clearCastedProperties(): void
    {
        $this->castedProperties = [];
    }

    /**
     * Get statistics about casted properties.
     *
     * @return array{total: int, casted: int, uncasted: int}
     */
    public function getCastStatistics(): array
    {
        $totalProperties = count(get_object_vars($this));
        $castedCount = count($this->castedProperties);

        return [
            'total' => $totalProperties,
            'casted' => $castedCount,
            'uncasted' => $totalProperties - $castedCount,
        ];
    }

    /**
     * Apply casts selectively based on configuration.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $casts
     * @param array{lazy: bool, skipNull: bool, trackCasted: bool} $options
     * @return array<string, mixed>
     */
    private static function applySelectiveCasts(
        array $data,
        array $casts,
        array $options = []
    ): array {
        $lazy = $options['lazy'] ?? true;
        $skipNull = $options['skipNull'] ?? true;
        $trackCasted = $options['trackCasted'] ?? false;

        foreach ($casts as $key => $cast) {
            // Skip if lazy mode and property not present
            if ($lazy && !array_key_exists($key, $data)) {
                continue;
            }

            // Skip if skipNull mode and value is null
            if ($skipNull && null === $data[$key] && !static::isCastRequired($cast)) {
                continue;
            }

            $data[$key] = static::castAttribute($key, $data[$key], $cast, $data);

            // Track casted property if requested
            if ($trackCasted) {
                // Note: This would need instance context
            }
        }

        return $data;
    }

    /**
     * Optimize nested DTO casting by checking if value is already a DTO instance.
     *
     * @param mixed $value
     * @param string $dtoClass
     * @return bool
     */
    private static function isAlreadyCasted(mixed $value, string $dtoClass): bool
    {
        // Check if value is already an instance of the target DTO class
        if (is_object($value) && $value instanceof $dtoClass) {
            return true;
        }

        return false;
    }

    /**
     * Apply casts with nested DTO optimization.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $casts
     * @return array<string, mixed>
     */
    private static function applyOptimizedCasts(array $data, array $casts): array
    {
        foreach ($casts as $key => $cast) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // Skip if value is null
            if (null === $data[$key]) {
                continue;
            }

            // Check if it's a DTO cast and value is already casted
            if (class_exists($cast) && static::isAlreadyCasted($data[$key], $cast)) {
                continue;
            }

            $data[$key] = static::castAttribute($key, $data[$key], $cast, $data);
        }

        return $data;
    }

    /**
     * Get cast performance metrics.
     *
     * @return array{
     *     totalCasts: int,
     *     appliedCasts: int,
     *     skippedCasts: int,
     *     efficiency: float
     * }
     */
    public static function getCastPerformanceMetrics(): array
    {
        // This would need to be tracked during cast application
        // For now, return placeholder data
        return [
            'totalCasts' => 0,
            'appliedCasts' => 0,
            'skippedCasts' => 0,
            'efficiency' => 0.0,
        ];
    }
}

