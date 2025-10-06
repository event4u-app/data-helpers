<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Converts empty strings to null.
 *
 * Useful for database operations where empty strings should be stored as NULL.
 *
 * Example:
 *   DataMapper::pipe([ConvertEmptyToNull::class])->map($source, $target, $mapping);
 */
final class ConvertEmptyToNull implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return '' === $value ? null : $value;
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

