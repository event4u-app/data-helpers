<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;

class SymfonyUuidTestDto extends SimpleDto
{
    public function __construct(
        #[Uuid]
        public readonly ?string $id = null,
    ) {}
}

describe('Uuid Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes with valid UUID', function(): void {
        $result = SymfonyUuidTestDto::validate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid UUID', function(): void {
        $result = SymfonyUuidTestDto::validate(['id' => 'not-a-uuid']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('id'))->toBeTrue();
    });
});

