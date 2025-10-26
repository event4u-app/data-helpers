<?php

declare(strict_types=1);

namespace Tests\utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class UserWithEmailsDTO extends SimpleDTO
{
    public function __construct(
        public string $name = '',
        public array $emails = [],
    ) {
    }
}
