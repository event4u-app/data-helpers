<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Encodes a value as JSON.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([JsonEncode::class])->map()->getTarget();
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
        return DataMapperHook::BeforeTransform->value;
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
