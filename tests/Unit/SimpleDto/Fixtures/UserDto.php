<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\Fixtures;

use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
    ) {}
}
