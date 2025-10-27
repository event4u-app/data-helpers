<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

/**
 * Child DTO extending ParentDto.
 * Should be eligible for FastPath (no attributes added).
 */
class ChildDto extends ParentDto
{
    public function __construct(
        ?string $parentProperty = null,
        public readonly ?string $childProperty = null,
    ) {
        parent::__construct($parentProperty);
    }
}

