<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use Tests\Utils\Models\Profile;

#[HasModel(Profile::class)]
class ProfileLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly ?string $bio = null,
        public readonly ?string $website = null,
        public readonly ?int $score = null,
    ) {}
}
