<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with custom attribute (NOT in event4u\DataHelpers\SimpleDto\Attributes\ namespace).
 * Should be eligible for FastPath (custom attribute is not detected).
 */
class DtoWithCustomAttribute extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        #[CustomAttribute('test')]
        public readonly ?string $value = null,
    ) {}
}

