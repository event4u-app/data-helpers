<?php

declare(strict_types=1);

use E2E\Laravel\Dtos\UserDto;
use E2E\Laravel\Models\User;

describe('Laravel HasModel Attribute E2E', function(): void {
    it('creates model from DTO using HasModel attribute', function(): void {
        $dto = new UserDto(
            name: 'John Doe',
            email: 'john@example.com'
        );

        // toModel() without parameter should use HasModel attribute
        $model = $dto->toModel();

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('John Doe');
        expect($model->email)->toBe('john@example.com');
        expect($model->exists)->toBeFalse();
    });

    it('creates existing model from DTO using HasModel attribute', function(): void {
        $dto = new UserDto(
            name: 'Jane Smith',
            email: 'jane@example.com'
        );

        // toModel() with exists=true
        $model = $dto->toModel(exists: true);

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Jane Smith');
        expect($model->email)->toBe('jane@example.com');
        expect($model->exists)->toBeTrue();
    });

    it('allows overriding model class in toModel()', function(): void {
        $dto = new UserDto(
            name: 'Override Test',
            email: 'override@example.com'
        );

        // Test override by explicitly passing User class
        $model = $dto->toModel(User::class);

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Override Test');
        expect($model->email)->toBe('override@example.com');
    });

    it('creates DTO from model using fromModel()', function(): void {
        $model = new User();
        $model->name = 'Model Test';
        $model->email = 'model@test.com';

        $dto = UserDto::fromModel($model);

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Model Test');
        expect($dto->email)->toBe('model@test.com');
    });

    it('throws exception when no model class provided and no attribute', function(): void {
        // Create DTO without HasModel attribute
        $dtoWithoutAttribute = new class('Test', 'test@example.com') extends \event4u\DataHelpers\SimpleDto {
            use \event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        expect(fn() => $dtoWithoutAttribute->toModel())
            ->toThrow(InvalidArgumentException::class);
    });
});

