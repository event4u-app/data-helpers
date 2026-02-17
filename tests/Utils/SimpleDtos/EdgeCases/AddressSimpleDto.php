<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use Tests\Utils\Models\Address;

#[HasModel(Address::class)]
class AddressSimpleDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $company_id = null,
        public readonly string $street = '',
        public readonly string $city = '',
        public readonly string $country = '',
    ) {}
}
