<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Size;

class SymfonySizeTestDto extends SimpleDto
{
    public function __construct(
        #[Size(5)]
        public readonly ?string $code = null,
    ) {}
}

describe('Size Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when string has exact size', function(): void {
        $result = SymfonySizeTestDto::validate(['code' => 'ABCDE']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is too short', function(): void {
        $result = SymfonySizeTestDto::validate(['code' => 'ABCD']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });

    it('fails when string is too long', function(): void {
        $result = SymfonySizeTestDto::validate(['code' => 'ABCDEF']);
        expect($result->isValid())->toBeFalse();
    });
});

