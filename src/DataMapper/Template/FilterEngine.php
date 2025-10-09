<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;
use InvalidArgumentException;

/**
 * Applies transformers to values in template expressions using filter syntax.
 *
 * Example: {{ value | trim | upper }}
 *
 * Transformers are registered via TransformerRegistry and can be used
 * in template expressions with their aliases.
 */
final class FilterEngine
{
    /**
     * Apply transformers to a value using filter syntax.
     *
     * @param array<int, string> $filters Transformer aliases to apply
     */
    public static function apply(mixed $value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            $value = self::applyFilter($value, $filter);
        }

        return $value;
    }

    /** Apply a single transformer using its alias. */
    private static function applyFilter(mixed $value, string $filter): mixed
    {
        $filter = trim($filter);

        // Get transformer class from registry
        $transformerClass = TransformerRegistry::get($filter);
        if (null !== $transformerClass) {
            /** @var TransformerInterface $transformer */
            $transformer = new $transformerClass();

            // Create a minimal context for the transformer
            $context = new PairContext('template-expression', 0, '', '', [], []);

            return $transformer->transform($value, $context);
        }

        // Unknown transformer alias - throw exception
        throw new InvalidArgumentException(
            sprintf(
                "Unknown transformer alias '%s'. " .
                "Create a Transformer class with getAliases() method and register it using TransformerRegistry::register().",
                $filter
            )
        );
    }
}
