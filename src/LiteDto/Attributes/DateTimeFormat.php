<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Specify the format for DateTime/DateTimeImmutable serialization.
 *
 * This attribute controls how DateTime objects are formatted when serializing to array, JSON, or string.
 *
 * Example:
 *   class EventDto extends SimpleDto {
 *       public function __construct(
 *           #[DateTimeFormat('Y-m-d')]
 *           public readonly DateTimeImmutable $date,
 *
 *           #[DateTimeFormat('Y-m-d H:i:s')]
 *           public readonly DateTimeImmutable $createdAt,
 *
 *           #[DateTimeFormat('c', timezone: 'UTC')]
 *           public readonly DateTimeImmutable $timestamp,
 *       ) {}
 *   }
 *
 *   $dto = EventDto::from(['date' => '2024-01-15', 'createdAt' => '2024-01-15 10:30:00']);
 *   $array = $dto->toArray();
 *   // ['date' => '2024-01-15', 'createdAt' => '2024-01-15 10:30:00', 'timestamp' => '2024-01-15T10:30:00+00:00']
 *
 * Supported formats:
 * - Any PHP date() format string (e.g., 'Y-m-d', 'Y-m-d H:i:s', 'c', 'U')
 * - Default format if not specified: 'Y-m-d H:i:s'
 *
 * Works with:
 * - SimpleDto::toArray()
 * - SimpleDto::toJson()
 * - SimpleDto::jsonSerialize()
 * - LiteDto::toArray()
 * - LiteDto::toJson()
 * - LiteDto::jsonSerialize()
 * - DataMapper serialization
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DateTimeFormat
{
    /**
     * @param string $format PHP date() format string (default: 'Y-m-d H:i:s')
     * @param string|null $timezone Optional timezone for formatting (e.g., 'UTC', 'Europe/Berlin')
     */
    public function __construct(
        public readonly string $format = 'Y-m-d H:i:s',
        public readonly ?string $timezone = null,
    ) {}

    /**
     * Format a DateTime value using this attribute's format.
     * Supports DateTime, DateTimeImmutable, and Carbon (if installed).
     *
     * @param DateTimeInterface $value DateTime value to format
     * @return string Formatted date string
     */
    public function format(DateTimeInterface $value): string
    {
        // If timezone is specified, convert to that timezone first
        if (null !== $this->timezone) {
            $timezone = new DateTimeZone($this->timezone);

            // Handle different DateTime types
            // Check for Carbon first (if installed), then fall back to base classes
            // Carbon\CarbonImmutable extends DateTimeImmutable
            // Carbon\Carbon extends DateTime
            if ($value instanceof DateTimeImmutable) {
                // DateTimeImmutable, Carbon\CarbonImmutable (if Carbon is installed)
                $value = $value->setTimezone($timezone);
            } elseif ($value instanceof DateTime) {
                // DateTime, Carbon\Carbon (if Carbon is installed) - mutable, so clone first
                // @phpstan-ignore-next-line
                $value = (clone $value)->setTimezone($timezone);
            }
        }

        return $value->format($this->format);
    }
}
