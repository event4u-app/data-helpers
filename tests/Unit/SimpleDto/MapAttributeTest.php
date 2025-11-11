<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Map;

describe('Map Attribute', function(): void {
    describe('Basic Bidirectional Mapping', function(): void {
        it('maps properties for input and output', function(): void {
            $dto = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Map('user_name')]
                    public readonly string $name,
                    #[Map('email_address')]
                    public readonly string $email,
                ) {}
            };

            // Test input mapping (MapFrom behavior)
            $result = $dto::fromArray([
                'user_name' => 'Jane Smith',
                'email_address' => 'jane@example.com',
            ]);

            expect($result->name)->toBe('Jane Smith')
                ->and($result->email)->toBe('jane@example.com');

            // Test output mapping (MapTo behavior)
            $array = $result->toArray();

            expect($array)->toBe([
                'user_name' => 'Jane Smith',
                'email_address' => 'jane@example.com',
            ]);
        });
    });

    describe('Fallback Sources', function(): void {
        it('supports array of sources with fallback', function(): void {
            $dto = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Map(['email', 'email_address', 'mail'])]
                    public readonly string $email,
                    #[Map(['name', 'full_name', 'username'])]
                    public readonly string $name,
                ) {}
            };

            // Test with first source
            $result1 = $dto::fromArray([
                'email' => 'test1@example.com',
                'name' => 'Test User 1',
            ]);

            expect($result1->email)->toBe('test1@example.com')
                ->and($result1->name)->toBe('Test User 1');

            // Test with second source (fallback)
            $result2 = $dto::fromArray([
                'email_address' => 'test2@example.com',
                'full_name' => 'Test User 2',
            ]);

            expect($result2->email)->toBe('test2@example.com')
                ->and($result2->name)->toBe('Test User 2');

            // Test with third source (fallback)
            $result3 = $dto::fromArray([
                'mail' => 'test3@example.com',
                'username' => 'Test User 3',
            ]);

            expect($result3->email)->toBe('test3@example.com')
                ->and($result3->name)->toBe('Test User 3');

            // Test output mapping (uses first source)
            $array = $result1->toArray();

            expect($array)->toBe([
                'email' => 'test1@example.com',
                'name' => 'Test User 1',
            ]);
        });
    });

    describe('Dot Notation', function(): void {
        it('supports nested property mapping', function(): void {
            $dto = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Map('user.profile.name')]
                    public readonly string $name,
                    #[Map('user.contact.email')]
                    public readonly string $email,
                ) {}
            };

            // Test input mapping with nested data
            $result = $dto::fromArray([
                'user' => [
                    'profile' => ['name' => 'John Doe'],
                    'contact' => ['email' => 'john@example.com'],
                ],
            ]);

            expect($result->name)->toBe('John Doe')
                ->and($result->email)->toBe('john@example.com');

            // Test output mapping (creates nested structure)
            $array = $result->toArray();

            expect($array)->toBe([
                'user' => [
                    'profile' => ['name' => 'John Doe'],
                    'contact' => ['email' => 'john@example.com'],
                ],
            ]);
        });
    });

    describe('Priority', function(): void {
        it('has priority over MapFrom and MapTo', function(): void {
            $dto = new class ('', '') extends SimpleDto {
                public function __construct(
                    #[Map('mapped_name')]
                    public readonly string $name,
                    public readonly string $email,
                ) {}
            };

            $result = $dto::fromArray([
                'mapped_name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            expect($result->name)->toBe('Test User')
                ->and($result->email)->toBe('test@example.com');

            $array = $result->toArray();

            expect($array)->toBe([
                'mapped_name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        });
    });
});
