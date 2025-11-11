<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Contracts;

/**
 * Interface for attributes that transform values before validation.
 *
 * Transform attributes modify the input value before any validation occurs.
 * This is useful for normalizing data (e.g., converting to lowercase/uppercase).
 *
 * Example:
 * ```php
 * #[Attribute]
 * class Lowercase implements TransformAttribute
 * {
 *     public function transform(mixed $value, string $propertyName): mixed
 *     {
 *         return is_string($value) ? mb_strtolower($value, 'UTF-8') : $value;
 *     }
 * }
 * ```
 */
interface TransformAttribute
{
    /**
     * Transform the value before validation.
     *
     * @param mixed $value The value to transform
     * @param string $propertyName The name of the property being transformed
     * @return mixed The transformed value
     */
    public function transform(mixed $value, string $propertyName): mixed;
}
