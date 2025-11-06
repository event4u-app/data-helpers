<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

describe('LiteDto Visibility Attributes', function(): void {
    describe('Hidden Attribute', function(): void {
        it('hides properties from both toArray() and toJson()', function(): void {
            $dto = new class('John', 'john@example.com', 'secret123') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                    #[Hidden]
                    public readonly string $password,
                ) {}
            };

            // Property is accessible
            expect($dto->password)->toBe('secret123');

            // Hidden from toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not->toHaveKey('password');

            // Hidden from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->toHaveKey('email');
            expect($json)->not->toHaveKey('password');
        });

        it('works with multiple hidden properties', function(): void {
            $dto = new class('John', 'john@example.com', 'secret123', 'api-key-123') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                    #[Hidden]
                    public readonly string $password,
                    #[Hidden]
                    public readonly string $apiKey,
                ) {}
            };

            // All properties accessible
            expect($dto->password)->toBe('secret123');
            expect($dto->apiKey)->toBe('api-key-123');

            // Hidden from toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKeys(['name', 'email']);
            expect($array)->not->toHaveKey('password');
            expect($array)->not->toHaveKey('apiKey');

            // Hidden from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKeys(['name', 'email']);
            expect($json)->not->toHaveKey('password');
            expect($json)->not->toHaveKey('apiKey');
        });
    });
});
