<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Positive;

class LaravelPositiveTestDto extends SimpleDto
{
    public function __construct(
        #[Positive]
        public readonly ?int $count = null,
    ) {}
}

describe('Positive Attribute - Laravel E2E', function(): void {
    it('passes with positive integer', function(): void {
        $result = LaravelPositiveTestDto::validate(['count' => 42]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with zero', function(): void {
        $result = LaravelPositiveTestDto::validate(['count' => 0]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('count'))->toBeTrue();
    });
});

