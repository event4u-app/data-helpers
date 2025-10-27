<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * DTO with nested DTO.
 * Should be eligible for FastPath (nested DTOs are handled recursively).
 */
class DtoWithNestedDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?SimpleDtoForFastPath $nested = null,
    ) {}
}

