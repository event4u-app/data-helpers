<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;

class TagDto extends SimpleDto
{
    public function __construct(
        public readonly string $name = '',
    ) {}
}
