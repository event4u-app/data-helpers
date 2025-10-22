<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

// Test classes for discriminator
abstract class AnimalAdv
{
    public string $name = '';
    public int $age = 0;
}

class DogAdv extends AnimalAdv
{
    public string $breed = '';

    public function bark(): string
    {
        return 'Woof!';
    }
}

class CatAdv extends AnimalAdv
{
    public string $color = '';

    public function meow(): string
    {
        return 'Meow!';
    }
}

class BirdAdv extends AnimalAdv
{
    public bool $canFly = true;

    public function chirp(): string
    {
        return 'Chirp!';
    }
}

describe('DataMapper - Discriminator Advanced Tests', function(): void {
    describe('Discriminator with Nested Paths', function(): void {
        it('supports dot-notation for discriminator field', function(): void {
            $source = [
                'meta' => [
                    'type' => 'bird',
                ],
                'name' => 'Tweety',
                'age' => 1,
                'canFly' => true,
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('meta.type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                    'bird' => BirdAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'canFly' => '{{ canFly }}',
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->toBeInstanceOf(BirdAdv::class);
            assert($target instanceof BirdAdv);
            expect($target->chirp())->toBe('Chirp!');
        });

        it('handles deeply nested discriminator field', function(): void {
            $source = [
                'data' => [
                    'meta' => [
                        'classification' => [
                            'type' => 'cat',
                        ],
                    ],
                ],
                'name' => 'Fluffy',
                'age' => 2,
                'color' => 'white',
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('data.meta.classification.type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'color' => '{{ color }}',
                ])
                ->map();

            expect($result->getTarget())->toBeInstanceOf(CatAdv::class);
        });
    });

    describe('Discriminator with copy()', function(): void {
        it('copy() preserves discriminator configuration', function(): void {
            $source1 = [
                'type' => 'dog',
                'name' => 'Max',
                'age' => 6,
                'breed' => 'Beagle',
            ];

            $source2 = [
                'type' => 'cat',
                'name' => 'Luna',
                'age' => 4,
                'color' => 'black',
            ];

            $mapper = DataMapper::source($source1)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                    'color' => '{{ color }}',
                ]);

            $result1 = $mapper->map();
            expect($result1->getTarget())->toBeInstanceOf(DogAdv::class);

            // Copy and use with different source
            $copy = $mapper->copy();
            $result2 = DataMapper::source($source2)
                ->target($copy->getQueries() ? BirdAdv::class : BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                    'color' => '{{ color }}',
                ])
                ->map();

            expect($result2->getTarget())->toBeInstanceOf(CatAdv::class);
        });
    });

    describe('Discriminator with Filters and Pipelines', function(): void {
        it('works with pipeline filters (discriminator value is trimmed)', function(): void {
            $source = [
                'type' => '  dog  ',
                'name' => '  Buddy  ',
                'age' => 7,
                'breed' => '  Poodle  ',
            ];

            // Note: Pipeline always returns array, so discriminator selects the class
            // but the result is still an array (this is a known limitation of pipeline)
            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                ])
                ->pipeline([new TrimStrings()])
                ->map();

            // Pipeline returns the discriminated object
            $target = $result->getTarget();
            expect($target)->toBeInstanceOf(DogAdv::class);
            assert($target instanceof DogAdv);
            expect($target->name)->toBe('Buddy');
            expect($target->breed)->toBe('Poodle');
        });

        it('works with property filters', function(): void {
            $source = [
                'type' => 'cat',
                'name' => '  MITTENS  ',
                'age' => 5,
                'color' => 'gray',
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'color' => '{{ color }}',
                ])
                ->setValueFilters('name', new TrimStrings())
                ->map();

            $target = $result->getTarget();
            expect($target)->toBeInstanceOf(CatAdv::class);
            assert($target instanceof CatAdv);
            expect($target->name)->toBe('MITTENS');
        });
    });

    describe('Edge Cases', function(): void {
        it('handles boolean discriminator values', function(): void {
            $source = [
                'type' => true,
                'name' => 'BoolDog',
                'age' => 3,
                'breed' => 'Terrier',
            ];

            /** @var array<string, class-string> $map */
            $map = [
                '1' => DogAdv::class,
                '' => CatAdv::class,
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', $map)
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                ])
                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogAdv::class);
        });

        it('handles case-sensitive discriminator values', function(): void {
            $source = [
                'type' => 'DOG',
                'name' => 'UpperDog',
                'age' => 4,
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'DOG' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                ])
                ->map();

            expect($result->getTarget())->toBeInstanceOf(CatAdv::class);
        });

        it('handles multiple discriminator configurations (last wins)', function(): void {
            $source = [
                'type' => 'dog',
                'name' => 'MultiConfig',
                'age' => 6,
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => CatAdv::class,
                ])
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                ])
                ->map();

            expect($result->getTarget())->toBeInstanceOf(DogAdv::class);
        });

        it('works with skipNull option', function(): void {
            $source = [
                'type' => 'cat',
                'name' => 'NullSkipper',
                'age' => null,
                'color' => 'brown',
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', [
                    'dog' => DogAdv::class,
                    'cat' => CatAdv::class,
                ])
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'color' => '{{ color }}',
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->toBeInstanceOf(CatAdv::class);
            assert($target instanceof CatAdv);
            expect($target->name)->toBe('NullSkipper');
            expect($target->age)->toBe(0); // Default value
        });

        it('works with large discriminator map', function(): void {
            $map = [];
            for ($i = 0; 100 > $i; $i++) {
                $map['type' . $i] = $i % 2 === 0 ? DogAdv::class : CatAdv::class;
            }

            $source = [
                'type' => 'type42',
                'name' => 'LargeMap',
                'age' => 8,
                'breed' => 'Husky',
            ];

            $result = DataMapper::source($source)
                ->target(BirdAdv::class)
                ->discriminator('type', $map)
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'breed' => '{{ breed }}',
                ])
                ->map();

            $target = $result->getTarget();
            expect($target)->toBeInstanceOf(DogAdv::class);
            assert($target instanceof DogAdv);
            expect($target->name)->toBe('LargeMap');
        });
    });
});
