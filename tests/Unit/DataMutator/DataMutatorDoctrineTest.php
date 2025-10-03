<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use event4u\DataHelpers\DataMutator;
use Tests\utils\Entities\Product;

describe('DataMutator with Doctrine', function () {
    it('can set values in Doctrine ArrayCollection', function () {
        $collection = new ArrayCollection([
            'users' => [
                ['name' => 'John', 'age' => 30],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'users.0.email', 'john@example.com');

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['users'][0]['email'])->toBe('john@example.com');
    });

    it('can use wildcards to set values in Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'users' => [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'users.*.active', true);

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['users'][0]['active'])->toBeTrue();
        expect($array['users'][1]['active'])->toBeTrue();
    });

    it('can merge values in Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'user' => ['name' => 'John'],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'user', ['age' => 30], merge: true);

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['user']['name'])->toBe('John');
        expect($array['user']['age'])->toBe(30);
    });

    it('can unset values from Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'users' => [
                ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
                ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com'],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->unset($collection, 'users.*.email');

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['users'][0])->not->toHaveKey('email');
        expect($array['users'][1])->not->toHaveKey('email');
        expect($array['users'][0]['name'])->toBe('John');
        expect($array['users'][1]['name'])->toBe('Jane');
    });

    it('can set attributes on Doctrine Entity', function () {
        $entity = new Product('Laptop', '999.99');

        $mutator = new DataMutator();
        $result = $mutator->set($entity, 'description', 'A powerful laptop');

        expect($result)->toBeInstanceOf(Product::class);
        expect($result->getDescription())->toBe('A powerful laptop');
    });

    it('can unset attributes from Doctrine Entity', function () {
        $entity = new Product('Laptop', '999.99');
        $entity->setDescription('A powerful laptop');

        $mutator = new DataMutator();
        $result = $mutator->unset($entity, 'description');

        expect($result)->toBeInstanceOf(Product::class);
        expect($result->getDescription())->toBeNull();
    });

    it('preserves Doctrine Collection type after mutation', function () {
        $collection = new ArrayCollection(['name' => 'John']);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'age', 30);

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        expect($result)->not->toBeArray();
    });

    it('can work with nested Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'categories' => [
                [
                    'name' => 'Electronics',
                    'products' => [
                        ['name' => 'Laptop', 'price' => '999.99'],
                    ],
                ],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'categories.0.products.0.stock', 10);

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['categories'][0]['products'][0]['stock'])->toBe(10);
    });

    it('can use deep wildcards with Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'categories' => [
                [
                    'products' => [
                        ['name' => 'Laptop', 'price' => '999.99'],
                        ['name' => 'Mouse', 'price' => '29.99'],
                    ],
                ],
                [
                    'products' => [
                        ['name' => 'Keyboard', 'price' => '79.99'],
                    ],
                ],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->set($collection, 'categories.*.products.*.inStock', true);

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['categories'][0]['products'][0]['inStock'])->toBeTrue();
        expect($array['categories'][0]['products'][1]['inStock'])->toBeTrue();
        expect($array['categories'][1]['products'][0]['inStock'])->toBeTrue();
    });

    it('can unset with deep wildcards in Doctrine Collections', function () {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'profile' => ['email' => 'john@example.com', 'phone' => '123'],
                ],
                [
                    'profile' => ['email' => 'jane@example.com', 'phone' => '456'],
                ],
            ],
        ]);

        $mutator = new DataMutator();
        $result = $mutator->unset($collection, 'users.*.profile.phone');

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        $array = $result->toArray();
        expect($array['users'][0]['profile'])->not->toHaveKey('phone');
        expect($array['users'][1]['profile'])->not->toHaveKey('phone');
        expect($array['users'][0]['profile']['email'])->toBe('john@example.com');
        expect($array['users'][1]['profile']['email'])->toBe('jane@example.com');
    });
});

