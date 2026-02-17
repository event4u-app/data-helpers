<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;

class OrderWithNestedItemsDto extends SimpleDto
{
    public function __construct(
        public readonly string $orderNumber = '',
        /** @var array<array<string, mixed>>|null */
        #[DataCollectionOf(ItemWithTagsDto::class)]
        public readonly ?array $items = null,
    ) {}
}
