<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;

class OrderWithItemsDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $orderNumber = '',
        /** @var array<array<string, mixed>>|null */
        #[DataCollectionOf(OrderItemDto::class)]
        public readonly ?array $items = null,
    ) {}
}
