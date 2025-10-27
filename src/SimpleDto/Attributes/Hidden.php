<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Mark a property as hidden from toArray() and JSON serialization.
 *
 * The property will still be accessible via direct property access,
 * but will be excluded from array and JSON output.
 *
 * Example:
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Hidden]
 *         public readonly string $password,
 *     ) {}
 * }
 *
 * $user = UserDto::fromArray(['name' => 'John', 'password' => 'secret']);
 * echo $user->password; // 'secret' - accessible
 * $user->toArray(); // ['name' => 'John'] - password hidden
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Hidden
{
}
