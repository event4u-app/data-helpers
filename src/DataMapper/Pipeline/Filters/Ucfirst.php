<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Capitalizes the first character of a string.
 *
 * Example:
 *   DataMapper::pipe([Ucfirst::class])->map($source, $target, $mapping);
 *   Template: {{ value | ucfirst }}
 */
final class Ucfirst implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? ucfirst($value) : $value;
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
        return ['ucfirst'];
    }
}

