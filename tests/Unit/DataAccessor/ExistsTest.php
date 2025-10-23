<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor exists()', function(): void {
    it('returns true for existing path with non-null value', function(): void {
        $data = ['user' => ['name' => 'John']];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.name'))->toBeTrue();
    });

    it('returns true for existing path with null value', function(): void {
        $data = ['user' => ['name' => null]];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.name'))->toBeTrue();
    });

    it('returns false for non-existing path', function(): void {
        $data = ['user' => ['name' => 'John']];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.email'))->toBeFalse();
    });

    it('returns false for deeply nested non-existing path', function(): void {
        $data = ['user' => ['name' => 'John']];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.address.city'))->toBeFalse();
    });

    it('returns true for root level path', function(): void {
        $data = ['name' => 'John'];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('name'))->toBeTrue();
    });

    it('returns true for wildcard path if any element has the property', function(): void {
        $data = [
            'users' => [
                ['name' => 'John'],
                ['name' => 'Jane'],
            ],
        ];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('users.*.name'))->toBeTrue();
    });

    it('returns false for wildcard path if no element has the property', function(): void {
        $data = [
            'users' => [
                ['name' => 'John'],
                ['name' => 'Jane'],
            ],
        ];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('users.*.email'))->toBeFalse();
    });

    it('returns true for wildcard path with null values', function(): void {
        $data = [
            'users' => [
                ['name' => null],
                ['name' => 'Jane'],
            ],
        ];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('users.*.name'))->toBeTrue();
    });

    it('returns true for nested path with arrays containing objects', function(): void {
        $user = ['name' => 'John', 'email' => null];
        $data = ['user' => $user];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.name'))->toBeTrue();
        expect($accessor->exists('user.email'))->toBeTrue(); // exists even though value is null
    });

    it('returns false for empty data', function(): void {
        $data = [];
        $accessor = new DataAccessor($data);

        expect($accessor->exists('user.name'))->toBeFalse();
    });
});
