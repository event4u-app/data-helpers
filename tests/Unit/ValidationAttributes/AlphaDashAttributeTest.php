<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AlphaDash;

class AlphaDashTestDto extends SimpleDto
{
    public function __construct(
        #[AlphaDash]
        public readonly ?string $slug = null,
    ) {}
}

describe('AlphaDash Attribute - Plain PHP Validation', function(): void {
    it('passes with letters, numbers, dashes and underscores', function(): void {
        $result = AlphaDashTestDto::validate(['slug' => 'my-slug_123']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with only letters', function(): void {
        $result = AlphaDashTestDto::validate(['slug' => 'myslug']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with spaces', function(): void {
        $result = AlphaDashTestDto::validate(['slug' => 'my slug']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('slug'))->toBeTrue();
    });

    it('fails with dots', function(): void {
        $result = AlphaDashTestDto::validate(['slug' => 'my.slug']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = AlphaDashTestDto::validate(['slug' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
