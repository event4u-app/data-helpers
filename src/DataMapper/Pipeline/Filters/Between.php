<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Checks if a numeric value is between a minimum and maximum (inclusive by default).
 *
 * By default uses inclusive boundaries (>= and <=), like Laravel, MySQL, etc.
 * Set $strict to true for exclusive boundaries (> and <).
 *
 * Examples:
 *   Pipeline: new Between(3, 5)                 // Inclusive: 3, 4, 5 are valid
 *   Pipeline: new Between(3, 5, true)           // Strict: only 4 is valid
 *   Template: {{ value | between:3:5 }}         // Inclusive (default)
 *   Template: {{ value | between:3:5:strict }}  // Strict mode
 *   Template: {{ value | between:0:100 }}       // Check if between 0 and 100
 *
 * Note: For clamping values, use the 'clamp' alias or Clamp transformer.
 */
final readonly class Between implements FilterInterface
{
    /**
     * @param float|null $min Minimum value (null = no minimum)
     * @param float|null $max Maximum value (null = no maximum)
     * @param bool $strict Use strict comparison (> and <) instead of inclusive (>= and <=)
     */
    public function __construct(
        private ?float $min = null,
        private ?float $max = null,
        private bool $strict = false,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_numeric($value)) {
            return false;
        }

        // Get min, max, and strict from context args (from filter syntax) or constructor
        $args = $context->extra();
        $strict = $this->strict;

        if (count($args) >= 2) {
            $min = is_numeric($args[0]) ? (float)$args[0] : null;
            $max = is_numeric($args[1]) ? (float)$args[1] : null;

            // Third argument is strict mode (accepts "strict" or boolean)
            if (isset($args[2]) && is_string($args[2])) {
                $strict = 'strict' === strtolower(trim($args[2])) || filter_var($args[2], FILTER_VALIDATE_BOOLEAN);
            }
        } else {
            $min = $this->min;
            $max = $this->max;
        }

        if (null === $min || null === $max) {
            return false;
        }

        $numValue = (float)$value;

        // Inclusive by default (like Laravel, MySQL, etc.)
        if ($strict) {
            // Strict: > and <
            return $numValue > $min && $numValue < $max;
        }

        // Inclusive: >= and <=
        return $numValue >= $min && $numValue <= $max;
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
        return ['between'];
    }
}
