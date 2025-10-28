<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Casters;

use DateTime;
use DateTimeImmutable;

/**
 * Cast string to DateTimeImmutable.
 *
 * Example:
 *   #[CastWith(DateTimeImmutableCaster::class)]
 *   public readonly ?DateTimeImmutable $createdAt;
 */
class DateTimeImmutableCaster
{
    /**
     * Cast value to DateTimeImmutable.
     *
     * @param mixed $value String date, timestamp, or null
     * @return DateTimeImmutable|null
     */
    public static function cast(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}

