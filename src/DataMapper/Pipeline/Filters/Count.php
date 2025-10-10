<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Counts elements in an array or countable object.
 *
 * Example:
 *   DataMapper::pipe([Count::class])->map($source, $target, $mapping);
 *   Template: {{ value | count }}
 */
final class Count implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_countable($value) ? count($value) : 0;
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
        return ['count'];
    }
}

