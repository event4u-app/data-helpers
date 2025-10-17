<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentTrait;
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
    beforeEach(function(): void {
        $this->mockModelClass = TestUserModel::class;
    });

    describe('fromModel()', function(): void {
        it('creates DTO from model', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new $this->mockModelClass();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
            expect($instance->email)->toBe('john@example.com');
        });

        it('handles model with extra attributes', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $model = new $this->mockModelClass();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
        });

        it('handles model with missing optional attributes', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly ?int $age = null,
                ) {}
            };

            $model = new $this->mockModelClass();
            $model->setRawAttributes([
                'name' => 'John Doe',
            ]);

            $instance = $dto::fromModel($model);

            expect($instance->name)->toBe('John Doe');
            expect($instance->age)->toBeNull();
        });

        it('throws exception if model is not an Eloquent Model', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $invalidModel = new class {
                public string $name = 'John';
            };

            expect(fn() => $dto::fromModel($invalidModel))
                ->toThrow(TypeError::class);
        });
    });

    describe('toModel()', function(): void {
        it('creates model from DTO', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                    public readonly string $email = 'john@example.com',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel($this->mockModelClass);

            expect($model->toArray())->toBe([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('sets exists flag when requested', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel($this->mockModelClass, exists: true);

            expect($model->exists)->toBeTrue();
        });

        it('does not set exists flag by default', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);
            $model = $instance->toModel($this->mockModelClass);

            expect($model->exists)->toBeFalse();
        });

        it('throws exception if model class does not exist', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $instance = $dto::fromArray([]);

            expect(fn() => $instance->toModel('NonExistentClass'))
                ->toThrow(InvalidArgumentException::class, 'Model class NonExistentClass does not exist');
        });

        it('throws exception if model class is not an Eloquent Model', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = 'John Doe',
                ) {}
            };

            $invalidModelClass = new class {
                public function __construct() {}
            };

            $instance = $dto::fromArray([]);

            expect(fn() => $instance->toModel($invalidModelClass::class))
                ->toThrow(InvalidArgumentException::class, 'must extend');
        });
    });

    describe('Round-trip (Model → DTO → Model)', function(): void {
        it('preserves data in round-trip', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                    public readonly int $age = 0,
                ) {}
            };

            $originalModel = new $this->mockModelClass();
            $originalModel->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            // Model → DTO
            $dtoInstance = $dto::fromModel($originalModel);

            // DTO → Model
            $newModel = $dtoInstance->toModel($this->mockModelClass);

            expect($newModel->toArray())->toBe($originalModel->toArray());
        });

        it('handles multiple round-trips', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly int $count = 0,
                ) {}
            };

            $model1 = new $this->mockModelClass();
            $model1->setRawAttributes(['name' => 'Test', 'count' => 1]);

            $dto1 = $dto::fromModel($model1);
            $model2 = $dto1->toModel($this->mockModelClass);
            $dto2 = $dto::fromModel($model2);
            $model3 = $dto2->toModel($this->mockModelClass);

            expect($model3->toArray())->toBe($model1->toArray());
        });
    });

    describe('Update Model from DTO', function(): void {
        it('updates existing model with DTO data', function(): void {
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new $this->mockModelClass();
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
            $dto = new class extends SimpleDTO {
                use SimpleDTOEloquentTrait;

                public function __construct(
                    public readonly string $name = '',
                    public readonly string $email = '',
                ) {}
            };

            $model = new $this->mockModelClass();
            $model->setRawAttributes([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            // Create update DTO with only name
            $updateDto = $dto::fromArray([
                'name' => 'Jane Doe',
                'email' => 'john@example.com', // Keep same email
            ]);

            // Use only() to filter which fields to update
            $updateData = $updateDto->only(['name'])->toArray();
            $model->fill($updateData);

            expect($model->name)->toBe('Jane Doe');
            expect($model->email)->toBe('john@example.com');
        });
    });
});

