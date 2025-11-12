<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts numeric values to floats.
 *
 * When used with pipe syntax (|float), always casts numeric values to floats.
 * Skips null values and non-numeric strings.
 *
 * Example:
 *   Template: ['result' => 'value|float']
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([CastToFloat::class])->map()->getTarget();
 */
final class CastToFloat implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Cast to float if numeric
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): string
    {
        return 'float';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['float'];
    }
}
