<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;
use Tests\Unit\SimpleDTO\Fixtures\ColorEnum;
use Tests\Unit\SimpleDTO\Fixtures\RoleEnum;
use Tests\Unit\SimpleDTO\Fixtures\StatusEnum;

describe('EnumCastEdgeCases', function(): void {
    describe('Enum with Mapping', function(): void {
        it('combines enum cast with MapFrom', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('order_status')]
                    public readonly StatusEnum $status,
                ) {}

                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['order_status' => 'active']);

            expect($instance->status)->toBe(StatusEnum::ACTIVE);
        });

        it('combines enum cast with MapTo', function(): void {
            $dto = new class(StatusEnum::DELIVERED) extends SimpleDTO {
                public function __construct(
                    #[MapTo('order_status')]
                    public readonly StatusEnum $status,
                ) {}

                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('order_status')
                ->and($array['order_status'])->toBe('delivered');
        });

        it('combines enum cast with MapFrom (snake_case)', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('order_status')]
                    public readonly StatusEnum $orderStatus,
                ) {}

                protected function casts(): array
                {
                    return ['orderStatus' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['order_status' => 'shipped']);

            expect($instance->orderStatus)->toBe(StatusEnum::SHIPPED);
        });

        it('combines enum cast with bidirectional mapping', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('input_status')]
                    #[MapTo('output_status')]
                    public readonly StatusEnum $status,
                ) {}

                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['input_status' => 'cancelled']);
            $array = $instance->toArray();

            expect($instance->status)->toBe(StatusEnum::CANCELLED)
                ->and($array)->toHaveKey('output_status')
                ->and($array['output_status'])->toBe('cancelled');
        });
    });

    describe('Multiple Enums', function(): void {
        it('handles multiple different enums in one DTO', function(): void {
            $dto = new class(
                status: StatusEnum::PENDING,
                role: RoleEnum::USER,
                color: ColorEnum::RED,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                    public readonly RoleEnum $role,
                    public readonly ColorEnum $color,
                ) {}

                protected function casts(): array
                {
                    return [
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                        'role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum',
                        'color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'status' => 'active',
                'role' => 3,
                'color' => 'BLUE',
            ]);

            expect($instance->status)->toBe(StatusEnum::ACTIVE)
                ->and($instance->role)->toBe(RoleEnum::ADMIN)
                ->and($instance->color)->toBe(ColorEnum::BLUE);
        });

        it('handles multiple enums with mixed valid and invalid values', function(): void {
            $dto = new class(
                status: null,
                role: null,
                color: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?StatusEnum $status,
                    public readonly ?RoleEnum $role,
                    public readonly ?ColorEnum $color,
                ) {}

                protected function casts(): array
                {
                    return [
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                        'role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum',
                        'color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'status' => 'active',
                'role' => 999,
                'color' => 'PURPLE',
            ]);

            expect($instance->status)->toBe(StatusEnum::ACTIVE)
                ->and($instance->role)->toBeNull()
                ->and($instance->color)->toBeNull();
        });
    });

    describe('Enum Arrays', function(): void {
        it('handles array of enum values', function(): void {
            $dto = new class([]) extends SimpleDTO {
                public function __construct(
                    public readonly array $statuses,
                ) {}

                protected function casts(): array
                {
                    return ['statuses' => 'array'];
                }
            };

            $instance = $dto::fromArray([
                'statuses' => ['pending', 'active', 'shipped'],
            ]);

            expect($instance->statuses)->toBe(['pending', 'active', 'shipped']);
        });
    });

    describe('Enum with Other Casts', function(): void {
        it('combines enum with datetime cast', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status = StatusEnum::PENDING,
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return [
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                        'createdAt' => 'datetime',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'status' => 'delivered',
                'createdAt' => '2024-06-15 10:30:00',
            ]);

            expect($instance->status)->toBe(StatusEnum::DELIVERED)
                ->and($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->format('Y-m-d'))->toBe('2024-06-15');
        });

        it('combines enum with decimal cast', function(): void {
            $dto = new class(
                status: StatusEnum::PENDING,
                amount: '0.00',
            ) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                    public readonly string $amount,
                ) {}

                protected function casts(): array
                {
                    return [
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                        'amount' => 'decimal:2',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'status' => 'pending',
                'amount' => '99.99',
            ]);

            expect($instance->status)->toBe(StatusEnum::PENDING)
                ->and($instance->amount)->toBe('99.99');
        });

        it('combines enum with boolean cast', function(): void {
            $dto = new class(
                status: StatusEnum::PENDING,
                active: false,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                    public readonly bool $active,
                ) {}

                protected function casts(): array
                {
                    return [
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                        'active' => 'boolean',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'status' => 'active',
                'active' => 1,
            ]);

            expect($instance->status)->toBe(StatusEnum::ACTIVE)
                ->and($instance->active)->toBeTrue();
        });
    });

    describe('Enum Case Sensitivity', function(): void {
        it('handles case-sensitive unit enum names', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?ColorEnum $color = null,
                ) {}

                protected function casts(): array
                {
                    return ['color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum'];
                }
            };

            $valid = $dto::fromArray(['color' => 'RED']);
            $invalid = $dto::fromArray(['color' => 'red']);

            // Unit enums are case-sensitive - 'RED' works, 'red' returns null
            expect($valid->color)->toBe(ColorEnum::RED)
                ->and($invalid->color)->toBeNull();
        });
    });
});

