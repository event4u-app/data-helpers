<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Strips HTML and PHP tags from string values.
 *
 * Useful for sanitizing user input.
 *
 * Example:
 *   DataMapper::pipe([StripTags::class])->map($source, $target, $mapping);
 */
final class StripTags implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? strip_tags($value) : $value;
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }
}

