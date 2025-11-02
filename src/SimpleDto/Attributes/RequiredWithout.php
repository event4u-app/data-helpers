<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredWithout;

/**
 * Alias for RequiredWithout attribute.
 *
 * @see \event4u\DataHelpers\SimpleDto\Attributes\Validation\RequiredWithout
 */
class_alias(
    RequiredWithout::class,
    __NAMESPACE__ . '\RequiredWithout'
);
