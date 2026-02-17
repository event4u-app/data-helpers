<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use Tests\Utils\Models\Address;

#[HasModel(Address::class)]
class AddressLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $company_id = null,
        public readonly string $street = '',
        public readonly string $city = '',
        public readonly string $country = '',
    ) {}
}
