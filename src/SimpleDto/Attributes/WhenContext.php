<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContext;

/**
 * Alias for WhenContext attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenContext
 */
class_alias(
    WhenContext::class,
    __NAMESPACE__ . '\WhenContext'
);
