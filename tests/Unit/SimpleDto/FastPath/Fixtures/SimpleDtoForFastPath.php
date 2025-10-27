<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * Simple DTO without any attributes or special features.
 * Should be eligible for FastPath.
 */
class SimpleDtoForFastPath extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
        public readonly ?string $email = null,
    ) {}
}

