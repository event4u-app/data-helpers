<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;

describe('Target Alias Fluent Mapping (mapToTargetsFromTemplate equivalent)', function(): void {
    test('map data to target aliases with plain format', function(): void {
        $data = [
            'profile' => [
                'fullname' => 'Alice Smith',
                'email' => 'alice@example.com',
            ],
        ];

        $userModel = new class {
            public ?string $name = null;
            public ?string $email = null;
        };

        $template = [
            'profile' => [
                'fullname' => 'user.name',
                'email' => 'user.email',
            ],
        ];

        $result = DataMapper::source($data)
            ->target(['user' => $userModel])
            ->template($template)
            ->map();

        $targets = $result->getTarget();
        $user = $targets['user'];
        assert(is_object($user));
        $accUser = new DataAccessor($user);
        expect($accUser->get('name'))->toBe('Alice Smith');
        expect($accUser->get('email'))->toBe('alice@example.com');
    });

    test('map data to target aliases with @ prefix', function(): void {
        $data = [
            'profile' => [
                'fullname' => 'Bob Jones',
                'email' => 'bob@example.com',
            ],
        ];

        $userModel = new class {
            public ?string $name = null;
            public ?string $email = null;
        };

        $template = [
            'profile' => [
                'fullname' => '@user.name',
                'email' => '@user.email',
            ],
        ];

        $result = DataMapper::source($data)
            ->target(['user' => $userModel])
            ->template($template)
            ->map();

        $targets = $result->getTarget();
        $user = $targets['user'];
        assert(is_object($user));
        $accUser = new DataAccessor($user);
        expect($accUser->get('name'))->toBe('Bob Jones');
        expect($accUser->get('email'))->toBe('bob@example.com');
    });

    test('map data to target aliases with {{ }} format', function(): void {
        $data = [
            'profile' => [
                'fullname' => 'Charlie Brown',
                'email' => 'charlie@example.com',
            ],
        ];

        $userModel = new class {
            public ?string $name = null;
            public ?string $email = null;
        };

        $template = [
            'profile' => [
                'fullname' => '{{ user.name }}',
                'email' => '{{ user.email }}',
            ],
        ];

        $result = DataMapper::source($data)
            ->target(['user' => $userModel])
            ->template($template)
            ->map();

        $targets = $result->getTarget();
        $user = $targets['user'];
        assert(is_object($user));
        $accUser = new DataAccessor($user);
        expect($accUser->get('name'))->toBe('Charlie Brown');
        expect($accUser->get('email'))->toBe('charlie@example.com');
    });

    test('map data to multiple target aliases', function(): void {
        $data = [
            'profile' => [
                'fullname' => 'David Lee',
                'street' => 'Main St 123',
                'city' => 'Berlin',
            ],
        ];

        $userModel = new class {
            public ?string $name = null;
        };

        $addressArray = [];

        $template = [
            'profile' => [
                'fullname' => 'user.name',
                'street' => 'addr.street',
                'city' => 'addr.city',
            ],
        ];

        $result = DataMapper::source($data)
            ->target(['user' => $userModel, 'addr' => $addressArray])
            ->template($template)
            ->map();

        $targets = $result->getTarget();
        $user = $targets['user'];
        assert(is_object($user));
        $accUser = new DataAccessor($user);
        expect($accUser->get('name'))->toBe('David Lee');
        expect($targets['addr'])->toBe(['street' => 'Main St 123', 'city' => 'Berlin']);
    });

    test('map nested data to target aliases', function(): void {
        $data = [
            'user' => [
                'personal' => [
                    'firstName' => 'Eve',
                    'lastName' => 'Smith',
                ],
                'contact' => [
                    'email' => 'eve@example.com',
                ],
            ],
        ];

        $profileModel = new class {
            public ?string $fullName = null;
            public ?string $email = null;
        };

        $template = [
            'user' => [
                'personal' => [
                    'firstName' => 'profile.fullName',
                    'lastName' => 'profile.fullName',
                ],
                'contact' => [
                    'email' => 'profile.email',
                ],
            ],
        ];

        $result = DataMapper::source($data)
            ->target(['profile' => $profileModel])
            ->template($template)
            ->map();

        $targets = $result->getTarget();
        $profile = $targets['profile'];
        assert(is_object($profile));
        $accProfile = new DataAccessor($profile);
        expect($accProfile->get('email'))->toBe('eve@example.com');
    });
});
