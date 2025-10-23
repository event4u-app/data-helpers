<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Mark a property as hidden from toArray() only.
 *
 * The property will still be included in JSON serialization
 * and accessible via direct property access.
 *
 * Example:
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[HiddenFromArray]
 *         public readonly string $internalId,
 *     ) {}
 * }
 *
 * $user = UserDTO::fromArray(['name' => 'John', 'internalId' => '123']);
 * $user->toArray(); // ['name' => 'John'] - internalId hidden
 * json_encode($user); // {"name":"John","internalId":"123"} - internalId visible
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class HiddenFromArray
{
}
