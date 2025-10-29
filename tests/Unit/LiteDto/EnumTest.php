<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\EnumSerialize;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test Enums
enum LiteDtoStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

enum LiteDtoRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}

enum LiteDtoPriority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

enum LiteDtoColor
{
    case RED;
    case GREEN;
    case BLUE;
}

// Test DTOs
class LiteDtoEnumUserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly LiteDtoStatus $status,
        public readonly LiteDtoRole $role,
    ) {}
}

class LiteDtoEnumTaskDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        public readonly LiteDtoPriority $priority,
    ) {}
}

class LiteDtoEnumSerializeModeDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[EnumSerialize('value')]
        public readonly LiteDtoStatus $statusValue,
        #[EnumSerialize('name')]
        public readonly LiteDtoStatus $statusName,
        #[EnumSerialize('both')]
        public readonly LiteDtoStatus $statusBoth,
    ) {}
}

class LiteDtoUnitEnumDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly LiteDtoColor $color,
    ) {}
}

describe('Enum Support', function(): void {
    describe('BackedEnum (string)', function(): void {
        it('casts string to BackedEnum', function(): void {
            $dto = LiteDtoEnumUserDto::from([
                'name' => 'John',
                'status' => 'active',
                'role' => 'admin',
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->status)->toBe(LiteDtoStatus::ACTIVE)
                ->and($dto->role)->toBe(LiteDtoRole::ADMIN);
        });

        it('serializes BackedEnum to value by default', function(): void {
            $dto = LiteDtoEnumUserDto::from([
                'name' => 'John',
                'status' => 'active',
                'role' => 'admin',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'status' => 'active',
                'role' => 'admin',
            ]);
        });

        it('handles all enum cases', function(): void {
            $dto1 = LiteDtoEnumUserDto::from(['name' => 'User1', 'status' => 'active', 'role' => 'admin']);
            $dto2 = LiteDtoEnumUserDto::from(['name' => 'User2', 'status' => 'inactive', 'role' => 'user']);
            $dto3 = LiteDtoEnumUserDto::from(['name' => 'User3', 'status' => 'pending', 'role' => 'guest']);

            expect($dto1->status)->toBe(LiteDtoStatus::ACTIVE)
                ->and($dto2->status)->toBe(LiteDtoStatus::INACTIVE)
                ->and($dto3->status)->toBe(LiteDtoStatus::PENDING);
        });
    });

    describe('BackedEnum (int)', function(): void {
        it('casts int to BackedEnum', function(): void {
            $dto = LiteDtoEnumTaskDto::from([
                'title' => 'Fix bug',
                'priority' => 3,
            ]);

            expect($dto->title)->toBe('Fix bug')
                ->and($dto->priority)->toBe(LiteDtoPriority::HIGH);
        });

        it('serializes int BackedEnum to value', function(): void {
            $dto = LiteDtoEnumTaskDto::from([
                'title' => 'Fix bug',
                'priority' => 2,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'title' => 'Fix bug',
                'priority' => 2,
            ]);
        });
    });

    describe('UnitEnum', function(): void {
        it('casts string to UnitEnum by name', function(): void {
            $dto = LiteDtoUnitEnumDto::from([
                'name' => 'Test',
                'color' => 'RED',
            ]);

            expect($dto->name)->toBe('Test')
                ->and($dto->color)->toBe(LiteDtoColor::RED);
        });

        it('serializes UnitEnum to name', function(): void {
            $dto = LiteDtoUnitEnumDto::from([
                'name' => 'Test',
                'color' => 'GREEN',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Test',
                'color' => 'GREEN',
            ]);
        });
    });

    describe('EnumSerialize Attribute', function(): void {
        it('serializes with mode "value"', function(): void {
            $dto = LiteDtoEnumSerializeModeDto::from([
                'name' => 'Test',
                'statusValue' => 'active',
                'statusName' => 'active',
                'statusBoth' => 'active',
            ]);

            $array = $dto->toArray();

            expect($array['statusValue'])->toBe('active');
        });

        it('serializes with mode "name"', function(): void {
            $dto = LiteDtoEnumSerializeModeDto::from([
                'name' => 'Test',
                'statusValue' => 'active',
                'statusName' => 'active',
                'statusBoth' => 'active',
            ]);

            $array = $dto->toArray();

            expect($array['statusName'])->toBe('ACTIVE');
        });

        it('serializes with mode "both"', function(): void {
            $dto = LiteDtoEnumSerializeModeDto::from([
                'name' => 'Test',
                'statusValue' => 'active',
                'statusName' => 'active',
                'statusBoth' => 'active',
            ]);

            $array = $dto->toArray();

            expect($array['statusBoth'])->toBe([
                'name' => 'ACTIVE',
                'value' => 'active',
            ]);
        });
    });

    describe('JSON Serialization', function(): void {
        it('serializes enum to JSON', function(): void {
            $dto = LiteDtoEnumUserDto::from([
                'name' => 'John',
                'status' => 'active',
                'role' => 'admin',
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"John","status":"active","role":"admin"}');
        });
    });

    describe('Error Handling', function(): void {
        it('throws exception for invalid enum value', function(): void {
            LiteDtoEnumUserDto::from([
                'name' => 'John',
                'status' => 'invalid',
                'role' => 'admin',
            ]);
        })->throws(ValueError::class);

        it('throws exception for invalid enum name', function(): void {
            LiteDtoUnitEnumDto::from([
                'name' => 'Test',
                'color' => 'INVALID',
            ]);
        })->throws(InvalidArgumentException::class);
    });
});
