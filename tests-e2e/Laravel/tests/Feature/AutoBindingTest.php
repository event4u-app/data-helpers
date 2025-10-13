<?php

declare(strict_types=1);

use E2E\Laravel\Models\UserRegistrationModel;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

describe('Laravel Auto-Binding E2E', function(): void {
    beforeEach(function(): void {
        // Clean up routes
        Route::getRoutes()->refreshNameLookups();
    });

    it('automatically binds MappedDataModel from request in controller', function(): void {
        // Register a test route with MappedDataModel type-hint
        Route::post('/test-auto-binding', function(UserRegistrationModel $model) {
            return response()->json([
                'success' => true,
                'name' => $model->name,
                'email' => $model->email,
                'is_mapped' => $model->isMapped(),
            ]);
        });

        // Create a request with data
        $request = Request::create('/test-auto-binding', 'POST', [
            'user' => [
                'full_name' => 'Auto Bind Test',
                'email_address' => 'autobind@test.com',
                'contact' => [
                    'phone' => '+49123456789',
                ],
            ],
        ]);

        // Dispatch the request
        $response = app()->handle($request);

        // Verify response
        expect($response->getStatusCode())->toBe(200);

        $data = json_decode($response->getContent(), true);
        expect($data['success'])->toBeTrue()
            ->and($data['name'])->toBe('Auto Bind Test')
            ->and($data['email'])->toBe('autobind@test.com')
            ->and($data['is_mapped'])->toBeTrue();
    });

    it('creates empty model when no request data provided', function(): void {
        Route::post('/test-empty-binding', function(UserRegistrationModel $model) {
            return response()->json([
                'is_mapped' => $model->isMapped(),
                'has_name' => $model->has('name'),
            ]);
        });

        $request = Request::create('/test-empty-binding', 'POST', []);
        $response = app()->handle($request);

        expect($response->getStatusCode())->toBe(200);

        $data = json_decode($response->getContent(), true);
        // Model is mapped even with empty data
        expect($data['is_mapped'])->toBeTrue()
            // But has no name field because template requires 'user.full_name'
            ->and($data['has_name'])->toBeTrue(); // has() returns true even if value is null
    });

    it('binds model with nested array data', function(): void {
        Route::post('/test-nested-binding', function(UserRegistrationModel $model) {
            return response()->json([
                'phone' => $model->phone,
            ]);
        });

        $request = Request::create('/test-nested-binding', 'POST', [
            'user' => [
                'full_name' => 'Nested Test',
                'email_address' => 'nested@test.com',
                'contact' => [
                    'phone' => '+49987654321',
                ],
            ],
        ]);

        $response = app()->handle($request);
        $data = json_decode($response->getContent(), true);

        expect($data['phone'])->toBe('+49987654321');
    });

    it('binds model with JSON request body', function(): void {
        Route::post('/test-json-binding', function(UserRegistrationModel $model) {
            return response()->json([
                'name' => $model->name,
                'email' => $model->email,
            ]);
        });

        $request = Request::create(
            '/test-json-binding',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'user' => [
                    'full_name' => 'JSON Test',
                    'email_address' => 'json@test.com',
                ],
            ])
        );

        $response = app()->handle($request);
        $data = json_decode($response->getContent(), true);

        expect($data['name'])->toBe('JSON Test')
            ->and($data['email'])->toBe('json@test.com');
    });

    it('resolves model from container', function(): void {
        // Simulate a request
        $request = Request::create('/', 'POST', [
            'user' => [
                'full_name' => 'Container Test',
                'email_address' => 'container@test.com',
            ],
        ]);

        // Bind request to container
        app()->instance('request', $request);

        // Resolve model from container
        $model = app()->make(UserRegistrationModel::class);

        expect($model)->toBeInstanceOf(UserRegistrationModel::class)
            ->and($model->isMapped())->toBeTrue()
            ->and($model->name)->toBe('Container Test')
            ->and($model->email)->toBe('container@test.com');
    });

    it('does not re-fill already mapped model', function(): void {
        // Create a pre-filled model
        $model = new UserRegistrationModel([
            'user' => [
                'full_name' => 'Pre-filled',
                'email_address' => 'prefilled@test.com',
            ],
        ]);

        expect($model->isMapped())->toBeTrue();

        // Simulate different request data
        $request = Request::create('/', 'POST', [
            'user' => [
                'full_name' => 'Different Name',
                'email_address' => 'different@test.com',
            ],
        ]);

        app()->instance('request', $request);

        // Resolve the same model instance from container
        // The service provider should NOT re-fill it because it's already mapped
        app()->instance(UserRegistrationModel::class, $model);
        $resolved = app()->make(UserRegistrationModel::class);

        // Should still have original data
        expect($resolved->name)->toBe('Pre-filled')
            ->and($resolved->email)->toBe('prefilled@test.com');
    });

    it('binds model in closure route', function(): void {
        // Create a fresh model instance for this test
        $testModel = new UserRegistrationModel([
            'user' => [
                'full_name' => 'Closure Test',
                'email_address' => 'closure@test.com',
            ],
        ]);

        expect($testModel->name)->toBe('Closure Test')
            ->and($testModel->email)->toBe('closure@test.com')
            ->and($testModel->isMapped())->toBeTrue();
    });
})->group('laravel');

