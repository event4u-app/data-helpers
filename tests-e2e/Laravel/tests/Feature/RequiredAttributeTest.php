<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

class LaravelRequiredTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        public readonly int $age,
    ) {}
}

describe('Required Attribute - Laravel E2E', function(): void {
    it('passes when all required fields are provided', function(): void {
        $result = LaravelRequiredTestDto::validate([
            'name' => 'John',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when required string is null', function(): void {
        $result = LaravelRequiredTestDto::validate([
            'name' => null,
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('fails when required string is empty', function(): void {
        $result = LaravelRequiredTestDto::validate([
            'name' => '',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes with zero as value', function(): void {
        $result = LaravelRequiredTestDto::validate([
            'name' => 'John',
            'age' => 0,
        ]);
        expect($result->isValid())->toBeTrue();
    });
});

