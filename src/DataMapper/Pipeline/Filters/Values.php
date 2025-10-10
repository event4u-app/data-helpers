<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Returns the values of an array.
 *
 * Example:
 *   DataMapper::pipe([Values::class])->map($source, $target, $mapping);
 *   Template: {{ value | values }}
 */
final class Values implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? array_values($value) : [];
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
        return ['values'];
    }
}

