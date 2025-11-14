<?php

declare(strict_types=1);

use E2E\Laravel\Models\User;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\Attributes\LaravelModelFillable;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;

describe('Laravel LaravelModelFillable Attribute E2E', function(): void {
    it('uses fillable parameter to override model fillable', function(): void {
        // Create DTO without LaravelModelFillable attribute
        $dto = new class('John Doe', 'john@example.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // User model only has 'name' and 'email' as fillable
        // Pass fillable parameter to allow 'role' as well
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['name', 'email', 'role']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('John Doe'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('john@example.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('admin'); // @phpstan-ignore property.notFound
    });

    it('uses fillable parameter with wildcard to allow all properties', function(): void {
        $dto = new class('Jane Smith', 'jane@example.com', 'user', 'secret') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
                public readonly string $password,
            ) {
            }
        };

        // Use ['*'] to make all properties fillable
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['*']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Jane Smith'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('jane@example.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('user'); // @phpstan-ignore property.notFound
        expect($model->password)->toBe('secret'); // @phpstan-ignore property.notFound
    });

    it('uses LaravelModelFillable attribute on specific properties', function(): void {
        $dtoClass = new #[HasModel(User::class)] class('Property Test', 'property@test.com', 'editor') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            #[LaravelModelFillable]
            public readonly string $name;

            #[LaravelModelFillable]
            public readonly string $email;

            #[LaravelModelFillable]
            public readonly string $role;

            public function __construct(string $name, string $email, string $role)
            {
                $this->name = $name;
                $this->email = $email;
                $this->role = $role;
            }
        };

        // toModel() should use properties marked with LaravelModelFillable
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel();

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Property Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('property@test.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('editor'); // @phpstan-ignore property.notFound
    });

    it('uses LaravelModelFillable attribute on class to make all properties fillable', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('Class Test', 'class@test.com', 'admin', 'password123') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
                public readonly string $password,
            ) {
            }
        };

        // toModel() should make all properties fillable
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel();

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Class Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('class@test.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('admin'); // @phpstan-ignore property.notFound
        expect($model->password)->toBe('password123'); // @phpstan-ignore property.notFound
    });

    it('respects model fillable when no fillable parameter or attribute', function(): void {
        $dto = new class('Respect Test', 'respect@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // User model only has 'name' and 'email' as fillable
        // 'role' should NOT be filled
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Respect Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('respect@test.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBeNull(); // Not fillable, should be null // @phpstan-ignore property.notFound
    });

    it('fillable parameter takes priority over LaravelModelFillable attribute', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('Priority Test', 'priority@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // fillable parameter should override the attribute
        // Only 'name' and 'email' should be fillable
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel(fillable: ['name', 'email']);

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Priority Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('priority@test.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBeNull(); // Not in fillable parameter, should be null // @phpstan-ignore property.notFound
    });

    it('restores original fillable after toModel', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('Restore Test', 'restore@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // Get original fillable from User model
        $originalModel = new User();
        $originalFillable = $originalModel->getFillable();

        // Convert DTO to model (this temporarily changes fillable)
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel();

        // Create a new model instance to check if fillable was restored
        $newModel = new User();
        $newFillable = $newModel->getFillable();

        expect($newFillable)->toBe($originalFillable);
    });
});

