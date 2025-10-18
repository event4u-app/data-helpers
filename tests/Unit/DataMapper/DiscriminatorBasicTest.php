<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

// Test classes for discriminator
abstract class Animal
{
    public string $name = '';
    public int $age = 0;
}

class Dog extends Animal
{
    public string $breed = '';

    public function bark(): string
    {
        return 'Woof!';
    }
}

class Cat extends Animal
{
    public string $color = '';

    public function meow(): string
    {
        return 'Meow!';
    }
}

class Bird extends Animal
{
    public bool $canFly = true;

    public function chirp(): string
    {
        return 'Chirp!';
    }
}

describe('DataMapper - Discriminator Basic', function(): void {
    it('selects correct subclass based on discriminator field', function(): void {
        $source = [
            'type' => 'dog',
            'name' => 'Bello',
            'age' => 5,
            'breed' => 'Golden Retriever',
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'breed' => '{{ breed }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Dog::class);
        expect($result->getTarget()->name)->toBe('Bello');
        expect($result->getTarget()->age)->toBe(5);
        expect($result->getTarget()->breed)->toBe('Golden Retriever');
        expect($result->getTarget()->bark())->toBe('Woof!');
    });

    it('selects different subclass for different discriminator value', function(): void {
        $source = [
            'type' => 'cat',
            'name' => 'Whiskers',
            'age' => 3,
            'color' => 'orange',
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'color' => '{{ color }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Cat::class);
        expect($result->getTarget()->name)->toBe('Whiskers');
        expect($result->getTarget()->age)->toBe(3);
        expect($result->getTarget()->color)->toBe('orange');
        expect($result->getTarget()->meow())->toBe('Meow!');
    });

    it('falls back to original target if discriminator value not in map', function(): void {
        $source = [
            'type' => 'unknown',
            'name' => 'Mystery',
            'age' => 1,
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Animal::class);
        expect($result->getTarget())->not->toBeInstanceOf(Dog::class);
        expect($result->getTarget())->not->toBeInstanceOf(Cat::class);
    });

    it('falls back to original target if discriminator field missing', function(): void {
        $source = [
            'name' => 'NoType',
            'age' => 2,
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Animal::class);
        expect($result->getTarget())->not->toBeInstanceOf(Dog::class);
        expect($result->getTarget())->not->toBeInstanceOf(Cat::class);
    });

    it('works with numeric discriminator values', function(): void {
        $source = [
            'type' => 1,
            'name' => 'Rex',
            'age' => 4,
            'breed' => 'Labrador',
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                '1' => Dog::class,
                '2' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'breed' => '{{ breed }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Dog::class);
    });

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
            ->target(Bird::class)
            ->discriminator('meta.type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
                'bird' => Bird::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'canFly' => '{{ canFly }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Bird::class);
        expect($result->getTarget()->chirp())->toBe('Chirp!');
    });

    it('handles null discriminator value', function(): void {
        $source = [
            'type' => null,
            'name' => 'NullType',
            'age' => 1,
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Animal::class);
    });

    it('handles empty string discriminator value', function(): void {
        $source = [
            'type' => '',
            'name' => 'EmptyType',
            'age' => 2,
            'color' => 'gray',
        ];

        $result = DataMapper::source($source)
            ->target(Bird::class)
            ->discriminator('type', [
                '' => Cat::class,
                'dog' => Dog::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'color' => '{{ color }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Cat::class);
    });

    it('works with array target', function(): void {
        $source = [
            'type' => 'dog',
            'name' => 'ArrayDog',
            'age' => 5,
            'breed' => 'Bulldog',
        ];

        $result = DataMapper::source($source)
            ->target([])
            ->discriminator('type', [
                'dog' => Dog::class,
                'cat' => Cat::class,
            ])
            ->template([
                'name' => '{{ name }}',
                'age' => '{{ age }}',
                'breed' => '{{ breed }}',
            ])
            ->map();

        expect($result->getTarget())->toBeInstanceOf(Dog::class);
    });
});
