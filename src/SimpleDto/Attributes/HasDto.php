<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to link a Model/Entity to a DTO.
 *
 * This attribute allows Models and Entities to specify their corresponding DTO class,
 * enabling automatic DTO resolution in toDto() method.
 *
 * Example:
 * ```php
 * #[HasDto(UserDto::class)]
 * class User extends Model
 * {
 *     use DtoMappingTrait;
 *
 *     protected $fillable = ['name', 'email'];
 * }
 *
 * // Usage:
 * $user = User::find(1);
 * $dto = $user->toDto(); // Automatically uses UserDto::class
 * ```
 *
 * Works with both Laravel Eloquent Models and Symfony Doctrine Entities.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasDto
{
    /** @param class-string $dtoClass The DTO class */
    public function __construct(public readonly string $dtoClass)
    {
    }
}
