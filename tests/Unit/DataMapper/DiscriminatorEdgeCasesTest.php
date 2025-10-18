<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Enums\DataMapperHook;

// Test classes
abstract class AnimalEdge
{
    public string $name = '';
    public int $age = 0;
}

class DogEdge extends AnimalEdge
{
    public string $breed = '';
    public array $items = [];
}

class CatEdge extends AnimalEdge
{
    public string $color = '';
}

class BirdEdge extends AnimalEdge
{
    public bool $canFly = true;
}

describe('DataMapper - Discriminator Edge Cases', function(): void {
    describe('Critical Edge Cases', function(): void {
        it('works with reverseMap()', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'Rex',
                'age' => 5,
                'breed' => 'Labrador',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                    'cat' => CatEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                ])
                ->reverseMap();

            // reverseMap should also use discriminator
            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
            expect($result->getTarget()->name)->toBe('Rex');
        });

        it('works with extendTemplate()', function(): void {
            $source = [
                'type' => 'cat',
                'name' => 'Whiskers',
                'age' => 3,
                'color' => 'orange',
            ];

            $base = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                    'cat' => CatEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                ]);

            $extended = $base->copy()
                ->extendTemplate([
                    'age' => '{{ age }}',
                    'color' => '{{ color }}',
                ]);

            $result = $extended->map();

            expect($result->getTarget())->toBeInstanceOf(CatEdge::class);
            expect($result->getTarget()->name)->toBe('Whiskers');
            expect($result->getTarget()->age)->toBe(3);
            expect($result->getTarget()->color)->toBe('orange');
        });

        it('handles empty discriminator map', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'Rex',
            ];

            $result = DataMapper::source($source)
                ->target([]) // Use array as target
                ->discriminator('type', []) // Empty map
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Empty map means no discriminator resolution, returns array
            expect($result->getTarget())->toBeArray();
            expect($result->getTarget()['name'])->toBe('Rex');
        });

        it('handles empty string as discriminator field', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'Rex',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('', [ // Empty field name
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Should fallback to original target (empty field doesn't exist)
            expect($result->getTarget())->toBeInstanceOf(BirdEdge::class);
        });

        it('handles float discriminator values', function(): void {
            $source = [
                'type' => 1.5,
                'name' => 'Rex',
                'breed' => 'Labrador',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    '1.5' => DogEdge::class,
                    '2.5' => CatEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'breed' => '{{ breed }}',
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
        });

        it('handles special characters in discriminator values', function(): void {
            $source = [
                'type' => 'dog-special',
                'name' => 'Rex',
                'breed' => 'Labrador',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog-special' => DogEdge::class,
                    'cat/special' => CatEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'breed' => '{{ breed }}',
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
        });
    });

    describe('Important Edge Cases', function(): void {
        it('works with hooks', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'rex',
                'age' => 5,
                'breed' => 'Labrador',
            ];

            $hookCalled = false;

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                ])
                ->hooks([
                    DataMapperHook::BeforeAll->value => function($context) use (&$hookCalled) {
                        $hookCalled = true;
                        return $context;
                    },
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
            expect($hookCalled)->toBeTrue();
        });

        it('handles very deep nesting in discriminator field', function(): void {
            $source = [
                'a' => [
                    'b' => [
                        'c' => [
                            'd' => [
                                'e' => [
                                    'f' => [
                                        'type' => 'cat',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'name' => 'Whiskers',
                'color' => 'orange',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('a.b.c.d.e.f.type', [
                    'dog' => DogEdge::class,
                    'cat' => CatEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'color' => '{{ color }}',
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(CatEdge::class);
        });

        it('handles discriminator field as array', function(): void {
            $source = [
                'type' => ['dog', 'cat'], // Array instead of string
                'name' => 'Rex',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Should fallback to original target (array can't be matched)
            expect($result->getTarget())->toBeInstanceOf(BirdEdge::class);
        });

        it('handles discriminator field as object', function(): void {
            $source = [
                'type' => (object)['value' => 'dog'], // Object instead of string
                'name' => 'Rex',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Should fallback to original target (object can't be matched directly)
            expect($result->getTarget())->toBeInstanceOf(BirdEdge::class);
        });

        it('works with caseInsensitiveReplace option', function(): void {
            $source = [
                'TYPE' => 'dog', // Uppercase key
                'NAME' => 'Rex',
                'BREED' => 'Labrador',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('TYPE', [ // Uppercase discriminator field (matches source)
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ NAME }}', // Use uppercase in template to match source
                    'breed' => '{{ BREED }}',
                ])
                ->caseInsensitiveReplace(true)
                ->trimValues(true)

                ->map();

            // caseInsensitiveReplace allows case-insensitive template matching
            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
            expect($result->getTarget()->name)->toBe('Rex');
        });

        it('works with trimValues option', function(): void {
            $source = [
                'type' => '  dog  ', // Whitespace around value
                'name' => '  Rex  ',
                'breed' => '  Labrador  ',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'breed' => '{{ breed }}',
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
            expect($result->getTarget()->name)->toBe('Rex');
        });
    });

    describe('Optional Edge Cases', function(): void {
        it('handles non-existent class in discriminator map', function(): void {
            $source = [
                'type' => 'unicorn',
                'name' => 'Sparkle',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'unicorn' => 'NonExistentClass', // Class doesn't exist
                ])
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Should return array (because class_exists check fails)
            expect($result->getTarget())->toBeArray();
        });

        it('handles very long discriminator strings', function(): void {
            $longType = str_repeat('dog', 100); // 300 characters

            $source = [
                'type' => $longType,
                'name' => 'Rex',
                'breed' => 'Labrador',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    $longType => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'breed' => '{{ breed }}',
                ])
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
        });

        it('handles discriminator with wildcard in field path', function(): void {
            $source = [
                'items' => [
                    ['type' => 'dog'],
                    ['type' => 'cat'],
                ],
                'name' => 'Rex',
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('items.*.type', [ // Wildcard in path
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                ])
                ->trimValues(true)

                ->map();

            // Should fallback to original target (wildcard returns array)
            expect($result->getTarget())->toBeInstanceOf(BirdEdge::class);
        });

        it('handles discriminator with MapperQuery combination', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'Rex',
                'age' => 5,
                'breed' => 'Labrador',
                'items' => [
                    ['id' => 1, 'status' => 'active'],
                    ['id' => 2, 'status' => 'inactive'],
                ],
            ];

            $result = DataMapper::source($source)
                ->target(BirdEdge::class)
                ->discriminator('type', [
                    'dog' => DogEdge::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                    'items' => '{{ items }}',
                ])
                ->query('items.*')
                    ->where('status', 'active')
                    ->end()
                ->trimValues(true)

                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogEdge::class);
            expect($result->getTarget()->name)->toBe('Rex');
        });
    });
});

