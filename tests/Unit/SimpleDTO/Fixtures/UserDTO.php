<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDTO\Fixtures;

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
    ) {}
}
