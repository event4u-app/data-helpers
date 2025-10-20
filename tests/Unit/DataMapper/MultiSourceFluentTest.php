<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;

describe('Multi-Source Fluent Mapping (mapFromTemplate equivalent)', function(): void {
    test('map from multiple sources with @alias syntax', function(): void {
        $sources = [
            'user' => ['name' => 'Alice', 'age' => 30],
            'company' => ['name' => 'Acme Inc', 'country' => 'DE'],
        ];

        $template = [
            'userName' => '{{ @user.name }}',
            'userAge' => '{{ @user.age }}',
            'companyName' => '{{ @company.name }}',
            'companyCountry' => '{{ @company.country }}',
        ];

        $result = DataMapper::source($sources)
            ->target([])
            ->template($template)
            ->map();

        $data = $result->getTarget();
        expect($data)->toBe([
            'userName' => 'Alice',
            'userAge' => 30,
            'companyName' => 'Acme Inc',
            'companyCountry' => 'DE',
        ]);
    });

    test('map from multiple sources with nested paths and concatenation', function(): void {
        $sources = [
            'user' => [
                'personal' => ['firstName' => 'Bob', 'lastName' => 'Jones'],
                'contact' => ['email' => 'bob@example.com'],
            ],
            'company' => [
                'info' => ['name' => 'Tech Corp'],
            ],
        ];

        $template = [
            'fullName' => '{{ @user.personal.firstName }} {{ @user.personal.lastName }}',
            'email' => '{{ @user.contact.email }}',
            'companyName' => '{{ @company.info.name }}',
        ];

        $result = DataMapper::source($sources)
            ->target([])
            ->template($template)
            ->map();

        $data = $result->getTarget();
        expect($data['fullName'])->toBe('Bob Jones');
        expect($data['email'])->toBe('bob@example.com');
        expect($data['companyName'])->toBe('Tech Corp');
    });

    test('map from multiple sources with default values', function(): void {
        $sources = [
            'user' => ['name' => 'charlie'],
            'company' => [],
        ];

        $template = [
            'userName' => '{{ @user.name }}',
            'companyName' => '{{ @company.name ?? "Unknown" }}',
        ];

        $result = DataMapper::source($sources)
            ->target([])
            ->template($template)
            ->skipNull(false)
            ->map();

        $data = $result->getTarget();
        expect($data['userName'])->toBe('charlie');
        expect($data['companyName'])->toBe('Unknown');
    });

    test('map from multiple sources with skipNull', function(): void {
        $sources = [
            'user' => ['name' => 'David'],
            'company' => ['name' => null],
        ];

        $template = [
            'userName' => '{{ @user.name }}',
            'companyName' => '{{ @company.name }}',
        ];

        $result = DataMapper::source($sources)
            ->target([])
            ->template($template)
            ->skipNull(true)
            ->map();

        $data = $result->getTarget();
        expect($data)->toBe(['userName' => 'David']);
        expect($data)->not->toHaveKey('companyName');
    });

    test('map from multiple sources to object target', function(): void {
        $sources = [
            'user' => ['name' => 'Eve', 'age' => '25'],
            'company' => ['name' => 'StartUp Inc'],
        ];

        $profile = new class {
            public ?string $userName = null;
            public ?string $userAge = null;
            public ?string $companyName = null;
        };

        $template = [
            'userName' => '{{ @user.name }}',
            'userAge' => '{{ @user.age }}',
            'companyName' => '{{ @company.name }}',
        ];

        $result = DataMapper::source($sources)
            ->target($profile)
            ->template($template)
            ->map();

        $data = $result->getTarget();
        assert(is_object($data));
        $acc = new DataAccessor($data);
        expect($acc->get('userName'))->toBe('Eve');
        expect($acc->get('userAge'))->toBe('25');
        expect($acc->get('companyName'))->toBe('StartUp Inc');
    });
});
