<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;

// Test Dtos
class BasicLazyDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $biography,
    ) {}
}

class MultipleLazyDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $biography,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $notes,
    ) {}
}

class SecretLazyDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $secret,
    ) {}
}

class InternalNotesDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $internalNotes,
    ) {}
}

class MappedLazyDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|string $biography,
    ) {}
}

class TypedLazyDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|int $age,
        #[Lazy]
        public readonly \event4u\DataHelpers\Support\Lazy|array $tags,
    ) {}
}

describe('Lazy Properties', function(): void {
    describe('Basic Lazy Loading', function(): void {
        it('does not include lazy property by default', function(): void {
            $instance = BasicLazyDto::fromArray(['name' => 'John', 'biography' => 'Bio text']);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->not->toHaveKey('biography');
        });

        it('includes lazy property when explicitly requested', function(): void {
            $instance = BasicLazyDto::fromArray(['name' => 'John', 'biography' => 'Bio text']);
            $array = $instance->include(['biography'])->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('biography')
                ->and($array['biography'])->toBe('Bio text');
        });

        it('property is still accessible directly', function(): void {
            $instance = BasicLazyDto::fromArray(['name' => 'John', 'biography' => 'Bio text']);

            assert($instance->biography instanceof \event4u\DataHelpers\Support\Lazy);
            expect($instance->name)->toBe('John')
                ->and($instance->biography->get())->toBe('Bio text');
        });
    });

    describe('Multiple Lazy Properties', function(): void {
        it('supports multiple lazy properties', function(): void {
            $instance = MultipleLazyDto::fromArray([
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

            // Include all
            $array3 = $instance->includeAll()->toArray();
            expect($array3)->toHaveKey('biography')
                ->and($array3)->toHaveKey('notes');
        });

        it('can selectively include lazy properties', function(): void {
            $instance = MultipleLazyDto::fromArray([
                'name' => 'John',
                'biography' => 'Bio text',
                'notes' => 'Internal notes',
            ]);

            $array = $instance->include(['biography'])->toArray();

            expect($array)->toHaveKey('biography')
                ->and($array)->not->toHaveKey('notes');
        });
    });

    describe('JSON Serialization', function(): void {
        it('does not include lazy properties in JSON by default', function(): void {
            $instance = SecretLazyDto::fromArray([
                'name' => 'John',
                'secret' => 'Secret data',
            ]);

            $jsonString = json_encode($instance);
            assert(is_string($jsonString));
            $json = json_decode($jsonString, true);

            expect($json)->toHaveKey('name')
                ->and($json)->not->toHaveKey('secret');
        });

        it('includes lazy properties when explicitly requested', function(): void {
            $instance = SecretLazyDto::fromArray([
                'name' => 'John',
                'secret' => 'Secret data',
            ]);

            $jsonString = json_encode($instance->include(['secret']));
            assert(is_string($jsonString));
            $json = json_decode($jsonString, true);

            expect($json)->toHaveKey('name')
                ->and($json)->toHaveKey('secret');
        });
    });

    describe('Conditional Lazy Loading', function(): void {
        it('can conditionally include lazy properties', function(): void {
            $instance = InternalNotesDto::fromArray([
                'name' => 'John',
                'internalNotes' => 'Internal notes',
            ]);

            $publicArray = $instance->toArray();
            expect($publicArray)->not->toHaveKey('internalNotes');

            $adminArray = $instance->include(['internalNotes'])->toArray();
            expect($adminArray)->toHaveKey('internalNotes');
        });
    });

    describe('Lazy Properties with Other Features', function(): void {
        it('works with property mapping', function(): void {
            $instance = MappedLazyDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'biography' => 'Bio text',
            ]);

            $array = $instance->toArray();
            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('email')
                ->and($array)->not->toHaveKey('biography');
        });

        it('works with different data types', function(): void {
            $instance = TypedLazyDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'tags' => ['php', 'laravel'],
            ]);

            $array = $instance->toArray();
            expect($array)->toHaveKey('name')
                ->and($array)->not->toHaveKey('age')
                ->and($array)->not->toHaveKey('tags');

            $fullArray = $instance->includeAll()->toArray();
            expect($fullArray)->toHaveKey('age')
                ->and($fullArray['age'])->toBe(30)
                ->and($fullArray)->toHaveKey('tags')
                ->and($fullArray['tags'])->toBe(['php', 'laravel']);
        });
    });

    describe('Chaining', function(): void {
        it('can chain include with other methods', function(): void {
            $instance = MappedLazyDto::fromArray([
                'name' => 'John',
                'email' => 'john@example.com',
                'biography' => 'Bio text',
            ]);

            $array = $instance
                ->include(['biography'])
                ->toArray();

            expect($array)->toHaveKey('biography');
        });

        it('preserves include across clones', function(): void {
            $instance = BasicLazyDto::fromArray([
                'name' => 'John',
                'biography' => 'Bio text',
            ]);

            $included = $instance->include(['biography']);
            $array = $included->toArray();

            expect($array)->toHaveKey('biography');
        });
    });
});
