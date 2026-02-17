<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto;

class TagDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $color = '',
    ) {}
}
