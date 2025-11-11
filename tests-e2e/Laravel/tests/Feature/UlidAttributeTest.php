<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Ulid;

class LaravelUlidTestDto extends SimpleDto
{
    public function __construct(
        #[Ulid]
        public readonly ?string $id = null,
    ) {}
}

describe('Ulid Attribute - Laravel E2E', function(): void {
    it('passes with valid ULID', function(): void {
        $result = LaravelUlidTestDto::validate(['id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid ULID', function(): void {
        $result = LaravelUlidTestDto::validate(['id' => 'not-a-ulid']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('id'))->toBeTrue();
    });
});

