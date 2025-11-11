<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Decimal;

class DecimalTestDto extends SimpleDto
{
    public function __construct(
        #[Decimal(10, 2)]
        public readonly ?float $price = null,
    ) {}
}

describe('Decimal Attribute - Plain PHP Validation', function(): void {
    it('passes with valid decimal', function(): void {
        $result = DecimalTestDto::validate(['price' => 99999.99]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with integer within range', function(): void {
        $result = DecimalTestDto::validate(['price' => 12345]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when integer part exceeds max digits', function(): void {
        $result = DecimalTestDto::validate(['price' => 999999999.99]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('price'))->toBeTrue();
    });

    it('fails when decimal part exceeds scale', function(): void {
        $result = DecimalTestDto::validate(['price' => 99.999]);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = DecimalTestDto::validate(['price' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
