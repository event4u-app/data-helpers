<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Decimal;

class SymfonyDecimalTestDto extends SimpleDto
{
    public function __construct(
        #[Decimal(10, 2)]
        public readonly ?float $price = null,
    ) {}
}

describe('Decimal Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid decimal', function(): void {
        $result = SymfonyDecimalTestDto::validate(['price' => 99999.99]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when decimal part exceeds scale', function(): void {
        $result = SymfonyDecimalTestDto::validate(['price' => 99.999]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('price'))->toBeTrue();
    });
});

