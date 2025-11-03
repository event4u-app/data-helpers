<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Conditional\WhenTrue;

// Alias for backward compatibility
class_alias(
    WhenTrue::class,
    __NAMESPACE__ . '\WhenTrue'
);
