<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaDash;

class LaravelAlphaDashTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaDash]
        public readonly ?string $slug = null,
    ) {}
}

describe('AlphaDash Attribute - Laravel E2E', function(): void {
    it('passes with letters, numbers, dashes and underscores', function(): void {
        $result = LaravelAlphaDashTestDto::validate(['slug' => 'my-slug_123']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with spaces', function(): void {
        $result = LaravelAlphaDashTestDto::validate(['slug' => 'my slug']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('slug'))->toBeTrue();
    });
});

