<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenNull;

// Alias for backward compatibility
class_alias(
    WhenNull::class,
    __NAMESPACE__ . '\WhenNull'
);
