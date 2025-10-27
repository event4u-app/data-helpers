<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\IntCast;

/**
 * DTO with cast attribute (#[Cast]).
 * Should NOT be eligible for FastPath.
 */
class DtoWithCastAttribute extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        #[Cast(IntCast::class)]
        public readonly ?int $age = null,
    ) {}
}

