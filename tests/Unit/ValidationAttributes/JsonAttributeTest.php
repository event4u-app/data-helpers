<?php

declare(strict_types=1);

namespace Tests\Unit\ValidationAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Json;

class JsonTestDto extends SimpleDto
{
    public function __construct(
        #[Json]
        public readonly ?string $data = null,
    ) {}
}

describe('Json Attribute - Plain PHP Validation', function(): void {
    it('passes with valid JSON object', function(): void {
        $result = JsonTestDto::validate(['data' => '{"name":"John","age":30}']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with valid JSON array', function(): void {
        $result = JsonTestDto::validate(['data' => '["apple","banana","orange"]']);
        expect($result->isValid())->toBeTrue();
    });

    it('passes with empty JSON object', function(): void {
        $result = JsonTestDto::validate(['data' => '{}']);
        expect($result->isValid())->toBeTrue();
    });

    it('fails with invalid JSON', function(): void {
        $result = JsonTestDto::validate(['data' => '{invalid json}']);
        expect($result->isValid())->toBeFalse();
        expect($result->hasError('data'))->toBeTrue();
    });

    it('fails with non-JSON string', function(): void {
        $result = JsonTestDto::validate(['data' => 'not json']);
        expect($result->isValid())->toBeFalse();
    });

    it('passes with null', function(): void {
        $result = JsonTestDto::validate(['data' => null]);
        expect($result->isValid())->toBeTrue();
    });
});
