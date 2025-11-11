<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Hide property from serialization when value is null.
 *
 * This attribute excludes properties from toArray() and toJson() when their value is null.
 * Non-null values are included normally.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[HideWhenNull]
 *         public readonly ?string $middleName = null,
 *         #[HideWhenNull]
 *         public readonly ?string $nickname = null,
 *     ) {}
 * }
 *
 * $dto = UserDto::from(['name' => 'John', 'middleName' => null, 'nickname' => 'Johnny']);
 * $dto->toArray(); // ['name' => 'John', 'nickname' => 'Johnny'] (middleName excluded because null)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class HideWhenNull
{
}
