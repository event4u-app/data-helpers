<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenNotNull;

// Alias for backward compatibility
class_alias(
    WhenNotNull::class,
    __NAMESPACE__ . '\WhenNotNull'
);
