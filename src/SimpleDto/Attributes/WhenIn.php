<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenIn;

// Alias for backward compatibility
class_alias(
    WhenIn::class,
    __NAMESPACE__ . '\WhenIn'
);
