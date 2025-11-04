<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\Sometimes;

/**
 * Alias for Sometimes attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\Sometimes
 */
class_alias(
    Sometimes::class,
    __NAMESPACE__ . '\Sometimes'
);
