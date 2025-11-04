<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextEquals;

/**
 * Alias for WhenContextEquals attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextEquals
 */
class_alias(
    WhenContextEquals::class,
    __NAMESPACE__ . '\WhenContextEquals'
);
