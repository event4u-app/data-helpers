<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class UserWithRolesDTO extends SimpleDTO
{
    /** @param array<string> $roles */
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $roles = [],
    ) {
    }
}
