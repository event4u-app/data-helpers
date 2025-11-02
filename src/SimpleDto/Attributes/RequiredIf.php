<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredIf;

/**
 * Alias for RequiredIf attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredIf
 */
class_alias(
    RequiredIf::class,
    __NAMESPACE__ . '\RequiredIf'
);
