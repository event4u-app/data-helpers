<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextIn;

/**
 * Alias for WhenContextIn attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextIn
 */
class_alias(
    WhenContextIn::class,
    __NAMESPACE__ . '\WhenContextIn'
);
