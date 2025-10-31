<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Cast property value using a custom caster.
 *
 * The caster class must implement a static cast() method:
 *   public static function cast(mixed $value): mixed
 *
 * Example:
 *   class DateTimeCaster {
 *       public static function cast(mixed $value): ?\DateTime {
 *           if ($value === null) return null;
 *           return new \DateTime($value);
 *       }
 *   }
 *
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *           #[CastWith(DateTimeCaster::class)]
 *           public readonly ?\DateTime $createdAt,
 *       ) {}
 *   }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CastWith
{
    /** @param class-string $casterClass */
    public function __construct(
        public readonly string $casterClass,
    ) {}
}
