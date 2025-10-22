<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * WHERE IN operator for filtering items.
 *
 * Checks if a field value is in an array of values.
 */
final class WhereInOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'WHERE IN';
    }

    public function getAliases(): array
    {
        return ['IN'];
    }

    protected function getConfigSchema(): array
    {
        return ['values'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $values = $context->getValue('values');

        if (!is_array($values)) {
            return false;
        }

        return in_array($actualValue, $values, true);
    }
}
