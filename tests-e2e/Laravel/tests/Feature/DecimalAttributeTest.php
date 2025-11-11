<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Decimal;

class LaravelDecimalTestDto extends SimpleDto
{
    public function __construct(
        #[Decimal(10, 2)]
        public readonly ?float $price = null,
    ) {}
}

describe('Decimal Attribute - Laravel E2E', function(): void {
    it('passes with valid decimal', function(): void {
        $result = LaravelDecimalTestDto::validate(['price' => 99999.99]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when decimal part exceeds scale', function(): void {
        $result = LaravelDecimalTestDto::validate(['price' => 99.999]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('price'))->toBeTrue();
    });
});

