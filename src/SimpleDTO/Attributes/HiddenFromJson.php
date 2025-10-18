<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Mark a property as hidden from JSON serialization only.
 *
 * The property will still be included in toArray()
 * and accessible via direct property access.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[HiddenFromJson]
 *         public readonly string $debugInfo,
 *     ) {}
 * }
 *
 * $user = UserDTO::fromArray(['name' => 'John', 'debugInfo' => 'debug data']);
 * $user->toArray(); // ['name' => 'John', 'debugInfo' => 'debug data'] - debugInfo visible
 * json_encode($user); // {"name":"John"} - debugInfo hidden
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class HiddenFromJson
{
}

