<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Clamps a numeric value between a minimum and maximum.
 *
 * Limits the value to the specified range by returning min if value < min,
 * max if value > max, or the value itself if it's within range.
 *
 * Examples:
 *   Pipeline: new Clamp(0, 100)
 *   Template: {{ value | clamp:0:100 }}    // Clamps between 0 and 100
 *   Template: {{ value | clamp:1:10 }}     // Clamps between 1 and 10
 *   Template: {{ value | clamp:-5:5 }}     // Clamps between -5 and 5
 *
 * Example behavior:
 *   clamp(2, 3, 5) returns 3   (2 < 3, so return min)
 *   clamp(4, 3, 5) returns 4   (4 is within range)
 *   clamp(6, 3, 5) returns 5   (6 > 5, so return max)
 */
final readonly class Clamp implements TransformerInterface
{
    /**
     * @param float|null $min Minimum value (null = no minimum)
     * @param float|null $max Maximum value (null = no maximum)
     */
    public function __construct(
        private ?float $min = null,
        private ?float $max = null,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        // Get min and max from context args (from filter syntax) or constructor
        $args = $context->extra();

        if (count($args) >= 2) {
            $min = is_numeric($args[0]) ? (float)$args[0] : null;
            $max = is_numeric($args[1]) ? (float)$args[1] : null;
        } else {
            $min = $this->min;
            $max = $this->max;
        }

        if (null === $min || null === $max) {
            return $value;
        }

        $numValue = (float)$value;

        return max($min, min($max, $numValue));
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['clamp'];
    }
}

