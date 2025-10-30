<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;
use event4u\DataHelpers\LiteDto\Attributes\EnumSerialize;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test Enums
enum UltraFastAutoDetectTest_Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum Priority
{
    case High;
    case Medium;
    case Low;
}

// Test Caster
class UpperCaseCaster
{
    public static function cast(mixed $value): string
    {
        return strtoupper((string)$value);
    }
}

// Test DTOs
#[UltraFast]
class ProductWithConvertEmptyDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[ConvertEmptyToNull]
        public readonly ?string $description,
    ) {}
}

#[UltraFast]
class TaskWithEnumDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[EnumSerialize(mode: 'name')]
        public readonly UltraFastAutoDetectTest_Status $status,
    ) {}
}

#[UltraFast]
class TaskWithUnitEnumDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[EnumSerialize(mode: 'name')]
        public readonly Priority $priority,
    ) {}
}

#[UltraFast]
class UserWithAllFeaturesDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,

        #[CastWith(UpperCaseCaster::class)]
        public readonly string $role,

        #[ConvertEmptyToNull]
        public readonly ?string $bio,

        #[MapTo('user_status')]
        #[EnumSerialize(mode: 'value')]
        public readonly UltraFastAutoDetectTest_Status $status,
    ) {}
}

describe('LiteDto UltraFast Auto-Detect', function(): void {
    describe('ConvertEmptyToNull Auto-Detection', function(): void {
        it('converts empty string to null when attribute is present', function(): void {
            $product = ProductWithConvertEmptyDto::from([
                'name' => 'Laptop',
                'description' => '',
            ]);

            expect($product->name)->toBe('Laptop');
            expect($product->description)->toBeNull();
        });

        it('converts empty array to null when attribute is present', function(): void {
            $product = ProductWithConvertEmptyDto::from([
                'name' => 'Laptop',
                'description' => [],
            ]);

            expect($product->name)->toBe('Laptop');
            expect($product->description)->toBeNull();
        });

        it('keeps non-empty values when attribute is present', function(): void {
            $product = ProductWithConvertEmptyDto::from([
                'name' => 'Laptop',
                'description' => 'A great laptop',
            ]);

            expect($product->name)->toBe('Laptop');
            expect($product->description)->toBe('A great laptop');
        });
    });

    describe('EnumSerialize Auto-Detection', function(): void {
        it('serializes backed enum with name mode', function(): void {
            $task = new TaskWithEnumDto('Fix bug', UltraFastAutoDetectTest_Status::Active);
            $array = $task->toArray();

            expect($array['title'])->toBe('Fix bug');
            expect($array['status'])->toBe('Active');
        });

        it('serializes backed enum with value mode (default)', function(): void {
            $task = new TaskWithEnumDto('Fix bug', UltraFastAutoDetectTest_Status::Inactive);
            $array = $task->toArray();

            expect($array['status'])->toBe('Inactive');
        });

        it('serializes unit enum with name mode', function(): void {
            $task = new TaskWithUnitEnumDto('Important task', Priority::High);
            $array = $task->toArray();

            expect($array['title'])->toBe('Important task');
            expect($array['priority'])->toBe('High');
        });

        it('works with toJson()', function(): void {
            $task = new TaskWithEnumDto('Fix bug', UltraFastAutoDetectTest_Status::Active);
            $json = json_decode($task->toJson(), true);

            expect($json['status'])->toBe('Active');
        });
    });

    describe('Combined Auto-Detection', function(): void {
        it('auto-detects all attributes together', function(): void {
            $user = UserWithAllFeaturesDto::from([
                'user_name' => 'John Doe',
                'role' => 'admin',
                'bio' => '',
                'status' => 'active',
            ]);

            expect($user->name)->toBe('John Doe'); // MapFrom
            expect($user->role)->toBe('ADMIN'); // CastWith
            expect($user->bio)->toBeNull(); // ConvertEmptyToNull
            expect($user->status)->toBe(UltraFastAutoDetectTest_Status::Active);
        });

        it('serializes with all attributes in toArray()', function(): void {
            $user = new UserWithAllFeaturesDto(
                'Jane Smith',
                'USER',
                null,
                UltraFastAutoDetectTest_Status::Inactive
            );
            $array = $user->toArray();

            expect($array['name'])->toBe('Jane Smith');
            expect($array['role'])->toBe('USER');
            expect($array['bio'])->toBeNull();
            expect($array['user_status'])->toBe('inactive'); // MapTo + EnumSerialize
        });

        it('works with from() and toJson()', function(): void {
            $user = UserWithAllFeaturesDto::from([
                'user_name' => 'Bob',
                'role' => 'moderator',
                'bio' => '',
                'status' => 'active',
            ]);

            $json = json_decode($user->toJson(), true);

            expect($json['name'])->toBe('Bob');
            expect($json['role'])->toBe('MODERATOR');
            expect($json['bio'])->toBeNull();
            expect($json['user_status'])->toBe('active');
        });
    });

    describe('Performance: No Overhead Without Attributes', function(): void {
        it('has minimal overhead when no attributes are used', function(): void {
            $dto = new #[UltraFast] class('test', 123) extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly int $value,
                ) {}
            };

            $start = hrtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto->toArray();
            }
            $end = hrtime(true);
            $duration = ($end - $start) / 1_000_000; // Convert to milliseconds

            // Should be very fast (< 5ms for 1000 iterations)
            expect($duration)->toBeLessThan(5.0);
        });
    });
});
