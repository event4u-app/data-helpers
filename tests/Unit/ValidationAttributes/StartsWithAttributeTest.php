<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\StartsWith;

class StartsWithTestDto extends SimpleDto
{
    public function __construct(
        #[StartsWith(['http://', 'https://'])]
        public readonly ?string $url = null,
    ) {}
}

describe('StartsWith Attribute - Plain PHP Validation', function(): void {
    it('passes when string starts with first prefix', function(): void {
        $result = StartsWithTestDto::validate(['url' => 'http://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string starts with second prefix', function(): void {
        $result = StartsWithTestDto::validate(['url' => 'https://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string does not start with any prefix', function(): void {
        $result = StartsWithTestDto::validate(['url' => 'ftp://example.com']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('url'))->toBeTrue();
    });

    it('fails with no prefix', function(): void {
        $result = StartsWithTestDto::validate(['url' => 'example.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = StartsWithTestDto::validate(['url' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
