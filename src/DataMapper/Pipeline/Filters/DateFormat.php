<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use DateTimeInterface;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Formats DateTime objects to strings using PHP date format.
 *
 * Default format: 'Y-m-d H:i:s'
 *
 * Examples:
 *   Template: {{ created | date }}              // '2024-01-15 10:30:00'
 *   Template: {{ created | date:"Y-m-d" }}      // '2024-01-15'
 *   Template: {{ created | date:"d.m.Y" }}      // '15.01.2024'
 *   Template: {{ created | date:"c" }}          // ISO 8601
 *   Template: {{ created | date:"U" }}          // Unix timestamp as string
 *
 * Pipeline: new DateFormat('Y-m-d')
 */
final readonly class DateFormat implements FilterInterface
{
    private const DEFAULT_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private string $format = self::DEFAULT_FORMAT,
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (null === $value) {
            return $value;
        }

        // Determine format: from filter args or constructor
        $args = $context->extra();
        $format = $args[0] ?? $this->format;

        if (!is_string($format)) {
            $format = self::DEFAULT_FORMAT;
        }

        // Handle DateTimeInterface directly
        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        }

        // Handle date strings: try to parse and reformat
        if (is_string($value) && '' !== $value) {
            $parsed = date_create($value);
            if (false !== $parsed) {
                return $parsed->format($format);
            }
        }

        // Handle Unix timestamps (int or numeric string)
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $parsed = date_create_from_format('U', (string)$value);
            if (false !== $parsed) {
                return $parsed->format($format);
            }
        }

        return $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['date', 'date_format'];
    }
}
