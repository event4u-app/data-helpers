<?php

declare(strict_types=1);

namespace Tests\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class EmailDTO extends SimpleDTO
{
    public function __construct(
        public string $email = '',
        public string $type = '',
        public bool $verified = false,
    ) {
    }
}

