<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

class SymfonyRequiredTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        public readonly int $age,
    ) {}
}

describe('Required Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when all required fields are provided', function(): void {
        $result = SymfonyRequiredTestDto::validate([
            'name' => 'John',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when required string is null', function(): void {
        $result = SymfonyRequiredTestDto::validate([
            'name' => null,
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('fails when required string is empty', function(): void {
        $result = SymfonyRequiredTestDto::validate([
            'name' => '',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes with zero as value', function(): void {
        $result = SymfonyRequiredTestDto::validate([
            'name' => 'John',
            'age' => 0,
        ]);
        expect($result->isValid())->toBeTrue();
    });
});

