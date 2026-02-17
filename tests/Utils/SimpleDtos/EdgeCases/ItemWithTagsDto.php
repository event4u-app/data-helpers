<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;

class ItemWithTagsDto extends SimpleDto
{
    public function __construct(
        public readonly string $name = '',
        /** @var array<string>|null */
        #[DataCollectionOf(TagDto::class)]
        public readonly ?array $tags = null,
    ) {}
}
