<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextNotNull;

describe('Context-Based Conditions', function () {
    describe('WhenContext Attribute', function () {
        it('includes property when context key exists', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('includeEmail')]
                    public readonly string $email,
                ) {}
            };

            $array = $dto->withContext(['includeEmail' => true])->toArray();

            expect($array)->toHaveKey('email')
                ->and($array['email'])->toBe('john@example.com');
        });

        it('excludes property when context key does not exist', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('includeEmail')]
                    public readonly string $email,
                ) {}
            };

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('email');
        });

        it('includes property when context value equals specified value', function () {
            $dto = new class('John', 'secret123') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('role', 'admin')]
                    public readonly string $secretKey,
                ) {}
            };

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('secretKey')
                ->and($array['secretKey'])->toBe('secret123');
        });

        it('excludes property when context value does not equal specified value', function () {
            $dto = new class('John', 'secret123') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('role', 'admin')]
                    public readonly string $secretKey,
                ) {}
            };

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('secretKey');
        });

        it('supports greater than operator', function () {
            $dto = new class('Product', 99.99) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('userLevel', '>=', 5)]
                    public readonly float $wholesalePrice,
                ) {}
            };

            $array = $dto->withContext(['userLevel' => 5])->toArray();

            expect($array)->toHaveKey('wholesalePrice');
        });

        it('supports less than operator', function () {
            $dto = new class('Product', 'Limited offer') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('stock', '<', 10)]
                    public readonly string $lowStockWarning,
                ) {}
            };

            $array = $dto->withContext(['stock' => 5])->toArray();

            expect($array)->toHaveKey('lowStockWarning');
        });

        it('supports not equal operator', function () {
            $dto = new class('User', 'Debug info') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContext('environment', '!=', 'production')]
                    public readonly string $debugInfo,
                ) {}
            };

            $array = $dto->withContext(['environment' => 'development'])->toArray();

            expect($array)->toHaveKey('debugInfo');
        });
    });

    describe('WhenContextEquals Attribute', function () {
        it('includes property when context value equals specified value (strict)', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextEquals('role', 'admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when context value does not equal specified value', function () {
            $dto = new class('John', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextEquals('role', 'admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('supports non-strict comparison', function () {
            $dto = new class('Product', 'On sale') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextEquals('onSale', 1, false)]
                    public readonly string $saleLabel,
                ) {}
            };

            $array = $dto->withContext(['onSale' => true])->toArray();

            expect($array)->toHaveKey('saleLabel');
        });
    });

    describe('WhenContextIn Attribute', function () {
        it('includes property when context value is in list', function () {
            $dto = new class('John', '/moderation') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextIn('role', ['admin', 'moderator'])]
                    public readonly string $moderationPanel,
                ) {}
            };

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('moderationPanel')
                ->and($array['moderationPanel'])->toBe('/moderation');
        });

        it('excludes property when context value is not in list', function () {
            $dto = new class('John', '/moderation') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextIn('role', ['admin', 'moderator'])]
                    public readonly string $moderationPanel,
                ) {}
            };

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('moderationPanel');
        });

        it('supports multiple values in list', function () {
            $dto = new class('Product', 'Special badge') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextIn('status', ['featured', 'promoted', 'bestseller'])]
                    public readonly string $badge,
                ) {}
            };

            $arrayFeatured = $dto->withContext(['status' => 'featured'])->toArray();
            $arrayPromoted = $dto->withContext(['status' => 'promoted'])->toArray();
            $arrayBestseller = $dto->withContext(['status' => 'bestseller'])->toArray();
            $arrayNormal = $dto->withContext(['status' => 'normal'])->toArray();

            expect($arrayFeatured)->toHaveKey('badge')
                ->and($arrayPromoted)->toHaveKey('badge')
                ->and($arrayBestseller)->toHaveKey('badge')
                ->and($arrayNormal)->not->toHaveKey('badge');
        });
    });

    describe('WhenContextNotNull Attribute', function () {
        it('includes property when context key exists and is not null', function () {
            $dto = new class('John', 'Welcome back!') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextNotNull('user')]
                    public readonly string $welcomeMessage,
                ) {}
            };

            $array = $dto->withContext(['user' => (object)['id' => 1]])->toArray();

            expect($array)->toHaveKey('welcomeMessage')
                ->and($array['welcomeMessage'])->toBe('Welcome back!');
        });

        it('excludes property when context key does not exist', function () {
            $dto = new class('John', 'Welcome back!') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextNotNull('user')]
                    public readonly string $welcomeMessage,
                ) {}
            };

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('welcomeMessage');
        });

        it('excludes property when context value is null', function () {
            $dto = new class('John', 'Welcome back!') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextNotNull('user')]
                    public readonly string $welcomeMessage,
                ) {}
            };

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('welcomeMessage');
        });
    });

    describe('Multiple Context Conditions', function () {
        it('supports multiple context conditions (AND logic)', function () {
            $dto = new class('John', 'Premium content') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextEquals('role', 'admin')]
                    #[WhenContextEquals('subscription', 'premium')]
                    public readonly string $premiumContent,
                ) {}
            };

            $arrayBoth = $dto->withContext(['role' => 'admin', 'subscription' => 'premium'])->toArray();
            $arrayOnlyRole = $dto->withContext(['role' => 'admin', 'subscription' => 'basic'])->toArray();
            $arrayOnlySub = $dto->withContext(['role' => 'user', 'subscription' => 'premium'])->toArray();

            expect($arrayBoth)->toHaveKey('premiumContent')
                ->and($arrayOnlyRole)->not->toHaveKey('premiumContent')
                ->and($arrayOnlySub)->not->toHaveKey('premiumContent');
        });
    });

    describe('Context Chaining', function () {
        it('merges context from multiple withContext calls', function () {
            $dto = new class('John', 'admin@example.com', '/admin') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,

                    #[WhenContextEquals('includeEmail', true)]
                    public readonly string $email,

                    #[WhenContextEquals('role', 'admin')]
                    public readonly string $adminPanel,
                ) {}
            };

            $array = $dto
                ->withContext(['includeEmail' => true])
                ->withContext(['role' => 'admin'])
                ->toArray();

            expect($array)->toHaveKey('email')
                ->and($array)->toHaveKey('adminPanel');
        });
    });
});

