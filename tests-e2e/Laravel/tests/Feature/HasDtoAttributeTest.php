<?php

declare(strict_types=1);

use E2E\Laravel\Dtos\UserDto;
use E2E\Laravel\Models\User;

describe('Laravel HasDto Attribute E2E', function(): void {
    it('creates DTO from model using HasDto attribute', function(): void {
        $model = new User();
        $model->name = 'John Doe';
        $model->email = 'john@example.com';

        // toDto() without parameter should use HasDto attribute
        $dto = $model->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('John Doe');
        expect($dto->email)->toBe('john@example.com');
    });

    it('allows overriding DTO class in toDto()', function(): void {
        $model = new User();
        $model->name = 'Override Test';
        $model->email = 'override@example.com';

        // Create anonymous DTO class for testing override
        $customDto = new class('', '') extends \event4u\DataHelpers\SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {
            }
        };

        $dto = $model->toDto($customDto::class);

        expect($dto)->toBeInstanceOf($customDto::class);
        expect($dto->name)->toBe('Override Test');
        expect($dto->email)->toBe('override@example.com');
    });

    it('handles model with relationships', function(): void {
        $model = new User();
        $model->name = 'Relationship Test';
        $model->email = 'relationship@test.com';

        $dto = $model->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Relationship Test');
        expect($dto->email)->toBe('relationship@test.com');
    });

    it('throws exception when no DTO class provided and no attribute', function(): void {
        // Create model without HasDto attribute
        $modelWithoutAttribute = new class extends \Illuminate\Database\Eloquent\Model {
            use \event4u\DataHelpers\Traits\DtoMappingTrait;

            protected $fillable = ['name', 'email'];
        };

        $modelWithoutAttribute->name = 'Test'; // @phpstan-ignore property.notFound
        $modelWithoutAttribute->email = 'test@example.com'; // @phpstan-ignore property.notFound

        expect(fn() => $modelWithoutAttribute->toDto())
            ->toThrow(InvalidArgumentException::class);
    });

    it('round-trip conversion Model -> DTO -> Model', function(): void {
        $originalModel = new User();
        $originalModel->name = 'Round Trip';
        $originalModel->email = 'roundtrip@test.com';

        // Model -> DTO
        $dto = $originalModel->toDto();

        expect($dto)->toBeInstanceOf(UserDto::class);
        expect($dto->name)->toBe('Round Trip');
        expect($dto->email)->toBe('roundtrip@test.com');

        // DTO -> Model
        $newModel = $dto->toModel();

        expect($newModel)->toBeInstanceOf(User::class);
        expect($newModel->name)->toBe('Round Trip');
        expect($newModel->email)->toBe('roundtrip@test.com');
    });
});

