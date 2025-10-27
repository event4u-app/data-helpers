<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class EmailDto extends SimpleDto
{
    public function __construct(
        public string $email = '',
        public string $type = '',
        public bool $verified = false,
    ) {
    }
}
