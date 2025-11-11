<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Url;

class UrlTestDto extends SimpleDto
{
    public function __construct(
        #[Url]
        public readonly ?string $website = null,
    ) {}
}

describe('Url Attribute - Plain PHP Validation', function(): void {
    it('passes with valid HTTPS URL', function(): void {
        $result = UrlTestDto::validate(['website' => 'https://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid HTTP URL', function(): void {
        $result = UrlTestDto::validate(['website' => 'http://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid FTP URL', function(): void {
        $result = UrlTestDto::validate(['website' => 'ftp://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid URL', function(): void {
        $result = UrlTestDto::validate(['website' => 'invalid-url']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('website'))->toBeTrue();
    });

    it('fails with missing protocol', function(): void {
        $result = UrlTestDto::validate(['website' => 'example.com']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = UrlTestDto::validate(['website' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
