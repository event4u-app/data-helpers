<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Alpha;

class AlphaTestDto extends SimpleDto
{
    public function __construct(
        #[Alpha]
        public readonly ?string $name = null,
    ) {}
}

describe('Alpha Attribute - Plain PHP Validation', function(): void {
    it('passes with only letters', function(): void {
        $result = AlphaTestDto::validate(['name' => 'JohnDoe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with numbers', function(): void {
        $result = AlphaTestDto::validate(['name' => 'John123']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('fails with special characters', function(): void {
        $result = AlphaTestDto::validate(['name' => 'John-Doe']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = AlphaTestDto::validate(['name' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
