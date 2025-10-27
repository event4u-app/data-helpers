<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

// Skip this file entirely if Laravel is not installed
if (!class_exists('Illuminate\Database\Eloquent\Model')) {
    return;
}

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;

/**
 * Mock Eloquent Model for edge case testing.
 */
class EdgeCaseTestModel extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    // Accessor
    public function getFullNameAttribute(): string
    {
        return ($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? '');
    }

    // Mutator
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }
}

describe('Eloquent Integration Edge Cases', function(): void {
    describe('fromModel() Edge Cases', function(): void {
        it('handles model with null values', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                    public readonly ?int $age = null,
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'name' => 'John',
                'email' => null,
                'age' => null,
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John');
            expect($instance->email)->toBeNull();
            expect($instance->age)->toBeNull();
        });

        it('handles model with empty strings', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'name' => '',
                'email' => '',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('');
            expect($instance->email)->toBe('');
        });

        it('handles model with accessor attributes', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $first_name = '',
                    public readonly string $last_name = '',
                    public readonly string $full_name = '',
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->first_name)->toBe('John');
            expect($instance->last_name)->toBe('Doe');
            // Accessor is not included in toArray() by default unless appended
            expect($instance->full_name)->toBe('');
        });

        it('handles model with numeric strings', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $code = '',
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'id' => 123,
                'code' => '456',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->id)->toBe(123);
            expect($instance->code)->toBe('456');
        });

        it('handles model with boolean values', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly int $is_active = 0,
                    public readonly int $is_verified = 0,
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'is_active' => 1,
                'is_verified' => 0,
            ]);

            $instance = $dto::fromModel($model);

            // Model toArray() returns integers for boolean columns
            expect($instance->is_active)->toBe(1);
            expect($instance->is_verified)->toBe(0);
        });
    });

    describe('toModel() Edge Cases', function(): void {
        it('handles Dto with null values', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                    public readonly ?int $age = null,
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'email' => null,
                'age' => null,
            ]);

            $model = $instance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($model->name)->toBe('John');
            /** @phpstan-ignore-next-line unknown */
            expect($model->email)->toBeNull();
            /** @phpstan-ignore-next-line unknown */
            expect($model->age)->toBeNull();
        });

        it('handles Dto with empty strings', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => '',
                'email' => '',
            ]);

            $model = $instance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($model->name)->toBe('');
            // Empty string is converted to null by mutator
            /** @phpstan-ignore-next-line unknown */
            expect($model->email)->toBeNull();
        });

        it('applies model mutators', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $email = '',
                ) {}
            };

            $instance = $dto::fromArray([
                'email' => 'JOHN@EXAMPLE.COM',
            ]);

            $model = $instance->toModel(EdgeCaseTestModel::class);

            // Mutator converts to lowercase
            /** @phpstan-ignore-next-line unknown */
            expect($model->email)->toBe('john@example.com');
        });

        it('handles Dto with extra properties not in model', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $extraField = '',
                ) {}
            };

            $instance = $dto::fromArray([
                'name' => 'John',
                'extraField' => 'Extra',
            ]);

            $model = $instance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($model->name)->toBe('John');
            // extraField is set but may not be in database
            /** @phpstan-ignore-next-line unknown */
            expect($model->extraField)->toBe('Extra');
        });

        it('handles exists flag correctly', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John']);

            $newModel = $instance->toModel(EdgeCaseTestModel::class, exists: false);
            $existingModel = $instance->toModel(EdgeCaseTestModel::class, exists: true);

            expect($newModel->exists)->toBeFalse();
            expect($existingModel->exists)->toBeTrue();
        });
    });

    describe('Round-trip Edge Cases', function(): void {
        it('preserves null values in round-trip', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'name' => 'John',
                'email' => null,
            ]);

            $dtoInstance = $dto::fromModel($model);
            $newModel = $dtoInstance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($newModel->name)->toBe('John');
            /** @phpstan-ignore-next-line unknown */
            expect($newModel->email)->toBeNull();
        });

        it('preserves empty strings in round-trip', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $email = null,
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes([
                'name' => '',
                'email' => '',
            ]);

            $dtoInstance = $dto::fromModel($model);
            $newModel = $dtoInstance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($newModel->name)->toBe('');
            // Empty string is converted to null by mutator
            /** @phpstan-ignore-next-line unknown */
            expect($newModel->email)->toBeNull();
        });

        it('preserves integer values in round-trip', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly int $is_active = 0,
                ) {}
            };

            $model = new EdgeCaseTestModel();
            $model->setRawAttributes(['is_active' => 1]);

            $dtoInstance = $dto::fromModel($model);
            $newModel = $dtoInstance->toModel(EdgeCaseTestModel::class);

            /** @phpstan-ignore-next-line unknown */
            expect($newModel->is_active)->toBe(1);
        });
    });
});
