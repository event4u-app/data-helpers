<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredUnless;

/**
 * Alias for RequiredUnless attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredUnless
 */
class_alias(
    RequiredUnless::class,
    __NAMESPACE__ . '\RequiredUnless'
);
