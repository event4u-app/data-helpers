<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\EndsWith;

class SymfonyEndsWithTestDto extends SimpleDto
{
    public function __construct(
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly ?string $domain = null,
    ) {}
}

describe('EndsWith Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when string ends with suffix', function(): void {
        $result = SymfonyEndsWithTestDto::validate(['domain' => 'example.com']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string does not end with suffix', function(): void {
        $result = SymfonyEndsWithTestDto::validate(['domain' => 'example.de']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('domain'))->toBeTrue();
    });
});

