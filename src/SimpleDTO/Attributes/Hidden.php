<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Mark a property as hidden from toArray() and JSON serialization.
 *
 * The property will still be accessible via direct property access,
 * but will be excluded from array and JSON output.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Hidden]
 *         public readonly string $password,
 *     ) {}
 * }
 *
 * $user = UserDTO::fromArray(['name' => 'John', 'password' => 'secret']);
 * echo $user->password; // 'secret' - accessible
 * $user->toArray(); // ['name' => 'John'] - password hidden
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Hidden
{
}
