<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenFalse;

// Alias for backward compatibility
class_alias(
    WhenFalse::class,
    __NAMESPACE__ . '\WhenFalse'
);
