<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenEquals;

// Alias for backward compatibility
class_alias(
    WhenEquals::class,
    __NAMESPACE__ . '\WhenEquals'
);
