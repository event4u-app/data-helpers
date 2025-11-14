<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Attribute to link a DTO to a Symfony Doctrine Entity.
 *
 * This attribute allows DTOs to specify their corresponding Entity class,
 * enabling automatic Entity resolution in toEntity() method.
 *
 * Example:
 * ```php
 * #[HasEntity(User::class)]
 * class UserDto extends SimpleDto
 * {
 *     use SimpleDtoDoctrineTrait;
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
 * $entity = $dto->toEntity(); // Automatically uses User::class
 * ```
 *
 * @requires doctrine/orm
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasEntity
{
    /** @param class-string $entityClass The Doctrine Entity class */
    public function __construct(public readonly string $entityClass)
    {
    }
}
