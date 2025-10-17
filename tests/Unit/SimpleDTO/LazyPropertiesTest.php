<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

describe('Lazy Properties', function(): void {
    describe('Basic Lazy Loading', function(): void {
        it('does not include lazy property by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Long biography text...',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'biography' => 'Bio text']);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->not->toHaveKey('biography');
        });

        it('includes lazy property when explicitly requested', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Long biography text...',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'biography' => 'Bio text']);
            $array = $instance->include(['biography'])->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('biography')
                ->and($array['biography'])->toBe('Bio text');
        });

        it('property is still accessible directly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Long biography text...',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'biography' => 'Bio text']);

            expect($instance->name)->toBe('John')
                ->and($instance->biography)->toBe('Bio text');
        });
    });

    describe('Multiple Lazy Properties', function(): void {
        it('supports multiple lazy properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Bio',
                    #[Lazy]
                    public readonly string $notes = 'Notes',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'biography' => 'Bio text',
                'notes' => 'Internal notes',
            ]);

            // Default: no lazy properties
            $array1 = $instance->toArray();
            expect($array1)->toHaveKey('name')
                ->and($array1)->not->toHaveKey('biography')
                ->and($array1)->not->toHaveKey('notes');

            // Include only biography
            $array2 = $instance->include(['biography'])->toArray();
            expect($array2)->toHaveKey('biography')
                ->and($array2)->not->toHaveKey('notes');

            // Include both
            $array3 = $instance->include(['biography', 'notes'])->toArray();
            expect($array3)->toHaveKey('biography')
                ->and($array3)->toHaveKey('notes');
        });

        it('includeAll includes all lazy properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Bio',
                    #[Lazy]
                    public readonly string $notes = 'Notes',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'biography' => 'Bio text',
                'notes' => 'Internal notes',
            ]);

            $array = $instance->includeAll()->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('biography')
                ->and($array)->toHaveKey('notes');
        });
    });

    describe('JSON Serialization', function(): void {
        it('does not include lazy property in JSON by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $secret = 'Secret data',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'secret' => 'Secret']);
            $json = json_encode($instance);

            expect($json)->toContain('John')
                ->and($json)->not->toContain('Secret');
        });

        it('includes lazy property in JSON when requested', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $secret = 'Secret data',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'secret' => 'Secret']);
            $json = json_encode($instance->include(['secret']));

            expect($json)->toContain('John')
                ->and($json)->toContain('Secret');
        });
    });

    describe('Conditional Lazy Loading', function(): void {
        it('explicit include always works', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy(when: 'admin')]
                    public readonly string $internalNotes = 'Internal notes',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'internalNotes' => 'Admin only notes',
            ]);

            // Without explicit include: not included
            $array1 = $instance->toArray();
            expect($array1)->not->toHaveKey('internalNotes');

            // Explicit include works
            $array2 = $instance->include(['internalNotes'])->toArray();
            expect($array2)->toHaveKey('internalNotes')
                ->and($array2['internalNotes'])->toBe('Admin only notes');
        });
    });

    describe('Lazy Properties with Other Features', function(): void {
        it('works with visibility attributes', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    #[\event4u\DataHelpers\SimpleDTO\Attributes\Hidden]
                    public readonly string $secret = 'Secret',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'secret' => 'Secret']);

            // Even with include, Hidden takes precedence
            $array = $instance->include(['secret'])->toArray();
            expect($array)->not->toHaveKey('secret');
        });

        it('works with only/except', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    public readonly string $email = 'test@example.com',
                    #[Lazy]
                    public readonly string $biography = 'Bio',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'biography' => 'Bio text',
            ]);

            $array = $instance->include(['biography'])->only(['name', 'biography'])->toArray();
            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('biography')
                ->and($array)->not->toHaveKey('email');
        });

        it('works with different data types', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly int $age = 0,
                    #[Lazy]
                    public readonly array $tags = [],
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'age' => 30,
                'tags' => ['php', 'laravel'],
            ]);

            $array = $instance->include(['age', 'tags'])->toArray();
            expect($array)->toHaveKey('age')
                ->and($array['age'])->toBe(30)
                ->and($array)->toHaveKey('tags')
                ->and($array['tags'])->toBe(['php', 'laravel']);
        });
    });

    describe('Chaining', function(): void {
        it('can chain include with other methods', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    public readonly string $email = 'test@example.com',
                    #[Lazy]
                    public readonly string $biography = 'Bio',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'biography' => 'Bio text',
            ]);

            $array = $instance
                ->include(['biography'])
                ->only(['name', 'biography'])
                ->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('biography')
                ->and($array)->not->toHaveKey('email');
        });

        it('preserves include across clones', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                    #[Lazy]
                    public readonly string $biography = 'Bio',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'biography' => 'Bio text']);
            $withBio = $instance->include(['biography']);

            // Original should not include biography
            expect($instance->toArray())->not->toHaveKey('biography');

            // Clone should include biography
            expect($withBio->toArray())->toHaveKey('biography');
        });
    });
});

