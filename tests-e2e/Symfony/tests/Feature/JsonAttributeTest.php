<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Json;

class SymfonyJsonTestDto extends SimpleDto
{
    public function __construct(
        #[Json]
        public readonly ?string $data = null,
    ) {}
}

describe('Json Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid JSON', function(): void {
        $result = SymfonyJsonTestDto::validate(['data' => '{"name":"John"}']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid JSON', function(): void {
        $result = SymfonyJsonTestDto::validate(['data' => '{invalid}']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('data'))->toBeTrue();
    });
});

