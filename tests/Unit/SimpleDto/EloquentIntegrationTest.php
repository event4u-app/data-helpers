<?php

declare(strict_types=1);

// Skip this file entirely if Laravel is not installed
if (!class_exists('Illuminate\Database\Eloquent\Model')) {
    return;
}

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Mock Eloquent Model for testing.
 */
class TestUserModel extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

describe('Eloquent Integration', function(): void {
    describe('fromModel()', function(): void {
        it('creates Dto from model', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new TestUserModel();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
        });

        it('handles model with extra attributes', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $model = new TestUserModel();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
        });

        it('handles model with missing optional attributes', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?int $age = null,
                ) {}
            };

            $model = new TestUserModel();
            $model->setRawAttributes([
                'name' => 'John Doe',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
            expect($instance->age)->toBeNull();
        });

        it('throws exception if model is not an Eloquent Model', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $invalidModel = new class {
                public string $name = 'John';
            };

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): object => $dto::fromModel($invalidModel))
                ->toThrow(TypeError::class);
        });
    });

    describe('toModel()', function(): void {
        it('creates model from Dto', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel(TestUserModel::class);

            expect($model->toArray())->toBe([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('sets exists flag when requested', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel(TestUserModel::class, exists: true);

            expect($model->exists)->toBeTrue();
        });

        it('does not set exists flag by default', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel(TestUserModel::class);

            expect($model->exists)->toBeFalse();
        });

        it('throws exception if model class does not exist', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): Model => $instance->toModel('NonExistentClass'))
                ->toThrow(InvalidArgumentException::class, 'Model class NonExistentClass does not exist');
        });

        it('throws exception if model class is not an Eloquent Model', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $invalidModelClass = new class
            {
            };

            $instance = $dto::fromArray([]);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): Model => $instance->toModel($invalidModelClass::class))
                ->toThrow(InvalidArgumentException::class, 'must extend');
        });
    });

    describe('Round-trip (Model → Dto → Model)', function(): void {
        it('preserves data in round-trip', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly int $age = 0,
                ) {}
            };

            $originalModel = new TestUserModel();
            $originalModel->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            // Model → Dto
            $dtoInstance = $dto::fromModel($originalModel);

            // Dto → Model
            $newModel = $dtoInstance->toModel(TestUserModel::class);

            expect($newModel->toArray())->toBe($originalModel->toArray());
        });

        it('handles multiple round-trips', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly int $count = 0,
                ) {}
            };

            $model1 = new TestUserModel();
            $model1->setRawAttributes(['name' => 'Test', 'count' => 1]);

            $dto1 = $dto::fromModel($model1);
            $model2 = $dto1->toModel(TestUserModel::class);
            $dto2 = $dto::fromModel($model2);
            $model3 = $dto2->toModel(TestUserModel::class);

            expect($model3->toArray())->toBe($model1->toArray());
        });
    });

    describe('Update Model from Dto', function(): void {
        it('updates existing model with Dto data', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new TestUserModel();
            $model->setRawAttributes([
                'name' => 'Old Name',
                'email' => 'old@example.com',
            ]);

            $updateDto = $dto::fromArray([
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

            $model->fill($updateDto->toArray());

            expect($model->toArray())->toBe([
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);
        });

        it('can update model with filtered data', function(): void {
            $dto = new class extends SimpleDto {
                use SimpleDtoEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new TestUserModel();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            // Create update Dto with only name
            $updateDto = $dto::fromArray([
                'name' => 'Jane Doe',
                'email' => 'john@example.com', // Keep same email
            ]);

            // Use only() to filter which fields to update
            $updateData = $updateDto->only(['name'])->toArray();
            $model->fill($updateData);

            /** @phpstan-ignore-next-line unknown */
            expect($model->name)->toBe('Jane Doe');
            /** @phpstan-ignore-next-line unknown */
            expect($model->email)->toBe('john@example.com');
        });
    });
});
