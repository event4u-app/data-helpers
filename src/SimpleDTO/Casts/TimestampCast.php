<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use DateTimeImmutable;
use DateTimeInterface;
use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;
use Throwable;

/**
 * Cast attribute to Unix timestamp (integer).
 *
 * Converts DateTimeInterface to Unix timestamp when setting.
 * Converts Unix timestamp to DateTimeImmutable when getting.
 *
 * Example:
 *   protected function casts(): array {
 *       return ['created_at' => 'timestamp'];
 *   }
 */
class TimestampCast implements CastsAttributes
{
    public function __construct() {}

    public function get(mixed $value, array $attributes): ?DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        // Already a DateTimeImmutable
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        // Convert other DateTime objects
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        // Convert Unix timestamp (int or numeric string)
        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            try {
                return (new DateTimeImmutable())->setTimestamp((int)$value);
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?int
    {
        if (null === $value) {
            return null;
        }

        // Convert DateTimeInterface to timestamp
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        // Already a timestamp
        if (is_int($value)) {
            return $value;
        }

        // Convert numeric string to timestamp
        if (is_string($value) && is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}

