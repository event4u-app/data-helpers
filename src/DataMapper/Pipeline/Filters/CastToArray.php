<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts values to arrays.
 *
 * Converts objects to arrays using (array) cast.
 * Wraps scalar values in an array.
 * Skips null values and existing arrays.
 *
 * Example:
 *   'string' => ['string']
 *   123 => [123]
 *   object => array
 *
 * Usage:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([CastToArray::class])->map()->getTarget();
 */
final class CastToArray implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Already an array
        if (is_array($value)) {
            return $value;
        }

        // Cast to array
        return (array)$value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): string
    {
        return 'array';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['array'];
    }
}
