<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use Tests\Utils\Models\Profile;

#[HasModel(Profile::class)]
class ProfileSimpleDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly ?string $bio = null,
        public readonly ?string $website = null,
        public readonly ?int $score = null,
    ) {}
}
