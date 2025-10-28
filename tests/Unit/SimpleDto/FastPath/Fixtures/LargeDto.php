<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto\FastPath\Fixtures;

use event4u\DataHelpers\SimpleDto;

/**
 * Large DTO with 50 properties for performance testing.
 * Should be eligible for FastPath.
 */
class LargeDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $prop1 = null,
        public readonly ?string $prop2 = null,
        public readonly ?string $prop3 = null,
        public readonly ?string $prop4 = null,
        public readonly ?string $prop5 = null,
        public readonly ?string $prop6 = null,
        public readonly ?string $prop7 = null,
        public readonly ?string $prop8 = null,
        public readonly ?string $prop9 = null,
        public readonly ?string $prop10 = null,
        public readonly ?string $prop11 = null,
        public readonly ?string $prop12 = null,
        public readonly ?string $prop13 = null,
        public readonly ?string $prop14 = null,
        public readonly ?string $prop15 = null,
        public readonly ?string $prop16 = null,
        public readonly ?string $prop17 = null,
        public readonly ?string $prop18 = null,
        public readonly ?string $prop19 = null,
        public readonly ?string $prop20 = null,
        public readonly ?string $prop21 = null,
        public readonly ?string $prop22 = null,
        public readonly ?string $prop23 = null,
        public readonly ?string $prop24 = null,
        public readonly ?string $prop25 = null,
        public readonly ?string $prop26 = null,
        public readonly ?string $prop27 = null,
        public readonly ?string $prop28 = null,
        public readonly ?string $prop29 = null,
        public readonly ?string $prop30 = null,
        public readonly ?string $prop31 = null,
        public readonly ?string $prop32 = null,
        public readonly ?string $prop33 = null,
        public readonly ?string $prop34 = null,
        public readonly ?string $prop35 = null,
        public readonly ?string $prop36 = null,
        public readonly ?string $prop37 = null,
        public readonly ?string $prop38 = null,
        public readonly ?string $prop39 = null,
        public readonly ?string $prop40 = null,
        public readonly ?string $prop41 = null,
        public readonly ?string $prop42 = null,
        public readonly ?string $prop43 = null,
        public readonly ?string $prop44 = null,
        public readonly ?string $prop45 = null,
        public readonly ?string $prop46 = null,
        public readonly ?string $prop47 = null,
        public readonly ?string $prop48 = null,
        public readonly ?string $prop49 = null,
        public readonly ?string $prop50 = null,
    ) {}
}
