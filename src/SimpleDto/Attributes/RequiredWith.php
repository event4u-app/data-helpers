<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredWith;

/**
 * Alias for RequiredWith attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredWith
 */
class_alias(
    RequiredWith::class,
    __NAMESPACE__ . '\RequiredWith'
);
