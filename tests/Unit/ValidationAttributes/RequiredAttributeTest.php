<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

class RequiredTestDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        public readonly int $age,
    ) {}
}

describe('Required Attribute - Plain PHP Validation', function(): void {
    it('passes when all required fields are provided', function(): void {
        $result = RequiredTestDto::validate([
            'name' => 'John',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when required string is null', function(): void {
        $result = RequiredTestDto::validate([
            'name' => null,
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('fails when required string is empty', function(): void {
        $result = RequiredTestDto::validate([
            'name' => '',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('fails when required field is missing', function(): void {
        $result = RequiredTestDto::validate([
            'age' => 25,
        ]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes with zero as value', function(): void {
        $result = RequiredTestDto::validate([
            'name' => 'John',
            'age' => 0,
        ]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with string zero', function(): void {
        $result = RequiredTestDto::validate([
            'name' => '0',
            'age' => 25,
        ]);
        expect($result->isValid())->toBeTrue();
    });
});
