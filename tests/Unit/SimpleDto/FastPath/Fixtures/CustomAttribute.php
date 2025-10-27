<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;

/**
 * Custom attribute for testing FastPath detection.
 * This is in the correct namespace (event4u\DataHelpers\SimpleDto\Attributes\)
 * so it WILL be detected by FastPath.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
class CustomAttribute
{
    public function __construct(
        public readonly string $value = '',
    ) {}
}

