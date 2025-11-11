<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Min;

class MinStringTestDto extends SimpleDto
{
    public function __construct(
        #[Min(3)]
        public readonly ?string $name = null,
    ) {}
}

class MinNumericTestDto extends SimpleDto
{
    public function __construct(
        #[Min(18)]
        public readonly ?int $age = null,
    ) {}
}

describe('Min Attribute - Plain PHP Validation', function(): void {
    it('passes when string meets minimum length', function(): void {
        $result = MinStringTestDto::validate(['name' => 'John']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when string equals minimum length', function(): void {
        $result = MinStringTestDto::validate(['name' => 'Joe']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when string is below minimum length', function(): void {
        $result = MinStringTestDto::validate(['name' => 'Jo']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('name'))->toBeTrue();
    });

    it('passes when number meets minimum', function(): void {
        $result = MinNumericTestDto::validate(['age' => 25]);
        expect($result->isValid())->toBeTrue();
    });

    it('passes when number equals minimum', function(): void {
        $result = MinNumericTestDto::validate(['age' => 18]);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when number is below minimum', function(): void {
        $result = MinNumericTestDto::validate(['age' => 17]);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('age'))->toBeTrue();
    });

    it('passes with null', function(): void {
        $result = MinStringTestDto::validate(['name' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
