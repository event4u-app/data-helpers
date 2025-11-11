<?php

declare(strict_types=1);

namespace Tests\Unit\TransformAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Hash;
use event4u\DataHelpers\SimpleDto\Attributes\Md5;

class HashTestDto extends SimpleDto
{
    public function __construct(
        #[Hash] // Default sha256
        public readonly string $password,
        #[Hash('sha512')]
        public readonly string $apiKey,
        #[Hash('md5')]
        public readonly string $token,
        #[Md5]
        public readonly string $cacheKey,
        #[Hash('bcrypt')]
        public readonly string $securePassword,
    ) {}
}

describe('Hash Transform Attribute', function(): void {
    it('hashes with default sha256', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        expect($dto->password)->toBe(hash('sha256', 'secret'));
        expect($dto->password)->toHaveLength(64); // SHA256 produces 64 hex characters
    });

    it('hashes with sha512', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'my-api-key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        expect($dto->apiKey)->toBe(hash('sha512', 'my-api-key'));
        expect($dto->apiKey)->toHaveLength(128); // SHA512 produces 128 hex characters
    });

    it('hashes with md5', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'my-token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        expect($dto->token)->toBe(md5('my-token'));
        expect($dto->token)->toHaveLength(32); // MD5 produces 32 hex characters
    });

    it('hashes with bcrypt', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'my-password',
        ]);

        expect(password_verify('my-password', $dto->securePassword))->toBeTrue();
        expect($dto->securePassword)->toStartWith('$2y$'); // Bcrypt hash starts with $2y$
    });

    it('produces different hashes for different values', function(): void {
        $dto1 = HashTestDto::from([
            'password' => 'secret1',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        $dto2 = HashTestDto::from([
            'password' => 'secret2',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        expect($dto1->password)->not->toBe($dto2->password);
    });

    it('produces same hash for same value', function(): void {
        $dto1 = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        $dto2 = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'cache',
            'securePassword' => 'secure',
        ]);

        expect($dto1->password)->toBe($dto2->password);
        expect($dto1->apiKey)->toBe($dto2->apiKey);
        expect($dto1->token)->toBe($dto2->token);
    });
});

describe('Md5 Transform Attribute', function(): void {
    it('hashes with md5', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => 'my-cache-key',
            'securePassword' => 'secure',
        ]);

        expect($dto->cacheKey)->toBe(md5('my-cache-key'));
        expect($dto->cacheKey)->toHaveLength(32);
    });

    it('handles empty strings', function(): void {
        $dto = HashTestDto::from([
            'password' => 'secret',
            'apiKey' => 'key',
            'token' => 'token',
            'cacheKey' => '',
            'securePassword' => 'secure',
        ]);

        expect($dto->cacheKey)->toBe('');
    });
});
