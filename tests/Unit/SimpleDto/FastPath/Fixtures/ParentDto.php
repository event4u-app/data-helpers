<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * Parent DTO without attributes.
 * Should be eligible for FastPath.
 */
class ParentDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $parentProperty = null,
    ) {}
}
