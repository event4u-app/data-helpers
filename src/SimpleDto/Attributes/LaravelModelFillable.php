<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Marks a DTO property or class as fillable for Laravel Eloquent Models.
 *
 * When applied to a property, that property will be temporarily added to the model's fillable array
 * during toModel() conversion.
 *
 * When applied to a class, all properties will be temporarily fillable during toModel() conversion.
 *
 * Usage on property:
 * ```php
 * #[HasModel(User::class)]
 * class UserDto extends SimpleDto
 * {
 *     use SimpleDtoEloquentTrait;
 *
 *     #[LaravelModelFillable]
 *     public readonly string $name;
 *
 *     #[LaravelModelFillable]
 *     public readonly string $email;
 *
 *     public readonly string $role; // Not fillable
 * }
 * ```
 *
 * Usage on class:
 * ```php
 * #[HasModel(User::class)]
 * #[LaravelModelFillable]
 * class UserDto extends SimpleDto
 * {
 *     use SimpleDtoEloquentTrait;
 *
 *     public readonly string $name;    // All properties are fillable
 *     public readonly string $email;   // All properties are fillable
 *     public readonly string $role;    // All properties are fillable
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class LaravelModelFillable
{
}
