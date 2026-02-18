<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;

class BaseNameDto extends SimpleDto
{
    public function __construct(
        public readonly string $name = '',
    ) {}
}
