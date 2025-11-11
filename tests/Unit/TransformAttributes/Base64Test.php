<?php

declare(strict_types=1);

namespace Tests\Unit\TransformAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Base64Decode;
use event4u\DataHelpers\SimpleDto\Attributes\Base64Encode;

class Base64EncodeTestDto extends SimpleDto
{
    public function __construct(
        #[Base64Encode]
        public readonly string $token,
        #[Base64Encode]
        public readonly ?string $payload = null,
    ) {}
}

class Base64DecodeTestDto extends SimpleDto
{
    public function __construct(
        #[Base64Decode]
        public readonly string $token,
        #[Base64Decode]
        public readonly ?string $payload = null,
    ) {}
}

describe('Base64Encode Transform Attribute', function(): void {
    it('encodes string to base64', function(): void {
        $dto = Base64EncodeTestDto::from(['token' => 'hello']);
        expect($dto->token)->toBe(base64_encode('hello'));
        expect($dto->token)->toBe('aGVsbG8=');
    });

    it('encodes complex strings', function(): void {
        $dto = Base64EncodeTestDto::from(['token' => 'Hello World!']);
        expect($dto->token)->toBe(base64_encode('Hello World!'));
        expect($dto->token)->toBe('SGVsbG8gV29ybGQh');
    });

    it('encodes special characters', function(): void {
        $dto = Base64EncodeTestDto::from(['token' => 'äöü@#$%']);
        expect($dto->token)->toBe(base64_encode('äöü@#$%'));
    });

    it('handles null values', function(): void {
        $dto = Base64EncodeTestDto::from(['token' => 'test', 'payload' => null]);
        expect($dto->payload)->toBeNull();
    });

    it('handles empty strings', function(): void {
        $dto = Base64EncodeTestDto::from(['token' => '']);
        expect($dto->token)->toBe('');
    });

    it('encodes json', function(): void {
        $json = json_encode(['user' => 'john', 'role' => 'admin']);
        $dto = Base64EncodeTestDto::from(['token' => $json]);
        expect($dto->token)->toBe(base64_encode($json));
        expect(base64_decode($dto->token))->toBe($json);
    });
});

describe('Base64Decode Transform Attribute', function(): void {
    it('decodes base64 string', function(): void {
        $dto = Base64DecodeTestDto::from(['token' => 'aGVsbG8=']);
        expect($dto->token)->toBe('hello');
    });

    it('decodes complex strings', function(): void {
        $dto = Base64DecodeTestDto::from(['token' => 'SGVsbG8gV29ybGQh']);
        expect($dto->token)->toBe('Hello World!');
    });

    it('decodes special characters', function(): void {
        $encoded = base64_encode('äöü@#$%');
        $dto = Base64DecodeTestDto::from(['token' => $encoded]);
        expect($dto->token)->toBe('äöü@#$%');
    });

    it('handles null values', function(): void {
        $dto = Base64DecodeTestDto::from(['token' => 'aGVsbG8=', 'payload' => null]);
        expect($dto->payload)->toBeNull();
    });

    it('handles empty strings', function(): void {
        $dto = Base64DecodeTestDto::from(['token' => '']);
        expect($dto->token)->toBe('');
    });

    it('handles invalid base64', function(): void {
        $dto = Base64DecodeTestDto::from(['token' => 'not-valid-base64!!!']);
        // Should return original value if decoding fails
        expect($dto->token)->toBe('not-valid-base64!!!');
    });

    it('decodes json', function(): void {
        $json = json_encode(['user' => 'john', 'role' => 'admin']);
        $encoded = base64_encode($json);
        $dto = Base64DecodeTestDto::from(['token' => $encoded]);
        expect($dto->token)->toBe($json);
        expect(json_decode($dto->token, true))->toBe(['user' => 'john', 'role' => 'admin']);
    });
});
