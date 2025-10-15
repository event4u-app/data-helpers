<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * WHERE NOT NULL operator for filtering items.
 *
 * Checks if a field value is NOT null.
 */
final class WhereNotNullOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'WHERE NOT NULL';
    }

    public function getAliases(): array
    {
        return ['IS NOT NULL', 'NOT NULL'];
    }

    protected function getConfigSchema(): array
    {
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        return null !== $actualValue;
    }
}

