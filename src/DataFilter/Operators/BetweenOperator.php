<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * BETWEEN operator for filtering items.
 *
 * Checks if a field value is between two values (inclusive).
 */
final class BetweenOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'BETWEEN';
    }

    protected function getConfigSchema(): array
    {
        return ['min', 'max'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $min = $context->getValue('min');
        $max = $context->getValue('max');

        if (!is_numeric($actualValue) || !is_numeric($min) || !is_numeric($max)) {
            return false;
        }

        return $actualValue >= $min && $actualValue <= $max;
    }
}
