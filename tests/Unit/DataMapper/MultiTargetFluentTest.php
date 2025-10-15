<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Multi-Target Fluent Mapping', function(): void {
    test('map to multiple array targets', function(): void {
        $source = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'street' => 'Main St',
            'city' => 'Berlin',
        ];

        $result = DataMapper::source($source)
            ->target(['user' => [], 'address' => []])
            ->template([
                'user' => [
                    'name' => '{{ name }}',
                    'email' => '{{ email }}',
                ],
                'address' => [
                    'street' => '{{ street }}',
                    'city' => '{{ city }}',
                ],
            ])
            ->map();

        $data = $result->getTarget();

        expect($data)->toBeArray();
        expect($data)->toHaveKey('user');
        expect($data)->toHaveKey('address');
        expect($data['user'])->toBe(['name' => 'Alice', 'email' => 'alice@example.com']);
        expect($data['address'])->toBe(['street' => 'Main St', 'city' => 'Berlin']);
    });

    test('map to multiple object targets', function(): void {
        $source = [
            'userName' => 'Bob',
            'userEmail' => 'bob@example.com',
            'companyName' => 'Acme Inc',
        ];

        $user = new class {
            public ?string $name = null;
            public ?string $email = null;
        };

        $company = new class {
            public ?string $name = null;
        };

        $result = DataMapper::source($source)
            ->target(['user' => $user, 'company' => $company])
            ->template([
                'user' => [
                    'name' => '{{ userName }}',
                    'email' => '{{ userEmail }}',
                ],
                'company' => [
                    'name' => '{{ companyName }}',
                ],
            ])
            ->map();

        $data = $result->getTarget();

        expect($data)->toBeArray();
        expect($data['user']->name)->toBe('Bob');
        expect($data['user']->email)->toBe('bob@example.com');
        expect($data['company']->name)->toBe('Acme Inc');
    });

    test('map with skipNull to multiple targets', function(): void {
        $source = [
            'name' => 'Eve',
            'email' => null,
            'city' => 'Munich',
        ];

        $result = DataMapper::source($source)
            ->target(['user' => [], 'location' => []])
            ->template([
                'user' => [
                    'name' => '{{ name }}',
                    'email' => '{{ email }}',
                ],
                'location' => [
                    'city' => '{{ city }}',
                ],
            ])
            ->skipNull(true)
            ->map();

        $data = $result->getTarget();

        expect($data['user'])->toBe(['name' => 'Eve']);
        expect($data['user'])->not->toHaveKey('email');
        expect($data['location'])->toBe(['city' => 'Munich']);
    });
});
