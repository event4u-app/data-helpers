<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use RuntimeException;

/**
 * Immutable SimpleDto with strict immutability enforcement.
 *
 * ImmutableSimpleDto extends SimpleDto and adds runtime checks to prevent
 * any property modifications after construction. This is useful when
 * you want to ensure absolute immutability at runtime.
 *
 * Note: SimpleDto already uses readonly properties, so this class is
 * primarily for explicit immutability guarantees and better semantics.
 *
 * Example usage:
 *   class UserDto extends ImmutableSimpleDto {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly int $age,
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['name' => 'John', 'age' => 30]);
 *   // $user->name = 'Jane'; // Error: Cannot modify readonly property
 *
 * Differences from SimpleDto:
 * - Explicit immutability semantics
 * - Runtime checks via __set() magic method
 * - Better documentation of immutability intent
 */
abstract class ImmutableSimpleDto extends SimpleDto
{
    /**
     * Prevent property modification after construction.
     *
     * @throws RuntimeException Always throws when attempting to set a property
     */
    public function __set(string $name, mixed $value): void
    {
        throw new RuntimeException(
            sprintf(
                'Cannot modify property "%s" on immutable DTO "%s". ' .
                'ImmutableSimpleDto instances cannot be modified after construction.',
                $name,
                static::class
            )
        );
    }

    /**
     * Prevent property unsetting.
     *
     * @throws RuntimeException Always throws when attempting to unset a property
     */
    public function __unset(string $name): void
    {
        throw new RuntimeException(
            sprintf(
                'Cannot unset property "%s" on immutable DTO "%s". ' .
                'ImmutableSimpleDto instances cannot be modified after construction.',
                $name,
                static::class
            )
        );
    }
}
