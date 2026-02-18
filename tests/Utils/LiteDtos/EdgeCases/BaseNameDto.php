<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto;

class BaseNameDto extends LiteDto
{
    public function __construct(
        public readonly string $name = '',
    ) {}
}
