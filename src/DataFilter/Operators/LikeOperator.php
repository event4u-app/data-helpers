<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * LIKE operator for pattern matching.
 *
 * Supports SQL-style wildcards (% for any characters, _ for single character).
 */
final class LikeOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'LIKE';
    }

    protected function getConfigSchema(): array
    {
        return ['pattern'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $pattern = $context->getValue('pattern');

        if (!is_string($actualValue) || !is_string($pattern)) {
            return false;
        }

        // Convert SQL LIKE pattern to regex
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';

        return 1 === preg_match($regex, $actualValue);
    }
}

