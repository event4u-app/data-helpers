<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

/**
 * Marker interface for filters whose arguments may reference a source path
 * instead of a literal value.
 *
 * When a filter implements this interface, FilterEngine resolves each
 * non-literal argument (not numeric, not a true/false/null keyword) against
 * the mapping sources before passing it to the filter. This allows
 * expressions like {{ order.minutes | divide:order.divisor }} where the
 * second operand is read from the data source instead of being a fixed value.
 *
 * Literal arguments (numeric or true/false/null keywords) are passed through
 * unchanged, so {{ value | multiply:60 }} keeps working without any source.
 */
interface ResolvesSourceArguments
{
}
