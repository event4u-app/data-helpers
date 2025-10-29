<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Contracts;

/**
 * Interface for custom type casters in LiteDto.
 *
 * Casters are used with the #[CastWith] attribute to transform
 * values during DTO creation.
 *
 * Example:
 * ```php
 * class UpperCaseCaster implements CasterInterface
 * {
 *     public function cast(mixed $value): mixed
 *     {
 *         return is_string($value) ? strtoupper($value) : $value;
 *     }
 * }
 *
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         #[CastWith(UpperCaseCaster::class)]
 *         public readonly string $name,
 *     ) {}
 * }
 * ```
 */
interface CasterInterface
{
    /**
     * Cast the given value to the desired type.
     *
     * @param mixed $value The value to cast
     * @return mixed The casted value
     */
    public function cast(mixed $value): mixed;
}
