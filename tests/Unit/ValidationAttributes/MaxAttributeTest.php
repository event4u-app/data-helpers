<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Max;

class MaxStringTestDto extends SimpleDto
{
    public function __construct(
        #[Max(255)]
        public readonly ?string $name = null,
    ) {}
}

class MaxNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Max(120)]
        public readonly ?int $age = null,
    ) {}
}

describe('Max Attribute - Plain PHP Validation', function(): void {
    it('passes when string is within maximum length', function(): void {
        $result = MaxStringTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string equals maximum length', function(): void {
        $result = MaxStringTestDto::validate(['name' => str_repeat('a', 255)]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string exceeds maximum length', function(): void {
        $result = MaxStringTestDto::validate(['name' => str_repeat('a', 256)]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes when number is within maximum', function(): void {
        $result = MaxNumericTestDto::validate(['age' => 100]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when number equals maximum', function(): void {
        $result = MaxNumericTestDto::validate(['age' => 120]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when number exceeds maximum', function(): void {
        $result = MaxNumericTestDto::validate(['age' => 121]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('age'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = MaxStringTestDto::validate(['name' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
