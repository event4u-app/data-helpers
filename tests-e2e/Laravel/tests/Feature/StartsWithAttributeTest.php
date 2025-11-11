<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\StartsWith;

class LaravelStartsWithTestDto extends SimpleDto
{
    public function __construct(
        #[StartsWith(['http://', 'https://'])]
        public readonly ?string $url = null,
    ) {}
}

describe('StartsWith Attribute - Laravel E2E', function(): void {
    it('passes when string starts with prefix', function(): void {
        $result = LaravelStartsWithTestDto::validate(['url' => 'https://example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string does not start with prefix', function(): void {
        $result = LaravelStartsWithTestDto::validate(['url' => 'ftp://example.com']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('url'))->toBeTrue();
    });
});

