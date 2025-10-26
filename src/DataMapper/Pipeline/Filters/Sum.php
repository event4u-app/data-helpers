<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Sums numeric values in an array.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([Sum::class])->map()->getTarget();
 *   Template: {{ orders.*.amount | sum }}
 */
final class Sum implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_array($value)) {
            return 0;
        }

        $sum = 0;
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $sum += $item;
            }
        }

        return $sum;
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
        return ['sum', 'total'];
    }
}

