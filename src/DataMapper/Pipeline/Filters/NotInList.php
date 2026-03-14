<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;
use InvalidArgumentException;

/**
 * Validates that a value is NOT in a blocked list.
 *
 * Returns the value if valid, or reports an exception and returns null.
 * Supports :optional flag to allow empty/null values without error.
 *
 * Examples:
 *   Template: {{ status | not_in:[DELETED,ARCHIVED] }}              // required (default)
 *   Template: {{ status | not_in:[DELETED,ARCHIVED]:optional }}     // empty/null → null (no error)
 *
 * Pipeline: new NotInList(['DELETED', 'ARCHIVED'])
 * Pipeline: new NotInList(['DELETED', 'ARCHIVED'], optional: true)
 */
final readonly class NotInList implements FilterInterface
{
    /** @param array<int, string> $blockedValues */
    public function __construct(
        private array $blockedValues = [],
        private bool $optional = false,
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        $args = $context->extra();

        // Parse blocked values from filter args or constructor
        $blockedValues = $this->blockedValues;
        $optional = $this->optional;

        if ([] !== $args) {
            // First arg: blocked values as array syntax [VALUE1,VALUE2]
            if (isset($args[0]) && is_string($args[0])) {
                $blockedValues = $this->parseArraySyntax($args[0]);
            }

            // Second arg: optional flag
            if (isset($args[1]) && is_string($args[1])) {
                $optional = in_array(strtolower(trim($args[1])), ['optional', 'not_required'], true);
            }
        }

        // Empty/null handling
        if (null === $value || (is_string($value) && '' === trim($value))) {
            if ($optional) {
                return null;
            }

            $exception = new InvalidArgumentException(
                'Value is required but empty. Blocked values: [' . implode(', ', $blockedValues) . ']'
                . ' (path: "' . ($context->tgtPath() ?? 'unknown') . '")'
            );
            MapperExceptions::handleException($exception);

            return null;
        }

        // Cast value to string for comparison
        $stringValue = (string)$value;

        if (in_array($stringValue, $blockedValues, true)) {
            $exception = new InvalidArgumentException(
                'Value "' . $stringValue . '" is in blocked list: [' . implode(', ', $blockedValues) . ']'
                . ' (path: "' . ($context->tgtPath() ?? 'unknown') . '")'
            );
            MapperExceptions::handleException($exception);

            return null;
        }

        return $value;
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
        return ['not_in', 'not_in_list'];
    }

    /**
     * Parse array syntax: [VALUE1,VALUE2,VALUE3] into ['VALUE1', 'VALUE2', 'VALUE3']
     *
     * @return array<int, string>
     */
    private function parseArraySyntax(string $str): array
    {
        if (str_starts_with($str, '[') && str_ends_with($str, ']')) {
            $str = trim($str, '[]');
        }

        if ('' === $str) {
            return [];
        }

        $items = explode(',', $str);

        return array_map('trim', $items);
    }
}
