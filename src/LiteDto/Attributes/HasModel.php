<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Attribute to link a DTO to a Laravel Eloquent Model.
 *
 * This attribute allows DTOs to specify their corresponding Model class,
 * enabling automatic Model resolution in toModel() method.
 *
 * Example:
 * ```php
 * #[HasModel(User::class)]
 * class UserDto extends LiteDto
 * {
 *     use LiteDtoEloquentTrait;
 *
 *     public function __construct(
 *         public readonly int $id,
 *         public readonly string $name,
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * // Usage:
 * $dto = new UserDto(1, 'John', 'john@example.com');
 * $model = $dto->toModel(); // Automatically uses User::class
 * ```
 *
 * @requires illuminate/database
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasModel
{
    /** @param class-string $modelClass The Eloquent Model class */
    public function __construct(public readonly string $modelClass)
    {
    }
}
