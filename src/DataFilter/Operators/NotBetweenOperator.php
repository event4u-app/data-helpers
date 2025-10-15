<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * NOT BETWEEN operator for filtering items.
 *
 * Checks if a field value is NOT between two values.
 */
final class NotBetweenOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'NOT BETWEEN';
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

        // Return true if NOT between
        return !($actualValue >= $min && $actualValue <= $max);
    }
}

