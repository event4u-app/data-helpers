<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * WHERE NULL operator for filtering items.
 *
 * Checks if a field value is null.
 */
final class WhereNullOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'WHERE NULL';
    }

    public function getAliases(): array
    {
        return ['IS NULL'];
    }

    protected function getConfigSchema(): array
    {
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        return null === $actualValue;
    }
}
