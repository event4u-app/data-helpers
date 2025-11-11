<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NotIn;

class NotInTestDto extends SimpleDto
{
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public readonly ?string $username = null,
    ) {}
}

describe('NotIn Attribute - Plain PHP Validation', function(): void {
    it('passes when value is not in forbidden list', function(): void {
        $result = NotInTestDto::validate(['username' => 'john']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails when value is in forbidden list', function(): void {
        $result = NotInTestDto::validate(['username' => 'admin']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('username'))->toBeTrue();
    });

    it('fails with another forbidden value', function(): void {
        $result = NotInTestDto::validate(['username' => 'root']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = NotInTestDto::validate(['username' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
