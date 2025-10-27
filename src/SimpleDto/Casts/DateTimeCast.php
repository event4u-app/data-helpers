<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use DateTimeImmutable;
use DateTimeInterface;
use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to DateTimeImmutable.
 *
 * Supports:
 * - Unix timestamps (int)
 * - Date strings (string)
 * - DateTimeInterface objects
 *
 * Example:
 *   protected function casts(): array {
 *       return ['created_at' => DateTimeCast::class];
 *   }
 */
class DateTimeCast implements CastsAttributes
{
    public function __construct(
        private readonly ?string $format = null,
    ) {}

    public function get(mixed $value, array $attributes): ?DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_int($value)) {
            return (new DateTimeImmutable())->setTimestamp($value);
        }

        if (is_string($value)) {
            if ($this->format) {
                $date = DateTimeImmutable::createFromFormat($this->format, $value);
                if (false !== $date) {
                    return $date;
                }
            }

            return new DateTimeImmutable($value);
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->format ?? 'Y-m-d H:i:s');
        }

        return is_string($value) ? $value : null;
    }
}
