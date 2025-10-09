<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Returns the first element of an array.
 *
 * Example:
 *   DataMapper::pipe([First::class])->map($source, $target, $mapping);
 *   Template: {{ value | first }}
 */
final class First implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? reset($value) : $value;
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
        return ['first'];
    }
}

