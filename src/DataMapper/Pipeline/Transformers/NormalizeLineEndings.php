<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Normalizes line endings to Unix style (\n).
 *
 * Converts Windows (\r\n) and Mac (\r) line endings to Unix (\n).
 *
 * Example:
 *   DataMapper::pipe([NormalizeLineEndings::class])->map($source, $target, $mapping);
 */
final class NormalizeLineEndings implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Convert Windows and Mac line endings to Unix
        return str_replace(["\r\n", "\r"], "\n", $value);
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

