<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\CustomAttribute;

/**
 * DTO with custom attribute (in correct namespace).
 * Should NOT be eligible for FastPath.
 */
class DtoWithCustomAttribute extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        #[CustomAttribute('test')]
        public readonly ?string $value = null,
    ) {}
}

