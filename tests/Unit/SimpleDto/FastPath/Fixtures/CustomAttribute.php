<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use Attribute;

/**
 * Custom attribute for testing FastPath detection.
 * This is in the TEST namespace, NOT in event4u\DataHelpers\SimpleDto\Attributes\
 * so it will NOT be detected by FastPath (which is correct for testing).
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
class CustomAttribute
{
    public function __construct(
        public readonly string $value = '',
    ) {}
}

