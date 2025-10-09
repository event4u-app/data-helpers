<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Trims whitespace from string values.
 *
 * Example:
 *   DataMapper::pipe([TrimStrings::class])->map($source, $target, $mapping);
 *   Template: {{ value | trim }}
 */
final class TrimStrings implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? trim($value) : $value;
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
        return ['trim'];
    }
}
