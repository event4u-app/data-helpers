<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class ProjectAutoCastDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?int $budget = null,
        public readonly ?string $status = null,
    ) {}
}
