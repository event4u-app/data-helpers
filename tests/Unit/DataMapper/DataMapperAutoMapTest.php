<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use Illuminate\Database\Eloquent\Model;

describe('DataMapper AutoMap', function (): void {
    test('automap: JSON array to Eloquent Model (top-level fields)', function (): void {
        $json = json_encode([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], JSON_THROW_ON_ERROR);

        $user = new class extends Model {
            /** @var array<string, mixed> */
            protected $attributes = [];
        };

        $updated = DataMapper::autoMap($json, $user);

        $acc = new DataAccessor($updated);
        expect($acc->get('name'))->toBe('Alice');
        expect($acc->get('email'))->toBe('alice@example.com');
    });

    test('automap: snake_case JSON to DTO with camelCase props', function (): void {
        $source = [
            'payment_status' => 'PAID',
            'order_id' => 42,
        ];

        $dto = new class {
            /** @var null|string */
            public $paymentStatus = null;

            /** @var null|int */
            public $orderId = null;
        };

        $res = DataMapper::autoMap($source, $dto);

        $acc = new DataAccessor($res);
        expect($acc->get('paymentStatus'))->toBe('PAID');
        expect($acc->get('orderId'))->toBe(42);
    });

    test('automap: DTO (public props) to Eloquent Model', function (): void {
        $dto = new class {
            /** @var string */
            public $name = 'Alice';

            /** @var string */
            public $email = 'alice@example.com';
        };

        $user = new class extends Model {
            /** @var array<string, mixed> */
            protected $attributes = [];
        };

        $updated = DataMapper::autoMap($dto, $user);
        $acc = new DataAccessor($updated);
        expect($acc->get('name'))->toBe('Alice');
        expect($acc->get('email'))->toBe('alice@example.com');
    });

    test('automap: skipNull=true omits null values by default', function (): void {
        $source = [
            'name' => 'Alice',
            'email' => null,
        ];

        $res = DataMapper::autoMap($source, []); // default skipNull=true

        expect($res)->toBe([
            'name' => 'Alice',
        ]);
    });

    test('automap deep: nested array to array (skips null, keeps structure)', function (): void {
        $source = [
            'user' => [
                'address' => [
                    'street' => 'Main',
                    'zip' => null,
                ],
            ],
        ];

        $res = DataMapper::autoMap($source, [], true, false, [], true, false, true);

        expect($res)->toBe([
            'user' => [
                'address' => [
                    'street' => 'Main',
                ],
            ],
        ]);
    });

    test('automap deep: list with wildcard preserves gaps by default', function (): void {
        $source = [
            'users' => [
                [
                    'name' => 'A',
                ],
                [
                    'name' => null,
                ],
                [
                    'name' => 'C',
                ],
            ],
        ];

        $res = DataMapper::autoMap($source, [], true, false, [], true, false, true);

        /** @var array<string, mixed> $res */
        /** @var array<int, array<string, mixed>> $users */
        $users = $res['users'];

        // Expect indices 0 and 2 present, 1 omitted due to skipNull=true
        expect(array_keys($users))->toBe([0, 2]);
        expect($users[0]['name'])->toBe('A');
        expect($users[2]['name'])->toBe('C');
    });

    test('automap deep: snake_case to DTO with camelCase top-level only', function (): void {
        $source = [
            'shipping_address' => [
                'street_name' => 'Main',
            ],
        ];

        $dto = new class {
            /** @var array<string, mixed> */
            public $shippingAddress = [];
        };

        $res = DataMapper::autoMap($source, $dto, true, false, [], true, false, true);

        $acc = new DataAccessor($res);
        expect($acc->get('shippingAddress.street_name'))->toBe('Main');
    });
});
