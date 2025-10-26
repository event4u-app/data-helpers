<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use event4u\DataHelpers\DataMutator;
use Tests\utils\Entities\Product;

describe('DataMutator with Doctrine', function(): void {
    it('can set values in Doctrine ArrayCollection', function(): void {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'name' => 'John',
                    'age' => 30,
                ],
            ],
        ]);        $result = DataMutator::make($collection)->set('users.0.email', 'john@example.com')->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['users'][0]['email'])->toBe('john@example.com');
    });

    it('can use wildcards to set values in Doctrine Collections', function(): void {
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
        ]);        $result = DataMutator::make($collection)->set('users.*.active', true)->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['users'][0]['active'])->toBeTrue();

        expect($array['users'][1]['active'])->toBeTrue();
    });

    it('can merge values in Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'user' => [
                'name' => 'John',
            ],
        ]);        $result = DataMutator::make($collection)->set('user', [
            'age' => 30,
        ], true)->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['user']['name'])->toBe('John');

        expect($array['user']['age'])->toBe(30);
    });

    it('can unset values from Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'name' => 'John',
                    'age' => 30,
                    'email' => 'john@example.com',
                ],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'email' => 'jane@example.com',
                ],
            ],
        ]);        $result = DataMutator::make($collection)->unset('users.*.email')->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['users'][0])->not->toHaveKey('email');

        expect($array['users'][1])->not->toHaveKey('email');

        expect($array['users'][0]['name'])->toBe('John');
        expect($array['users'][1]['name'])->toBe('Jane');
    });

    it('can set attributes on Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');        $result = DataMutator::make($entity)->set(
            'description',
            'A powerful laptop'
        )->toArray();

        expect($result)->toBeInstanceOf(Product::class);

        /** @var Product $result */
        expect($result->getDescription())->toBe('A powerful laptop');
    });

    it('can unset attributes from Doctrine Entity', function(): void {
        $entity = new Product('Laptop', '999.99');
        $entity->setDescription('A powerful laptop');

                $result = DataMutator::make($entity)->unset(
            'description'
        )->toArray();

        expect($result)->toBeInstanceOf(Product::class);

        /** @var Product $result */
        expect($result->getDescription())->toBeNull();
    });

    it('preserves Doctrine Collection type after mutation', function(): void {
        $collection = new ArrayCollection([
            'name' => 'John',
        ]);        $result = DataMutator::make($collection)->set('age', 30)->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);
        expect($result)->not->toBeArray();
    });

    it('can work with nested Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'categories' => [
                [
                    'name' => 'Electronics',
                    'products' => [
                        [
                            'name' => 'Laptop',
                            'price' => '999.99',
                        ],
                    ],
                ],
            ],
        ]);        $result = DataMutator::make($collection)->set('categories.0.products.0.stock', 10)->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['categories'][0]['products'][0]['stock'])->toBe(10);
    });

    it('can use deep wildcards with Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'categories' => [
                [
                    'products' => [
                        [
                            'name' => 'Laptop',
                            'price' => '999.99',
                        ],
                        [
                            'name' => 'Mouse',
                            'price' => '29.99',
                        ],
                    ],
                ],
                [
                    'products' => [
                        [
                            'name' => 'Keyboard',
                            'price' => '79.99',
                        ],
                    ],
                ],
            ],
        ]);        $result = DataMutator::make($collection)->set('categories.*.products.*.inStock', true)->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['categories'][0]['products'][0]['inStock'])->toBeTrue();

        expect($array['categories'][0]['products'][1]['inStock'])->toBeTrue();

        expect($array['categories'][1]['products'][0]['inStock'])->toBeTrue();
    });

    it('can unset with deep wildcards in Doctrine Collections', function(): void {
        $collection = new ArrayCollection([
            'users' => [
                [
                    'profile' => [
                        'email' => 'john@example.com',
                        'phone' => '123',
                    ],
                ],
                [
                    'profile' => [
                        'email' => 'jane@example.com',
                        'phone' => '456',
                    ],
                ],
            ],
        ]);        $result = DataMutator::make($collection)->unset('users.*.profile.phone')->toArray();

        expect($result)->toBeInstanceOf(ArrayCollection::class);

        /** @phpstan-ignore-next-line unknown */
        $array = $result->toArray();

        expect($array['users'][0]['profile'])->not->toHaveKey('phone');

        expect($array['users'][1]['profile'])->not->toHaveKey('phone');

        expect($array['users'][0]['profile']['email'])->toBe('john@example.com');

        expect($array['users'][1]['profile']['email'])->toBe('jane@example.com');
    });
})->group('doctrine');
