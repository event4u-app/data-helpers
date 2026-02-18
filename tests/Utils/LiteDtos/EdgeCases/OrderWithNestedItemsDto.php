<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto;

class OrderWithNestedItemsDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $orderNumber = '',
        /** @var array<array<string, mixed>>|null */
        public readonly ?array $items = null,
    ) {}
}
