<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;

class SymfonyBetweenNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Between(18, 120)]
        public readonly ?int $age = null,
    ) {}
}

class SymfonyBetweenStringTestDto extends SimpleDto
{
    public function __construct(
        #[Between(3, 10)]
        public readonly ?string $username = null,
    ) {}
}

describe('Between Attribute - Symfony E2E', function(): void {
    afterEach(function(): void {
        restore_error_handler();
        restore_exception_handler();
    });

    it('passes when numeric value is within range', function(): void {
        $result = SymfonyBetweenNumericTestDto::validate(['age' => 25]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when numeric value is below minimum', function(): void {
        $result = SymfonyBetweenNumericTestDto::validate(['age' => 17]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('age'))->toBeTrue();
    });

    it('fails when numeric value is above maximum', function(): void {
        $result = SymfonyBetweenNumericTestDto::validate(['age' => 121]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('age'))->toBeTrue();
    });

    it('passes when string length is within range', function(): void {
        $result = SymfonyBetweenStringTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string length is below minimum', function(): void {
        $result = SymfonyBetweenStringTestDto::validate(['username' => 'ab']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails when string length is above maximum', function(): void {
        $result = SymfonyBetweenStringTestDto::validate(['username' => '12345678901']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });
});

