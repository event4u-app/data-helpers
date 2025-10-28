<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

/**
 * DTO with #[AutoCast] attribute.
 * Should NOT be eligible for FastPath.
 */
#[AutoCast]
class DtoWithAutoCast extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
    ) {}
}
