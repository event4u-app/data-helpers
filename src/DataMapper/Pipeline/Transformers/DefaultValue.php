<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Returns empty string if value is null.
 *
 * Example:
 *   DataMapper::pipe([DefaultValue::class])->map($source, $target, $mapping);
 *   Template: {{ value | default }}
 */
final class DefaultValue implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return $value ?? '';
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
        return ['default'];
    }
}

