<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Removes null values from being written to target.
 *
 * Returns '__skip__' to prevent writing null values.
 *
 * Example:
 *   DataMapper::pipe([RemoveNullValues::class])->map($source, $target, $mapping);
 */
final class RemoveNullValues implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return null === $value ? '__skip__' : $value;
    }

    public function getHook(): string
    {
        return 'beforeWrite';
    }

    public function getFilter(): ?string
    {
        return null;
    }
}

