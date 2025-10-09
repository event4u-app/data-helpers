<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Encodes a value as JSON.
 *
 * Example:
 *   DataMapper::pipe([JsonEncode::class])->map($source, $target, $mapping);
 *   Template: {{ value | json }}
 */
final class JsonEncode implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return json_encode($value);
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
        return ['json'];
    }
}

