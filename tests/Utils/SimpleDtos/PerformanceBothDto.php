<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoAttributes, NoCasts]
class PerformanceBothDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}
