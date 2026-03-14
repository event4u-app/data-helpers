<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;
use InvalidArgumentException;

/**
 * Validates that a value is in an allowed list.
 *
 * Returns the value if valid, or reports an exception and returns null.
 * Supports :optional flag to allow empty/null values without error.
 *
 * Examples:
 *   Template: {{ type | in:[VEHICLE,ORDER,PROJECT] }}              // required (default)
 *   Template: {{ type | in:[VEHICLE,ORDER,PROJECT]:optional }}     // empty/null → null (no error)
 *   Template: {{ type | in:[active,inactive] }}                    // case-sensitive
 *
 * Pipeline: new InList(['VEHICLE', 'ORDER', 'PROJECT'])
 * Pipeline: new InList(['VEHICLE', 'ORDER'], optional: true)
 */
final readonly class InList implements FilterInterface
{
    /** @param array<int, string> $allowedValues */
    public function __construct(
        private array $allowedValues = [],
        private bool $optional = false,
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        $args = $context->extra();

        // Parse allowed values from filter args or constructor
        $allowedValues = $this->allowedValues;
        $optional = $this->optional;

        if ([] !== $args) {
            // First arg: allowed values as array syntax [VALUE1,VALUE2]
            if (isset($args[0]) && is_string($args[0])) {
                $allowedValues = $this->parseArraySyntax($args[0]);
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
                'Value is required but empty. Allowed values: [' . implode(', ', $allowedValues) . ']'
                . ' (path: "' . ($context->tgtPath() ?? 'unknown') . '")'
            );
            MapperExceptions::handleException($exception);

            return null;
        }

        // Cast value to string for comparison
        $stringValue = (string)$value;

        if (!in_array($stringValue, $allowedValues, true)) {
            $exception = new InvalidArgumentException(
                'Value "' . $stringValue . '" is not in allowed list: [' . implode(', ', $allowedValues) . ']'
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
        return ['in', 'in_list'];
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
