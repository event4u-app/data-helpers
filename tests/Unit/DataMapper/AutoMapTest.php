<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

/**
 * Tests for autoMap() and autoMapReverse() methods in FluentDataMapper.
 *
 * @internal
 */
describe('DataMapper autoMap() and autoMapReverse()', function (): void {
    describe('autoMap() - Automatic field mapping', function (): void {
        it('maps matching field names automatically (shallow)', function (): void {
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

        it('maps matching field names automatically (deep)', function (): void {
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

        it('skips template even if one is set', function (): void {
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

        it('converts snake_case to camelCase for object targets', function (): void {
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

            expect($targetObj->userName)->toBe('Alice');
            expect($targetObj->userEmail)->toBe('alice@example.com');
        });

        it('respects skipNull setting', function (): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
                'age' => 30,
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->skipNull(true)
                ->autoMap();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'age' => 30,
            ]);
        });

        it('respects trimValues setting', function (): void {
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

    describe('reverseAutoMap() - Automatic reverse field mapping', function (): void {
        it('maps matching field names automatically in reverse direction', function (): void {
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

        it('skips template even if one is set', function (): void {
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
});

