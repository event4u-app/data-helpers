<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use event4u\DataHelpers\Support\CollectionHelper;
use Illuminate\Support\Collection as LaravelCollection;

describe('CollectionHelper', function(): void {
    it('detects Laravel Collections', function(): void {
        $collection = new LaravelCollection([
            'name' => 'John',
        ]);

        expect(CollectionHelper::isLaravelCollection($collection))->toBeTrue();
        expect(CollectionHelper::isCollection($collection))->toBeTrue();
        expect(CollectionHelper::isDoctrineCollection($collection))->toBeFalse();
    })->group('laravel');

    it('converts Laravel Collection to array', function(): void {
        $collection = new LaravelCollection([
            'name' => 'John',
            'age' => 30,
        ]);

        $result = CollectionHelper::toArray($collection);

        expect($result)->toBe([
            'name' => 'John',
            'age' => 30,
        ]);
    })->group('laravel');

    it('checks if Laravel Collection has key', function(): void {
        $collection = new LaravelCollection([
            'name' => 'John',
            'age' => 30,
        ]);

        expect(CollectionHelper::has($collection, 'name'))->toBeTrue();
        expect(CollectionHelper::has($collection, 'email'))->toBeFalse();
    })->group('laravel');

    it('gets value from Laravel Collection', function(): void {
        $collection = new LaravelCollection([
            'name' => 'John',
            'age' => 30,
        ]);

        expect(CollectionHelper::get($collection, 'name'))->toBe('John');
        expect(CollectionHelper::get($collection, 'email', 'default'))->toBe('default');
    })->group('laravel');

    it('creates Laravel Collection from array', function(): void {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = CollectionHelper::fromArray($data);

        expect($result)->toBeInstanceOf(LaravelCollection::class);

        if ($result instanceof LaravelCollection) {
            expect($result->all())->toBe($data);
        }
    })->group('laravel');

    it('returns empty array for non-collection', function(): void {
        $notCollection = 'not a collection';

        expect(CollectionHelper::toArray($notCollection))->toBe([]);
        expect(CollectionHelper::has($notCollection, 'key'))->toBeFalse();
        expect(CollectionHelper::get($notCollection, 'key', 'default'))->toBe('default');
    });

    it('handles Doctrine Collections when available', function(): void {
        $collection = new ArrayCollection([
            'name' => 'Jane',
        ]);

        expect(CollectionHelper::isDoctrineCollection($collection))->toBeTrue();
        expect(CollectionHelper::isCollection($collection))->toBeTrue();
        expect(CollectionHelper::toArray($collection))->toBe([
            'name' => 'Jane',
        ]);
    })->group('doctrine');

    it('detects Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'name' => 'Jane',
        ]);

        expect(CollectionHelper::isDoctrineCollection($collection))->toBeTrue();
        expect(CollectionHelper::isCollection($collection))->toBeTrue();
        expect(CollectionHelper::isLaravelCollection($collection))->toBeFalse();
    })->group('doctrine');

    it('converts Doctrine Collection to array', function(): void {
        $collection = new ArrayCollection([
            'name' => 'Jane',
            'age' => 25,
        ]);

        $result = CollectionHelper::toArray($collection);

        expect($result)->toBe([
            'name' => 'Jane',
            'age' => 25,
        ]);
    })->group('doctrine');

    it('checks if Doctrine Collection has key', function(): void {
        $collection = new ArrayCollection([
            'name' => 'Jane',
            'age' => 25,
        ]);

        expect(CollectionHelper::has($collection, 'name'))->toBeTrue();
        expect(CollectionHelper::has($collection, 'email'))->toBeFalse();
    })->group('doctrine');

    it('gets value from Doctrine Collection', function(): void {
        $collection = new ArrayCollection([
            'name' => 'Jane',
            'age' => 25,
        ]);

        expect(CollectionHelper::get($collection, 'name'))->toBe('Jane');
        expect(CollectionHelper::get($collection, 'email', 'default'))->toBe('default');
    })->group('doctrine');

    it('creates Doctrine Collection from array with fromArrayWithType', function(): void {
        $original = new ArrayCollection([
            'name' => 'Jane',
        ]);
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = CollectionHelper::fromArrayWithType($data, $original);

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        if ($result instanceof ArrayCollection) {
            expect($result->toArray())->toBe($data);
        }
    })->group('doctrine');

    it('creates Laravel Collection from array with fromArrayWithType', function(): void {
        $original = new LaravelCollection([
            'name' => 'Jane',
        ]);
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = CollectionHelper::fromArrayWithType($data, $original);

        expect($result)->toBeInstanceOf(LaravelCollection::class);
        if ($result instanceof LaravelCollection) {
            expect($result->all())->toBe($data);
        }
    })->group('laravel');

    it('returns array when fromArrayWithType gets non-collection', function(): void {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = CollectionHelper::fromArrayWithType($data, 'not a collection');

        expect($result)->toBe($data);
    });
});
