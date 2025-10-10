<?php

declare(strict_types=1);

use E2E\Laravel\Models\UserRegistrationModel;

describe('Laravel MappedDataModel E2E', function(): void {
    it('creates model instance', function(): void {
        $model = new UserRegistrationModel();

        expect($model)->toBeInstanceOf(UserRegistrationModel::class);
        expect($model->isMapped())->toBeFalse();
    });

    it('fills model from request data', function(): void {
        $data = [
            'user' => [
                'full_name' => 'John Doe',
                'email_address' => 'john@example.com',
                'contact' => [
                    'phone' => '+49123456789',
                ],
            ],
        ];

        $model = new UserRegistrationModel($data);

        expect($model->isMapped())->toBeTrue();
        expect($model->name)->toBe('John Doe');
        expect($model->email)->toBe('john@example.com');
        expect($model->phone)->toBe('+49123456789');
    });

    it('accesses original data', function(): void {
        $data = [
            'user' => [
                'full_name' => 'Jane Smith',
                'email_address' => 'jane@example.com',
            ],
        ];

        $model = new UserRegistrationModel($data);

        expect($model->getOriginalData())->toBe($data);
        expect($model->hasOriginal('user'))->toBeTrue();
    });

    it('checks if model has mapped fields', function(): void {
        $model = new UserRegistrationModel([
            'user' => [
                'full_name' => 'Test User',
                'email_address' => 'test@example.com',
            ],
        ]);

        expect($model->has('name'))->toBeTrue();
        expect($model->has('email'))->toBeTrue();
        expect($model->has('nonexistent'))->toBeFalse();
    });

    it('converts model to array', function(): void {
        $model = new UserRegistrationModel([
            'user' => [
                'full_name' => 'Array Test',
                'email_address' => 'array@test.com',
            ],
        ]);

        $array = $model->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
        expect($array['name'])->toBe('Array Test');
        expect($array['email'])->toBe('array@test.com');
    });

    it('serializes to JSON', function(): void {
        $model = new UserRegistrationModel([
            'user' => [
                'full_name' => 'JSON Test',
                'email_address' => 'json@test.com',
            ],
        ]);

        $json = json_encode($model);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('JSON Test');
        expect($decoded['email'])->toBe('json@test.com');
    });
});

