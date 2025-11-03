<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextNotNull;

/**
 * Alias for WhenContextNotNull attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContextNotNull
 */
class_alias(
    WhenContextNotNull::class,
    __NAMESPACE__ . '\WhenContextNotNull'
);
