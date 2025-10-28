<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Casters;

use DateTime;
use DateTimeImmutable;

/**
 * Cast string to DateTime or DateTimeImmutable.
 *
 * Example:
 *   #[CastWith(DateTimeCaster::class)]
 *   public readonly ?DateTime $createdAt;
 */
class DateTimeCaster
{
    /**
     * Cast value to DateTime.
     *
     * @param mixed $value String date, timestamp, or null
     */
    public static function cast(mixed $value): ?DateTime
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        if ($value instanceof DateTimeImmutable) {
            return DateTime::createFromImmutable($value);
        }

        return new DateTime((string)$value);
    }
}
