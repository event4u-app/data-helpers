<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\Nullable;

/**
 * Alias for Nullable attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\Nullable
 */
class_alias(
    Nullable::class,
    __NAMESPACE__ . '\Nullable'
);
