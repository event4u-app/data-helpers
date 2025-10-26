<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * EXCEPT Operator - Exclude specific fields from items.
 *
 * Configuration: ['field1', 'field2', ...]
 *
 * Example:
 *   EXCEPT => ['password', 'token']
 *   Result: All fields except 'password' and 'token' are kept
 */
final class ExceptOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'EXCEPT';
    }

    protected function getConfigSchema(): array
    {
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        return true;
    }

    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_array($config)) {
            return $items;
        }

        $excludeFields = $config;

        return array_map(function($item) use ($excludeFields) {
            if (!is_array($item)) {
                return $item;
            }

            $result = $item;
            foreach ($excludeFields as $field) {
                if (is_string($field) || is_int($field)) {
                    unset($result[$field]);
                }
            }

            return $result;
        }, $items);
    }
}
