<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts numeric values to decimal strings with specified precision.
 *
 * Converts numeric values to decimal strings with 2 decimal places by default.
 * Skips null values and non-numeric strings.
 *
 * Example:
 *   123 => '123.00'
 *   '45.6' => '45.60'
 *   45.678 => '45.68'
 *
 * Usage:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([new CastToDecimal(2)])->map()->getTarget();
 */
final readonly class CastToDecimal implements FilterInterface
{
    public function __construct(
        private int $precision = 2
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Cast to decimal if numeric
        if (is_numeric($value)) {
            return number_format((float)$value, $this->precision, '.', '');
        }

        return $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): string
    {
        return 'decimal';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['decimal'];
    }
}
