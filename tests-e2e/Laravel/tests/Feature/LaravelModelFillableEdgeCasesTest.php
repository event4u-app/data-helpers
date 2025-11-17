<?php

declare(strict_types=1);

use E2E\Laravel\Models\User;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\Attributes\LaravelModelFillable;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;
use Illuminate\Database\Eloquent\Model;

describe('Laravel LaravelModelFillable Edge Cases E2E', function(): void {
    it('handles empty fillable array', function(): void {
        $dto = new class('John Doe', 'john@example.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        // Empty fillable array should result in no properties being filled
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: []); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBeNull(); // @phpstan-ignore property.notFound
        expect($model->email)->toBeNull(); // @phpstan-ignore property.notFound
    });

    it('handles fillable with non-existent properties', function(): void {
        $dto = new class('Test User', 'test@example.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        // fillable contains properties that don't exist in DTO
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['name', 'email', 'nonexistent', 'another_fake']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Test User'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('test@example.com'); // @phpstan-ignore property.notFound
        // Non-existent properties should be ignored
    });

    it('handles model with guarded properties', function(): void {
        // Create model with guarded properties
        $guardedModel = new class extends Model {
            protected $guarded = ['id', 'created_at', 'updated_at'];
            protected $fillable = [];
        };

        $dto = new class('Guarded Test', 'guarded@test.com', 1) extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly int $id,
            ) {
            }
        };

        // Use wildcard to override guarded
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel($guardedModel::class, fillable: ['*']);

        expect($model)->toBeInstanceOf($guardedModel::class);
        expect($model->name)->toBe('Guarded Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('guarded@test.com'); // @phpstan-ignore property.notFound
        expect($model->id)->toBe(1); // @phpstan-ignore property.notFound
    });

    it('handles multiple toModel calls on same DTO', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('Multiple Test', 'multiple@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // First call with attribute
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model1 = $dtoClass->toModel();
        expect($model1->name)->toBe('Multiple Test'); // @phpstan-ignore property.notFound
        expect($model1->role)->toBe('admin'); // @phpstan-ignore property.notFound

        // Second call with parameter override
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model2 = $dtoClass->toModel(fillable: ['name', 'email']);
        expect($model2->name)->toBe('Multiple Test'); // @phpstan-ignore property.notFound
        expect($model2->email)->toBe('multiple@test.com'); // @phpstan-ignore property.notFound
        expect($model2->role)->toBeNull(); // Not in fillable parameter // @phpstan-ignore property.notFound

        // Third call without any fillable
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model3 = $dtoClass->toModel(fillable: []);
        expect($model3->name)->toBeNull(); // @phpstan-ignore property.notFound
        expect($model3->email)->toBeNull(); // @phpstan-ignore property.notFound
    });

    it('handles wildcard with exists flag', function(): void {
        $dto = new class('Exists Test', 'exists@test.com', 'editor', 'secret') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
                public readonly string $password,
            ) {
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, exists: true, fillable: ['*']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->exists)->toBeTrue(); // @phpstan-ignore property.notFound
        expect($model->name)->toBe('Exists Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('exists@test.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('editor'); // @phpstan-ignore property.notFound
        expect($model->password)->toBe('secret'); // @phpstan-ignore property.notFound
    });

    it('handles DTO with null values', function(): void {
        $dto = new class(null, 'nullable@test.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly ?string $name,
                public readonly string $email,
            ) {
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['*']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBeNull(); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('nullable@test.com'); // @phpstan-ignore property.notFound
    });

    it('handles DTO with array and object properties', function(): void {
        $dto = new class('Complex Test', 'complex@test.com', ['admin', 'editor'], (object)['key' => 'value']) extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                /** @var array<string> */
                public readonly array $roles,
                public readonly object $metadata,
            ) {
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['*']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Complex Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('complex@test.com'); // @phpstan-ignore property.notFound
        expect($model->roles)->toBe(['admin', 'editor']); // @phpstan-ignore property.notFound
        expect($model->metadata)->toBeObject(); // @phpstan-ignore property.notFound
    });




    it('handles LaravelModelFillable on non-public properties', function(): void {
        // LaravelModelFillable should only work on public properties
        $dtoClass = new #[HasModel(User::class)] class('Private Test', 'private@test.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            #[LaravelModelFillable]
            public readonly string $name;

            #[LaravelModelFillable]
            private string $email; // This should be ignored // @phpstan-ignore property.onlyWritten

            public function __construct(string $name, string $email)
            {
                $this->name = $name;
                $this->email = $email;
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel();

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Private Test'); // @phpstan-ignore property.notFound
        // Private property should not be in fillable
    });

    it('handles concurrent model creation from same DTO', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('Concurrent Test', 'concurrent@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // Create multiple models from same DTO
        $models = [];
        for ($i = 0; $i < 5; $i++) {
            $models[] = $dtoClass->toModel();
        }

        // All models should be independent and have correct values
        foreach ($models as $model) {
            expect($model)->toBeInstanceOf(User::class);
            expect($model->name)->toBe('Concurrent Test'); // @phpstan-ignore property.notFound
            expect($model->email)->toBe('concurrent@test.com'); // @phpstan-ignore property.notFound
            expect($model->role)->toBe('admin'); // @phpstan-ignore property.notFound
        }

        // Models should be different instances
        expect($models[0])->not->toBe($models[1]);
    });

    it('handles fillable with case-sensitive property names', function(): void {
        $dto = new class('Case Test', 'case@test.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        // Property names are case-sensitive
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['Name', 'EMAIL']); // Wrong case // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        // Properties with wrong case should not be filled
        expect($model->name)->toBeNull(); // @phpstan-ignore property.notFound
        expect($model->email)->toBeNull(); // @phpstan-ignore property.notFound
    });

    it('handles model with both fillable and guarded set', function(): void {
        // Create model with both fillable and guarded
        $complexModel = new class extends Model {
            protected $fillable = ['name'];
            protected $guarded = ['id', 'password'];
        };

        $dto = new class('Complex Model', 'complex@model.com', 1, 'secret') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly int $id,
                public readonly string $password,
            ) {
            }
        };

        // Use wildcard to override both fillable and guarded
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel($complexModel::class, fillable: ['*']);

        expect($model)->toBeInstanceOf($complexModel::class);
        expect($model->name)->toBe('Complex Model'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('complex@model.com'); // @phpstan-ignore property.notFound
        expect($model->id)->toBe(1); // @phpstan-ignore property.notFound
        expect($model->password)->toBe('secret'); // @phpstan-ignore property.notFound
    });

    it('handles DTO with special characters in property values', function(): void {
        $dto = new class("O'Brien", 'test@example.com', '<script>alert("xss")</script>') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['*']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe("O'Brien"); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('test@example.com'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('<script>alert("xss")</script>'); // @phpstan-ignore property.notFound
    });

    it('handles very large fillable array', function(): void {
        $dto = new class('Large Test', 'large@test.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        // Create a very large fillable array
        $largeFillable = array_merge(['name', 'email'], array_map(fn($i) => "field_$i", range(1, 1000)));

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: $largeFillable); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Large Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('large@test.com'); // @phpstan-ignore property.notFound
    });

    it('handles DTO with numeric property names', function(): void {
        // This is an edge case - PHP allows numeric property names in arrays
        $dto = new class('Numeric Test', 'numeric@test.com') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dto->toModel(User::class, fillable: ['name', 'email', '123', '456']); // @phpstan-ignore argument.type

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Numeric Test'); // @phpstan-ignore property.notFound
        expect($model->email)->toBe('numeric@test.com'); // @phpstan-ignore property.notFound
    });

    it('handles LaravelModelFillable with mixed attribute placement', function(): void {
        // Mix of class-level and property-level attributes
        $dtoClass = new #[HasModel(User::class)] class('Mixed Test', 'mixed@test.com', 'admin', 'secret') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            #[LaravelModelFillable]
            public readonly string $name;

            public readonly string $email; // Not marked

            #[LaravelModelFillable]
            public readonly string $role;

            public readonly string $password; // Not marked

            public function __construct(string $name, string $email, string $role, string $password)
            {
                $this->name = $name;
                $this->email = $email;
                $this->role = $role;
                $this->password = $password;
            }
        };

        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model = $dtoClass->toModel();

        expect($model)->toBeInstanceOf(User::class);
        expect($model->name)->toBe('Mixed Test'); // @phpstan-ignore property.notFound
        expect($model->role)->toBe('admin'); // @phpstan-ignore property.notFound
        // email and password should not be filled (not marked with LaravelModelFillable)
        expect($model->email)->toBeNull(); // @phpstan-ignore property.notFound
        expect($model->password)->toBeNull(); // @phpstan-ignore property.notFound
    });

    it('handles model state preservation after fillable override', function(): void {
        $dtoClass = new #[HasModel(User::class)] #[LaravelModelFillable] class('State Test', 'state@test.com', 'admin') extends SimpleDto {
            use SimpleDtoEloquentTrait;

            public function __construct(
                public readonly string $name,
                public readonly string $email,
                public readonly string $role,
            ) {
            }
        };

        // Create first model
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model1 = $dtoClass->toModel();

        // Create second model - should not be affected by first
        /** @phpstan-ignore varTag.nativeType, class.notFound */
        $model2 = $dtoClass->toModel();

        // Both models should have correct values
        expect($model1->name)->toBe('State Test'); // @phpstan-ignore property.notFound
        expect($model2->name)->toBe('State Test'); // @phpstan-ignore property.notFound

        // Modify first model
        $model1->name = 'Modified'; // @phpstan-ignore property.notFound

        // Second model should not be affected
        expect($model2->name)->toBe('State Test'); // @phpstan-ignore property.notFound
    });
});