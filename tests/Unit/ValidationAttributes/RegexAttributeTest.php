<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;

class RegexTestDto extends SimpleDto
{
    public function __construct(
        #[Regex('/^[A-Z]{2}\d{4}$/')]
        public readonly ?string $code = null,
    ) {}
}

describe('Regex Attribute - Plain PHP Validation', function(): void {
    it('passes when value matches pattern', function(): void {
        $result = RegexTestDto::validate(['code' => 'AB1234']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with another matching value', function(): void {
        $result = RegexTestDto::validate(['code' => 'XY9999']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when value does not match pattern', function(): void {
        $result = RegexTestDto::validate(['code' => 'abc123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('code'))->toBeTrue();
    });

    it('fails with incomplete pattern', function(): void {
        $result = RegexTestDto::validate(['code' => 'AB123']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = RegexTestDto::validate(['code' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
