<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Joins array elements into a string with comma separator.
 *
 * Example:
 *   DataMapper::pipe([Join::class])->map($source, $target, $mapping);
 *   Template: {{ value | join }}
 */
final class Join implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? implode(', ', $value) : $value;
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
        return ['join'];
    }
}

