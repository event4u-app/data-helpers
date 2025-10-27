<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class UserWithEmailsDto extends SimpleDto
{
    /** @param array<string> $emails */
    public function __construct(
        public string $name = '',
        public array $emails = [],
    ) {
    }
}
