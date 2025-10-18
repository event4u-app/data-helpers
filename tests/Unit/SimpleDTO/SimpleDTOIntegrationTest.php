<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use Tests\Unit\SimpleDTO\Fixtures\StatusEnum;

describe('SimpleDTOIntegration', function(): void {
    describe('Casts + Validation', function(): void {
        it('validates and casts together', function(): void {
            $dto = new class(
                email: '',
                age: 0,
                price: '0.00',
            ) extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,
                    #[Required]
                    #[Between(18, 120)]
                    public readonly int $age,
                    #[Required]
                    public readonly string $price,
                ) {}

                protected function casts(): array
                {
                    return [
                        'age' => 'integer',
                        'price' => 'decimal:2',
                    ];
                }
            };

            $instance = $dto::validateAndCreate([
                'email' => 'test@example.com',
                'age' => '25',
                'price' => '99.99',
            ]);

            expect($instance->email)->toBe('test@example.com')
                ->and($instance->age)->toBe(25)
                ->and($instance->price)->toBe('99.99');
        })->skip('Laravel Validator not available in unit tests');
    });

    describe('Casts + Mapping', function(): void {
        it('maps and casts together', function(): void {
            $dto = new class(
                email: '',
                createdAt: new DateTimeImmutable(),
                price: '0.00',
            ) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_email')]
                    public readonly string $email,
                    #[MapFrom('created_at')]
                    public readonly DateTimeImmutable $createdAt,
                    #[MapFrom('product_price')]
                    public readonly string $price,
                ) {}

                protected function casts(): array
                {
                    return [
                        'createdAt' => 'datetime',
                        'price' => 'decimal:2',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'user_email' => 'test@example.com',
                'created_at' => '2024-01-15 10:30:00',
                'product_price' => '149.99',
            ]);

            expect($instance->email)->toBe('test@example.com')
                ->and($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->price)->toBe('149.99');
        });

        it('maps, casts, and outputs together', function(): void {
            $dto = new class(
                email: '',
                createdAt: new DateTimeImmutable(),
            ) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_email')]
                    #[MapTo('email_address')]
                    public readonly string $email,
                    #[MapFrom('created_at')]
                    #[MapTo('timestamp')]
                    public readonly DateTimeImmutable $createdAt,
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'datetime'];
                }
            };

            $instance = $dto::fromArray([
                'user_email' => 'test@example.com',
                'created_at' => '2024-06-15 10:30:00',
            ]);

            $array = $instance->toArray();

            expect($array)->toHaveKey('email_address')
                ->and($array['email_address'])->toBe('test@example.com')
                ->and($array)->toHaveKey('timestamp')
                ->and($array['timestamp'])->toBe('2024-06-15 10:30:00');
        });
    });

    describe('Validation + Mapping', function(): void {
        it('validates and maps together', function(): void {
            $dto = new class(
                email: '',
                name: '',
            ) extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    #[MapFrom('user_email')]
                    public readonly string $email,
                    #[Required]
                    #[Min(3)]
                    #[MapFrom('user_name')]
                    public readonly string $name,
                ) {}
            };

            $instance = $dto::validateAndCreate([
                'user_email' => 'test@example.com',
                'user_name' => 'John Doe',
            ]);

            expect($instance->email)->toBe('test@example.com')
                ->and($instance->name)->toBe('John Doe');
        })->skip('Laravel Validator not available in unit tests');
    });

    describe('All Features Combined', function(): void {
        it('combines casts, validation, and mapping', function(): void {
            $dto = new class(
                email: '',
                age: 0,
                createdAt: new DateTimeImmutable(),
                price: '0.00',
                status: StatusEnum::PENDING,
            ) extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    #[MapFrom('user_email')]
                    #[MapTo('email_address')]
                    public readonly string $email,
                    #[Required]
                    #[Between(18, 120)]
                    #[MapFrom('user_age')]
                    public readonly int $age,
                    #[MapFrom('created_at')]
                    #[MapTo('timestamp')]
                    public readonly DateTimeImmutable $createdAt,
                    #[Required]
                    #[MapFrom('product_price')]
                    public readonly string $price,
                    #[Required]
                    #[MapFrom('order_status')]
                    public readonly StatusEnum $status,
                ) {}

                protected function casts(): array
                {
                    return [
                        'age' => 'integer',
                        'createdAt' => 'datetime',
                        'price' => 'decimal:2',
                        'status' => 'enum:Tests\Unit\SimpleDTO\Fixtures\StatusEnum',
                    ];
                }
            };

            $instance = $dto::validateAndCreate([
                'user_email' => 'test@example.com',
                'user_age' => '30',
                'created_at' => '2024-01-15 10:30:00',
                'product_price' => '99.99',
                'order_status' => 'active',
            ]);

            expect($instance->email)->toBe('test@example.com')
                ->and($instance->age)->toBe(30)
                ->and($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->price)->toBe('99.99')
                ->and($instance->status)->toBe(StatusEnum::ACTIVE);

            $array = $instance->toArray();

            expect($array)->toHaveKey('email_address')
                ->and($array['email_address'])->toBe('test@example.com')
                ->and($array)->toHaveKey('timestamp')
                ->and($array['timestamp'])->toBe('2024-01-15 10:30:00')
                ->and($array)->toHaveKey('price')
                ->and($array['price'])->toBe('99.99')
                ->and($array)->toHaveKey('status')
                ->and($array['status'])->toBe('active');
        })->skip('Laravel Validator not available in unit tests');

        it('combines all features with MapFrom', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    #[MapFrom('user_email')]
                    public readonly string $userEmail = '',
                    #[Required]
                    #[Between(18, 120)]
                    #[MapFrom('user_age')]
                    public readonly int $userAge = 0,
                    #[MapFrom('created_at')]
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return [
                        'userAge' => 'integer',
                        'createdAt' => 'datetime',
                    ];
                }
            };

            $instance = $dto::validateAndCreate([
                'user_email' => 'test@example.com',
                'user_age' => '25',
                'created_at' => '2024-06-15 10:30:00',
            ]);

            expect($instance->userEmail)->toBe('test@example.com')
                ->and($instance->userAge)->toBe(25)
                ->and($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
        })->skip('Laravel Validator not available in unit tests');
    });

    describe('Real-World Scenarios', function(): void {
        it('handles user registration DTO', function(): void {
            $dto = new class(
                email: '',
                password: '',
                age: 0,
                acceptedTerms: false,
                registeredAt: new DateTimeImmutable(),
            ) extends SimpleDTO {
                public function __construct(
                    #[Required]
                    #[Email]
                    public readonly string $email,
                    #[Required]
                    #[Min(8)]
                    public readonly string $password,
                    #[Required]
                    #[Between(18, 120)]
                    public readonly int $age,
                    #[Required]
                    public readonly bool $acceptedTerms,
                    public readonly DateTimeImmutable $registeredAt,
                ) {}

                protected function casts(): array
                {
                    return [
                        'age' => 'integer',
                        'acceptedTerms' => 'boolean',
                        'registeredAt' => 'datetime',
                    ];
                }
            };

            $instance = $dto::validateAndCreate([
                'email' => 'user@example.com',
                'password' => 'SecurePass123',
                'age' => '25',
                'acceptedTerms' => '1',
                'registeredAt' => '2024-01-15 10:30:00',
            ]);

            expect($instance->email)->toBe('user@example.com')
                ->and($instance->age)->toBe(25)
                ->and($instance->acceptedTerms)->toBeTrue();
        })->skip('Laravel Validator not available in unit tests');
    });
});

