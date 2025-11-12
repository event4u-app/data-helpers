<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts values to strings.
 *
 * Converts scalar values (int, float, bool) to strings.
 * Skips null values, arrays, and objects.
 *
 * Example:
 *   'int' => 'string'
 *   'float' => 'string'
 *   'bool' => 'string'
 *
 * Usage:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([CastToString::class])->map()->getTarget();
 */
final class CastToString implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Only cast scalar values
        if (is_scalar($value)) {
            return (string)$value;
        }

        return $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): string
    {
        return 'string';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['string'];
    }
}
