<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Returns the last element of an array.
 *
 * Example:
 *   DataMapper::pipe([Last::class])->map($source, $target, $mapping);
 *   Template: {{ value | last }}
 */
final class Last implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? end($value) : $value;
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
        return ['last'];
    }
}

