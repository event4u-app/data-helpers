<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Sorts an array in ascending order.
 *
 * Example:
 *   DataMapper::pipe([Sort::class])->map($source, $target, $mapping);
 *   Template: {{ value | sort }}
 */
final class Sort implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $sorted = $value;
        sort($sorted);

        return $sorted;
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
        return ['sort'];
    }
}

