<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;

// Test DTOs
class BetweenNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Between(18, 120)]
        public readonly ?int $age = null,
    ) {}
}

class BetweenStringTestDto extends SimpleDto
{
    public function __construct(
        #[Between(3, 10)]
        public readonly ?string $username = null,
    ) {}
}

class BetweenFloatTestDto extends SimpleDto
{
    public function __construct(
        #[Between(0.0, 100.0)]
        public readonly ?float $percentage = null,
    ) {}
}

describe('Between Attribute - Plain PHP Validation', function(): void {
    describe('Numeric values', function(): void {
        it('passes when value is within range', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 25]);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when value equals minimum', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 18]);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when value equals maximum', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 120]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when value is below minimum', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 17]);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('age'))->toBeTrue();
            expect($result->firstError('age'))->toContain('between 18 and 120');
        });

        it('fails when value is above maximum', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 121]);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('age'))->toBeTrue();
        });

        it('passes when value is null', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => null]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('String length', function(): void {
        it('passes when string length is within range', function(): void {
            $result = BetweenStringTestDto::validate(['username' => 'john']);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when string length equals minimum', function(): void {
            $result = BetweenStringTestDto::validate(['username' => 'joe']);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when string length equals maximum', function(): void {
            $result = BetweenStringTestDto::validate(['username' => '1234567890']);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when string length is below minimum', function(): void {
            $result = BetweenStringTestDto::validate(['username' => 'ab']);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('username'))->toBeTrue();
        });

        it('fails when string length is above maximum', function(): void {
            $result = BetweenStringTestDto::validate(['username' => '12345678901']);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('username'))->toBeTrue();
        });

        it('passes when value is null', function(): void {
            $result = BetweenStringTestDto::validate(['username' => null]);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('Float values', function(): void {
        it('passes when float is within range', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => 50.5]);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when float equals minimum', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => 0.0]);
            expect($result->isValid())->toBeTrue();
        });

        it('passes when float equals maximum', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => 100.0]);
            expect($result->isValid())->toBeTrue();
        });

        it('fails when float is below minimum', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => -0.1]);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('percentage'))->toBeTrue();
        });

        it('fails when float is above maximum', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => 100.1]);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('percentage'))->toBeTrue();
        });
    });

    describe('Edge cases', function(): void {
        it('handles zero correctly', function(): void {
            $result = BetweenFloatTestDto::validate(['percentage' => 0.0]);
            expect($result->isValid())->toBeTrue();
        });

        it('handles empty string', function(): void {
            $result = BetweenStringTestDto::validate(['username' => '']);
            expect($result->isValid())->toBeFalse();
        });

        it('handles multibyte strings correctly', function(): void {
            $result = BetweenStringTestDto::validate(['username' => 'äöü']);
            expect($result->isValid())->toBeTrue();
        });
    });

    describe('DTO creation', function(): void {
        it('creates DTO when validation passes', function(): void {
            $dto = BetweenNumericTestDto::from(['age' => 25]);
            expect($dto->age)->toBe(25);
        });

        it('fails validation when creating DTO with invalid data', function(): void {
            $result = BetweenNumericTestDto::validate(['age' => 17]);
            expect($result->isValid())->toBeFalse();
            expect($result->hasError('age'))->toBeTrue();
        });
    });
});
