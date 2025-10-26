<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use event4u\DataHelpers\DataAccessor;
use Tests\Utils\Entities\Product;

describe('DataAccessor with Doctrine', function(): void {
    it('can read from Doctrine ArrayCollection', function(): void {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'name' => 'John',
                    'age' => 30,
                ],
                [
                    'name' => 'Jane',
                    'age' => 25,
                ],
            ],
        ]);

        $accessor = new DataAccessor($collection);

        expect($accessor->get('users.0.name'))->toBe('John');
        expect($accessor->get('users.1.name'))->toBe('Jane');
    });

    it('can use wildcards with Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'name' => 'John',
                    'age' => 30,
                ],
                [
                    'name' => 'Jane',
                    'age' => 25,
                ],
            ],
        ]);

        $accessor = new DataAccessor($collection);
        $names = $accessor->get('users.*.name');

        expect($names)->toBe([
            'users.0.name' => 'John',
            'users.1.name' => 'Jane',
        ]);
    });

    it('can read from Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');
        $entity->setDescription('A powerful laptop');

        $accessor = new DataAccessor($entity);

        expect($accessor->get('name'))->toBe('Laptop');
        expect($accessor->get('price'))->toBe('999.99');
        expect($accessor->get('description'))->toBe('A powerful laptop');
    });

    it('can read nested Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'products' => new ArrayCollection([
                [
                    'name' => 'Laptop',
                    'price' => '999.99',
                ],
                [
                    'name' => 'Mouse',
                    'price' => '29.99',
                ],
            ]),
        ]);

        $accessor = new DataAccessor($collection);

        expect($accessor->get('products.0.name'))->toBe('Laptop');
        expect($accessor->get('products.1.price'))->toBe('29.99');
    });

    it('can use wildcards with nested Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'categories' => [
                [
                    'name' => 'Electronics',
                    'products' => new ArrayCollection([
                        [
                            'name' => 'Laptop',
                            'price' => '999.99',
                        ],
                        [
                            'name' => 'Mouse',
                            'price' => '29.99',
                        ],
                    ]),
                ],
                [
                    'name' => 'Books',
                    'products' => new ArrayCollection([
                        [
                            'name' => 'PHP Book',
                            'price' => '49.99',
                        ],
                    ]),
                ],
            ],
        ]);

        $accessor = new DataAccessor($collection);
        $productNames = $accessor->get('categories.*.products.*.name');

        expect($productNames)->toHaveKey('categories.0.products.0.name');
        expect($productNames)->toHaveKey('categories.0.products.1.name');
        expect($productNames)->toHaveKey('categories.1.products.0.name');

        /** @var array<string, mixed> $productNames */
        expect($productNames['categories.0.products.0.name'])->toBe('Laptop');
        expect($productNames['categories.0.products.1.name'])->toBe('Mouse');
        expect($productNames['categories.1.products.0.name'])->toBe('PHP Book');
    });

    it('returns null for non-existent paths in Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'name' => 'John',
        ]);

        $accessor = new DataAccessor($collection);

        expect($accessor->get('nonexistent'))->toBeNull();
        expect($accessor->get('name.nested'))->toBeNull();
    });

    it('returns null for non-existent attributes in Doctrine Entities', function(): void {
        $entity = new Product('Laptop', '999.99');

        $accessor = new DataAccessor($entity);

        expect($accessor->get('nonexistent'))->toBeNull();
    });

    it('can read from array of Doctrine Entities', function(): void {
        $entities = [
            new Product('Laptop', '999.99'),
            new Product('Mouse', '29.99'),
            new Product('Keyboard', '79.99'),
        ];

        $accessor = new DataAccessor([
            'products' => $entities,
        ]);
        $names = $accessor->get('products.*.name');

        expect($names)->toBe([
            'products.0.name' => 'Laptop',
            'products.1.name' => 'Mouse',
            'products.2.name' => 'Keyboard',
        ]);
    });

    it('can read from Doctrine Collection of Entities', function(): void {
        $entities = new ArrayCollection([
            new Product('Laptop', '999.99'),
            new Product('Mouse', '29.99'),
        ]);

        $accessor = new DataAccessor([
            'products' => $entities,
        ]);
        $prices = $accessor->get('products.*.price');

        expect($prices)->toBe([
            'products.0.price' => '999.99',
            'products.1.price' => '29.99',
        ]);
    });
})->group('doctrine');
