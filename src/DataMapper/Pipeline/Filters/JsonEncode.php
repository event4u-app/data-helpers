<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Encodes a value as JSON.
 *
 * Example:
 *   DataMapper::pipe([JsonEncode::class])->map($source, $target, $mapping);
 *   Template: {{ value | json }}
 */
final class JsonEncode implements FilterInterface
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

