<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Removes duplicate values from an array.
 *
 * Example:
 *   DataMapper::pipe([Unique::class])->map($source, $target, $mapping);
 *   Template: {{ value | unique }}
 */
final class Unique implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? array_unique($value) : $value;
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
        return ['unique'];
    }
}

