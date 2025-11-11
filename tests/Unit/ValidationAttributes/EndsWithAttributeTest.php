<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\EndsWith;

class EndsWithTestDto extends SimpleDto
{
    public function __construct(
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly ?string $domain = null,
    ) {}
}

describe('EndsWith Attribute - Plain PHP Validation', function(): void {
    it('passes when string ends with first suffix', function(): void {
        $result = EndsWithTestDto::validate(['domain' => 'example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string ends with second suffix', function(): void {
        $result = EndsWithTestDto::validate(['domain' => 'example.org']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string does not end with any suffix', function(): void {
        $result = EndsWithTestDto::validate(['domain' => 'example.de']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('domain'))->toBeTrue();
    });

    it('fails with wrong suffix', function(): void {
        $result = EndsWithTestDto::validate(['domain' => 'example.co.uk']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = EndsWithTestDto::validate(['domain' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
