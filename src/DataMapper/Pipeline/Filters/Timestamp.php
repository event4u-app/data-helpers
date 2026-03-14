<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use DateTimeInterface;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Converts DateTime objects to Unix timestamps (int).
 *
 * Examples:
 *   Template: {{ created | timestamp }}  // DateTime -> 1705276800
 *
 * Pipeline: new Timestamp()
 */
final class Timestamp implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (null === $value) {
            return $value;
        }

        // Handle DateTimeInterface directly
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        // Handle date strings: try to parse
        if (is_string($value) && '' !== $value) {
            $parsed = date_create($value);
            if (false !== $parsed) {
                return $parsed->getTimestamp();
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
        return ['timestamp'];
    }
}
