<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use RuntimeException;

/**
 * Immutable LiteDto with strict immutability enforcement.
 *
 * ImmutableLiteDto extends LiteDto and adds runtime checks to prevent
 * any property modifications after construction. This is useful when
 * you want to ensure absolute immutability at runtime.
 *
 * Note: LiteDto already uses readonly properties, so this class is
 * primarily for explicit immutability guarantees and better semantics.
 *
 * Example usage:
 *   class UserDto extends ImmutableLiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly int $age,
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['name' => 'John', 'age' => 30]);
 *   // $user->name = 'Jane'; // Error: Cannot modify readonly property
 *
 * Differences from LiteDto:
 * - Explicit immutability semantics
 * - Runtime checks via __set() magic method
 * - Better documentation of immutability intent
 */
abstract class ImmutableLiteDto extends LiteDto
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
                'ImmutableLiteDto instances cannot be modified after construction.',
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
                'ImmutableLiteDto instances cannot be modified after construction.',
                $name,
                static::class
            )
        );
    }
}
