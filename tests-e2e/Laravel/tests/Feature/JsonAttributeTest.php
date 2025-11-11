<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Json;

class LaravelJsonTestDto extends SimpleDto
{
    public function __construct(
        #[Json]
        public readonly ?string $data = null,
    ) {}
}

describe('Json Attribute - Laravel E2E', function(): void {
    it('passes with valid JSON', function(): void {
        $result = LaravelJsonTestDto::validate(['data' => '{"name":"John"}']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid JSON', function(): void {
        $result = LaravelJsonTestDto::validate(['data' => '{invalid}']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('data'))->toBeTrue();
    });
});

