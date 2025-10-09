<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Capitalizes the first character of each word in a string.
 *
 * Example:
 *   DataMapper::pipe([Ucwords::class])->map($source, $target, $mapping);
 *   Template: {{ value | ucwords }}
 */
final class Ucwords implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? ucwords($value) : $value;
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
        return ['ucwords'];
    }
}

