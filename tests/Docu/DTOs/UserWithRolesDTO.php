<?php

declare(strict_types=1);

namespace Tests\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class UserWithRolesDTO extends SimpleDTO
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $roles = [],
    ) {
    }
}

