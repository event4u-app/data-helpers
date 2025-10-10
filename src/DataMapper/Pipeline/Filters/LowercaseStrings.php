<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Converts all string values to lowercase.
 *
 * Example:
 *   DataMapper::pipe([LowercaseStrings::class])->map($source, $target, $mapping);
 *   Template: {{ value | lower }} or {{ value | lowercase }}
 */
final class LowercaseStrings implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['lower', 'lowercase'];
    }
}
