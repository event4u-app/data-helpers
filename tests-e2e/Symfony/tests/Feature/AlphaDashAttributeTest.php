<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaDash;

class SymfonyAlphaDashTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaDash]
        public readonly ?string $slug = null,
    ) {}
}

describe('AlphaDash Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with letters, numbers, dashes and underscores', function(): void {
        $result = SymfonyAlphaDashTestDto::validate(['slug' => 'my-slug_123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with spaces', function(): void {
        $result = SymfonyAlphaDashTestDto::validate(['slug' => 'my slug']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('slug'))->toBeTrue();
    });
});

