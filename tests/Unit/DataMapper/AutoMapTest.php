<?php

declare(strict_types=1);

// Skip this file entirely if Laravel is not installed
if (!class_exists('Illuminate\Database\Eloquent\Model')) {
    return;
}

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use Illuminate\Database\Eloquent\Model;

/**
 * Tests for autoMap() and autoMapReverse() methods in FluentDataMapper.
 *
 * @internal
 */
describe('DataMapper autoMap() and autoMapReverse()', function(): void {
    describe('autoMap() - Automatic field mapping', function(): void {
        it('maps matching field names automatically (shallow)', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => 30,
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->autoMap();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => 30,
            ]);
        });

        it('maps matching field names automatically (deep)', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
                'address' => [
                    'street' => 'Main Street 1',
                    'city' => 'Berlin',
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->autoMap(deep: true);

            $target = $result->toArray();

            // Deep mode flattens nested structures to dot-paths
            expect($target)->toBeArray();
            expect($target)->toHaveKey('user');
            expect($target['user'])->toHaveKey('name');
            expect($target['user']['name'])->toBe('Alice');
            expect($target['user'])->toHaveKey('email');
            expect($target['user']['email'])->toBe('alice@example.com');
        });

        it('skips template even if one is set', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template(['fullname' => '{{ name }}']) // This template is ignored
                ->autoMap();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
        });

        it('converts snake_case to camelCase for object targets', function(): void {
            $source = [
                'user_name' => 'Alice',
                'user_email' => 'alice@example.com',
            ];

            $target = new class {
                public ?string $userName = null;

                public ?string $userEmail = null;
            };

            $result = DataMapper::source($source)
                ->target($target)
                ->autoMap();

            $targetObj = $result->getTarget();
            assert(is_object($targetObj));

            $acc = new DataAccessor($targetObj);
            expect($acc->get('userName'))->toBe('Alice');
            expect($acc->get('userEmail'))->toBe('alice@example.com');
        });

        it('respects skipNull setting', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
                'age' => 30,
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->autoMap();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'age' => 30,
            ]);
        });

        it('respects trimValues setting', function(): void {
            $source = [
                'name' => '  Alice  ',
                'email' => '  alice@example.com  ',
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->trimValues(true)
                ->autoMap();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
        });
    });

    describe('reverseAutoMap() - Automatic reverse field mapping', function(): void {
        it('maps matching field names automatically in reverse direction', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ];

            $target = [
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'age' => 25,
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->reverseAutoMap();

            // Reverse mapping: target → source
            expect($result->toArray())->toBe([
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'age' => 25,
            ]);
        });

        it('skips template even if one is set', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ];

            $target = [
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ];

            $result = DataMapper::source($source)
                ->target($target)
                ->template(['fullname' => '{{ name }}']) // This template is ignored
                ->reverseAutoMap();

            expect($result->toArray())->toBe([
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ]);
        });
    });

    describe('autoMap() with different target types', function(): void {
        it('maps JSON array to Eloquent Model (top-level fields)', function(): void {
            $json = json_encode([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], JSON_THROW_ON_ERROR);

            $user = new class extends Model {};

            $updated = DataMapper::source($json)->target($user)->autoMap()->getTarget();

            $acc = new DataAccessor($updated);
            expect($acc->get('name'))->toBe('Alice');
            expect($acc->get('email'))->toBe('alice@example.com');
        })->group('laravel');

        it('maps snake_case JSON to DTO with camelCase props', function(): void {
            $source = [
                'payment_status' => 'PAID',
                'order_id' => 42,
            ];

            $dto = new class {
                public ?string $paymentStatus = null;
                public ?int $orderId = null;
            };

            $res = DataMapper::source($source)->target($dto)->autoMap()->getTarget();

            $acc = new DataAccessor($res);
            expect($acc->get('paymentStatus'))->toBe('PAID');
            expect($acc->get('orderId'))->toBe(42);
        });

        it('maps DTO (public props) to Eloquent Model', function(): void {
            $dto = new class {
                public string $name = 'Alice';
                public string $email = 'alice@example.com';
            };

            $user = new class extends Model {};

            $updated = DataMapper::source($dto)->target($user)->autoMap()->getTarget();
            $acc = new DataAccessor($updated);
            expect($acc->get('name'))->toBe('Alice');
            expect($acc->get('email'))->toBe('alice@example.com');
        })->group('laravel');

        it('skips null values by default (skipNull=true)', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
            ];

            $res = DataMapper::source($source)->target([])->autoMap()->getTarget();

            expect($res)->toBe([
                'name' => 'Alice',
            ]);
        });

        it('maps nested array to array with deep mode (skips null, keeps structure)', function(): void {
            $source = [
                'user' => [
                    'address' => [
                        'street' => 'Main',
                        'zip' => null,
                    ],
                ],
            ];

            $res = DataMapper::source($source)->target([])->deep(true)->autoMap()->getTarget();

            expect($res)->toBe([
                'user' => [
                    'address' => [
                        'street' => 'Main',
                    ],
                ],
            ]);
        });

        it('preserves gaps in list with wildcard by default', function(): void {
            $source = [
                'users' => [
                    ['name' => 'A'],
                    ['name' => null],
                    ['name' => 'C'],
                ],
            ];

            $res = DataMapper::source($source)->target([])->deep(true)->autoMap()->getTarget();
            assert(is_array($res));

            $users = $res['users'];
            assert(is_array($users));

            // Expect indices 0 and 2 present, 1 omitted due to skipNull=true
            expect(array_keys($users))->toBe([0, 2]);
            expect($users[0]['name'])->toBe('A');
            expect($users[2]['name'])->toBe('C');
        });

        it('maps snake_case to DTO with camelCase (top-level only)', function(): void {
            $source = [
                'shipping_address' => [
                    'street_name' => 'Main',
                ],
            ];

            $dto = new class {
                /** @var array<string, mixed> */
                public array $shippingAddress = [];
            };

            $res = DataMapper::source($source)->target($dto)->deep(true)->autoMap()->getTarget();

            $acc = new DataAccessor($res);
            expect($acc->get('shippingAddress.street_name'))->toBe('Main');
        });
    });
});

