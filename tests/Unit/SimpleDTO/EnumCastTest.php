<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\EnumCast;
use Tests\Unit\SimpleDTO\Fixtures\ColorEnum;
use Tests\Unit\SimpleDTO\Fixtures\RoleEnum;
use Tests\Unit\SimpleDTO\Fixtures\StatusEnum;

describe('EnumCast', function(): void {
    describe('Backed String Enum', function(): void {
        it('casts string to backed string enum', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => 'active']);

            expect($instance->status)->toBeInstanceOf(StatusEnum::class)
                ->and($instance->status)->toBe(StatusEnum::ACTIVE)
                ->and($instance->status->value)->toBe('active');
        });

        it('casts all enum cases correctly', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $pending = $dto::fromArray(['status' => 'pending']);
            $active = $dto::fromArray(['status' => 'active']);
            $inactive = $dto::fromArray(['status' => 'inactive']);
            $deleted = $dto::fromArray(['status' => 'deleted']);

            expect($pending->status)->toBe(StatusEnum::PENDING)
                ->and($active->status)->toBe(StatusEnum::ACTIVE)
                ->and($inactive->status)->toBe(StatusEnum::INACTIVE)
                ->and($deleted->status)->toBe(StatusEnum::DELETED);
        });

        it('converts enum back to string in toArray', function(): void {
            $dto = new class(StatusEnum::ACTIVE) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('status')
                ->and($array['status'])->toBe('active');
        });

        it('handles invalid enum value gracefully', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?StatusEnum $status = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => 'invalid_status']);

            expect($instance->status)->toBeNull();
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?StatusEnum $status = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => null]);

            expect($instance->status)->toBeNull();
        });

        it('uses EnumCast class directly', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => EnumCast::class.':Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => 'pending']);

            expect($instance->status)->toBe(StatusEnum::PENDING);
        });
    });

    describe('Backed Integer Enum', function(): void {
        it('casts integer to backed integer enum', function(): void {
            $dto = new class(RoleEnum::GUEST) extends SimpleDTO {
                public function __construct(
                    public readonly RoleEnum $role,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum'];
                }
            };

            $instance = $dto::fromArray(['role' => 2]);

            expect($instance->role)->toBeInstanceOf(RoleEnum::class)
                ->and($instance->role)->toBe(RoleEnum::MODERATOR)
                ->and($instance->role->value)->toBe(2);
        });

        it('casts all integer enum cases correctly', function(): void {
            $dto = new class(RoleEnum::GUEST) extends SimpleDTO {
                public function __construct(
                    public readonly RoleEnum $role,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum'];
                }
            };

            $guest = $dto::fromArray(['role' => 0]);
            $user = $dto::fromArray(['role' => 1]);
            $moderator = $dto::fromArray(['role' => 2]);
            $admin = $dto::fromArray(['role' => 3]);

            expect($guest->role)->toBe(RoleEnum::GUEST)
                ->and($user->role)->toBe(RoleEnum::USER)
                ->and($moderator->role)->toBe(RoleEnum::MODERATOR)
                ->and($admin->role)->toBe(RoleEnum::ADMIN);
        });

        it('converts integer enum back to int in toArray', function(): void {
            $dto = new class(RoleEnum::ADMIN) extends SimpleDTO {
                public function __construct(
                    public readonly RoleEnum $role,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum'];
                }
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('role')
                ->and($array['role'])->toBe(3);
        });

        it('handles invalid integer enum value', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?RoleEnum $role = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['role' => 'enum:Tests\Unit\SimpleDTO\Fixtures\RoleEnum'];
                }
            };

            $instance = $dto::fromArray(['role' => 999]);

            expect($instance->role)->toBeNull();
        });
    });

    describe('Unit Enum', function(): void {
        it('casts string to unit enum by name', function(): void {
            $dto = new class(ColorEnum::RED) extends SimpleDTO {
                public function __construct(
                    public readonly ColorEnum $color,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum'];
                }
            };

            $instance = $dto::fromArray(['color' => 'RED']);

            expect($instance->color)->toBeInstanceOf(ColorEnum::class)
                ->and($instance->color)->toBe(ColorEnum::RED)
                ->and($instance->color->name)->toBe('RED');
        });

        it('casts all unit enum cases correctly', function(): void {
            $dto = new class(ColorEnum::RED) extends SimpleDTO {
                public function __construct(
                    public readonly ColorEnum $color,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum'];
                }
            };

            $red = $dto::fromArray(['color' => 'RED']);
            $green = $dto::fromArray(['color' => 'GREEN']);
            $blue = $dto::fromArray(['color' => 'BLUE']);
            $yellow = $dto::fromArray(['color' => 'YELLOW']);

            expect($red->color)->toBe(ColorEnum::RED)
                ->and($green->color)->toBe(ColorEnum::GREEN)
                ->and($blue->color)->toBe(ColorEnum::BLUE)
                ->and($yellow->color)->toBe(ColorEnum::YELLOW);
        });

        it('converts unit enum back to name in toArray', function(): void {
            $dto = new class(ColorEnum::BLUE) extends SimpleDTO {
                public function __construct(
                    public readonly ColorEnum $color,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum'];
                }
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('color')
                ->and($array['color'])->toBe('BLUE');
        });

        it('handles invalid unit enum name', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?ColorEnum $color = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['color' => 'enum:Tests\Unit\SimpleDTO\Fixtures\ColorEnum'];
                }
            };

            $instance = $dto::fromArray(['color' => 'PURPLE']);

            expect($instance->color)->toBeNull();
        });
    });

    describe('Edge Cases', function(): void {
        it('handles already enum instance', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => StatusEnum::ACTIVE]);

            expect($instance->status)->toBe(StatusEnum::ACTIVE);
        });

        it('handles non-existent enum class', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $status = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:NonExistentEnum'];
                }
            };

            $instance = $dto::fromArray(['status' => 'active']);

            expect($instance->status)->toBeNull();
        });

        it('works with JSON serialization', function(): void {
            $dto = new class(StatusEnum::PENDING) extends SimpleDTO {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum'];
                }
            };

            $json = json_encode($dto);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('status')
                ->and($decoded['status'])->toBe('pending');
        });
    });
});

