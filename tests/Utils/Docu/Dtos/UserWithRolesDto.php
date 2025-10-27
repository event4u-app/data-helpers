<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class UserWithRolesDto extends SimpleDto
{
    /** @param array<string> $roles */
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $roles = [],
    ) {
    }
}
