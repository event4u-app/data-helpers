<?php

declare(strict_types=1);

namespace Tests\utils\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Test transformer that reverses strings.
 */
class ReverseString implements FilterInterface
{
    public function getHook(): string
    {
        return 'preTransform';
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return strrev($value);
    }

    public function getFilter(): ?string
    {
        return null;
    }

    public function getAliases(): array
    {
        return ['reverse_str', 'rev_str'];
    }
}

