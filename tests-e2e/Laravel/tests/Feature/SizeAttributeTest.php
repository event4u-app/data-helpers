<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Size;

class LaravelSizeTestDto extends SimpleDto
{
    public function __construct(
        #[Size(5)]
        public readonly ?string $code = null,
    ) {}
}

describe('Size Attribute - Laravel E2E', function(): void {
    it('passes when string has exact size', function(): void {
        $result = LaravelSizeTestDto::validate(['code' => 'ABCDE']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is too short', function(): void {
        $result = LaravelSizeTestDto::validate(['code' => 'ABCD']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });

    it('fails when string is too long', function(): void {
        $result = LaravelSizeTestDto::validate(['code' => 'ABCDEF']);
        expect($result->isValid())->toBeFalse();
    });
});

