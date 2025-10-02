<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\CollectionHelper;
use Illuminate\Support\Collection as LaravelCollection;

describe('CollectionHelper', function () {
    it('detects Laravel Collections', function () {
        $collection = new LaravelCollection(['name' => 'John']);

        expect(CollectionHelper::isLaravelCollection($collection))->toBeTrue();
        expect(CollectionHelper::isCollection($collection))->toBeTrue();
        expect(CollectionHelper::isDoctrineCollection($collection))->toBeFalse();
    });

    it('converts Laravel Collection to array', function () {
        $collection = new LaravelCollection(['name' => 'John', 'age' => 30]);

        $result = CollectionHelper::toArray($collection);

        expect($result)->toBe(['name' => 'John', 'age' => 30]);
    });

    it('checks if Laravel Collection has key', function () {
        $collection = new LaravelCollection(['name' => 'John', 'age' => 30]);

        expect(CollectionHelper::has($collection, 'name'))->toBeTrue();
        expect(CollectionHelper::has($collection, 'email'))->toBeFalse();
    });

    it('gets value from Laravel Collection', function () {
        $collection = new LaravelCollection(['name' => 'John', 'age' => 30]);

        expect(CollectionHelper::get($collection, 'name'))->toBe('John');
        expect(CollectionHelper::get($collection, 'email', 'default'))->toBe('default');
    });

    it('creates Laravel Collection from array', function () {
        $data = ['name' => 'John', 'age' => 30];

        $result = CollectionHelper::fromArray($data);

        expect($result)->toBeInstanceOf(LaravelCollection::class);
        expect($result->all())->toBe($data);
    });

    it('returns empty array for non-collection', function () {
        $notCollection = 'not a collection';

        expect(CollectionHelper::toArray($notCollection))->toBe([]);
        expect(CollectionHelper::has($notCollection, 'key'))->toBeFalse();
        expect(CollectionHelper::get($notCollection, 'key', 'default'))->toBe('default');
    });

    it('handles Doctrine Collections when available', function () {
        if (!class_exists(\Doctrine\Common\Collections\ArrayCollection::class)) {
            expect(true)->toBeTrue(); // Skip if Doctrine not installed
            return;
        }

        $collection = new \Doctrine\Common\Collections\ArrayCollection(['name' => 'Jane']);

        expect(CollectionHelper::isDoctrineCollection($collection))->toBeTrue();
        expect(CollectionHelper::isCollection($collection))->toBeTrue();
        expect(CollectionHelper::toArray($collection))->toBe(['name' => 'Jane']);
    });
});

