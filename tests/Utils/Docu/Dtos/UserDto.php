<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class UserDto extends SimpleDto
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
        public mixed $address = null,
    ) {
    }
}
