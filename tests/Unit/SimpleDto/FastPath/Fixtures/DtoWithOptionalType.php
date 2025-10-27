<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Optional;

/**
 * DTO with Optional wrapper type.
 * Should NOT be eligible for FastPath.
 */
class DtoWithOptionalType extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly Optional|string $email = new Optional(),
    ) {}
}

