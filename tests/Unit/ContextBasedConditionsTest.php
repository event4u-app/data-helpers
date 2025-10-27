<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContext;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContextEquals;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContextIn;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContextNotNull;

// Test Dtos
class ContextDto1 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'includeEmail'
)] public readonly string $email) {} }
class ContextDto2 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'role',
    'admin'
)] public readonly string $secretKey) {} }
class ContextDto3 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextEquals(
    'role',
    'admin'
)] public readonly string $adminPanel) {} }
class ContextDto4 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextIn(
    'role',
    [
    'admin',
    'moderator',
])] public readonly string $modPanel) {} }
class ContextDto5 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextNotNull(
    'userId'
)] public readonly string $privateData) {} }
class ContextDto6 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'includeEmail'
)] #[WhenContext(
    'role',
    'admin'
)] public readonly string $email) {} }
class ContextDto7 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'userLevel',
    '>=',
    5
)] public readonly float $wholesalePrice) {} }
class ContextDto8 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'stock',
    '<',
    10
)] public readonly string $lowStockWarning) {} }
class ContextDto9 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'environment',
    '!=',
    'production'
)] public readonly string $debugInfo) {} }
class ContextDto10 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'isOnSale'
)] public readonly string $saleLabel) {} }
class ContextDto11 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextIn(
    'role',
    [
    'admin',
    'moderator',
])] public readonly string $modPanel) {} }
class ContextDto12 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'hasSpecialBadge'
)] public readonly string $badge) {} }
class ContextDto13 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextNotNull(
    'userId'
)] public readonly string $greeting) {} }
class ContextDto14 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'isPremium'
)] public readonly string $premiumContent) {} }
class ContextDto15 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'includeEmail'
)] public readonly string $email, #[WhenContext(
    'role',
    'admin'
)] public readonly string $adminPanel) {} }
class ContextDto16 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextEquals(
    'onSale',
    1,
    false
)] public readonly string $saleLabel) {} }
class ContextDto17 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextIn(
    'role',
    [
    'admin',
    'moderator',
])] public readonly string $modPanel) {} }
class ContextDto18 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContext(
    'hasSpecialBadge'
)] public readonly string $badge) {} }

describe('Context-Based Conditions', function(): void {
class ContextDto19 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextIn(
    'role',
    [
    'admin',
    'moderator',
])] public readonly string $moderationPanel) {} }
class ContextDto20 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextIn(
    'status',
    [
    'featured',
    'promoted',
    'bestseller',
])] public readonly string $badge) {} }
class ContextDto21 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextNotNull(
    'user'
)] public readonly string $welcomeMessage) {} }
class ContextDto22 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextEquals(
    'role',
    'admin'
)] #[WhenContextEquals(
    'subscription',
    'premium'
)] public readonly string $premiumContent) {} }
class ContextDto23 extends SimpleDto { public function __construct(public readonly string $name, #[WhenContextEquals(
    'includeEmail',
    true
)] public readonly string $email, #[WhenContextEquals(
    'role',
    'admin'
)] public readonly string $adminPanel) {} }

    describe('WhenContext Attribute', function(): void {
        it('includes property when context key exists', function(): void {
            $dto = new ContextDto1('John', 'john@example.com');

            $array = $dto->withContext(['includeEmail' => true])->toArray();

            expect($array)->toHaveKey('email')
                ->and($array['email'])->toBe('john@example.com');
        });

        it('excludes property when context key does not exist', function(): void {
            $dto = new ContextDto1('John', 'john@example.com');

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('email');
        });

        it('includes property when context value equals specified value', function(): void {
            $dto = new ContextDto2('John', 'secret123');

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('secretKey')
                ->and($array['secretKey'])->toBe('secret123');
        });

        it('excludes property when context value does not equal specified value', function(): void {
            $dto = new ContextDto2('John', 'secret123');

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('secretKey');
        });

        it('supports greater than operator', function(): void {
            $dto = new ContextDto7('Product', 99.99);

            $array = $dto->withContext(['userLevel' => 5])->toArray();

            expect($array)->toHaveKey('wholesalePrice');
        });

        it('supports less than operator', function(): void {
            $dto = new ContextDto8('Product', 'Limited offer');

            $array = $dto->withContext(['stock' => 5])->toArray();

            expect($array)->toHaveKey('lowStockWarning');
        });

        it('supports not equal operator', function(): void {
            $dto = new ContextDto9('User', 'Debug info');

            $array = $dto->withContext(['environment' => 'development'])->toArray();

            expect($array)->toHaveKey('debugInfo');
        });
    });

    describe('WhenContextEquals Attribute', function(): void {
        it('includes property when context value equals specified value (strict)', function(): void {
            $dto = new ContextDto3('John', '/admin');

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('adminPanel')
                ->and($array['adminPanel'])->toBe('/admin');
        });

        it('excludes property when context value does not equal specified value', function(): void {
            $dto = new ContextDto3('John', '/admin');

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('adminPanel');
        });

        it('supports non-strict comparison', function(): void {
            $dto = new ContextDto16('Product', 'On sale');

            $array = $dto->withContext(['onSale' => true])->toArray();

            expect($array)->toHaveKey('saleLabel');
        });
    });

    describe('WhenContextIn Attribute', function(): void {
        it('includes property when context value is in list', function(): void {
            $dto = new ContextDto19('John', '/moderation');

            $array = $dto->withContext(['role' => 'admin'])->toArray();

            expect($array)->toHaveKey('moderationPanel')
                ->and($array['moderationPanel'])->toBe('/moderation');
        });

        it('excludes property when context value is not in list', function(): void {
            $dto = new ContextDto19('John', '/moderation');

            $array = $dto->withContext(['role' => 'user'])->toArray();

            expect($array)->not->toHaveKey('moderationPanel');
        });

        it('supports multiple values in list', function(): void {
            $dto = new ContextDto20('Product', 'Special badge');

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

    describe('WhenContextNotNull Attribute', function(): void {
        it('includes property when context key exists and is not null', function(): void {
            $dto = new ContextDto21('John', 'Welcome back!');

            $array = $dto->withContext(['user' => (object)['id' => 1]])->toArray();

            expect($array)->toHaveKey('welcomeMessage')
                ->and($array['welcomeMessage'])->toBe('Welcome back!');
        });

        it('excludes property when context key does not exist', function(): void {
            $dto = new ContextDto21('John', 'Welcome back!');

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('welcomeMessage');
        });

        it('excludes property when context value is null', function(): void {
            $dto = new ContextDto21('John', 'Welcome back!');

            $array = $dto->withContext(['user' => null])->toArray();

            expect($array)->not->toHaveKey('welcomeMessage');
        });
    });

    describe('Multiple Context Conditions', function(): void {
        it('supports multiple context conditions (AND logic)', function(): void {
            $dto = new ContextDto22('John', 'Premium content');

            $arrayBoth = $dto->withContext(['role' => 'admin', 'subscription' => 'premium'])->toArray();
            $arrayOnlyRole = $dto->withContext(['role' => 'admin', 'subscription' => 'basic'])->toArray();
            $arrayOnlySub = $dto->withContext(['role' => 'user', 'subscription' => 'premium'])->toArray();

            expect($arrayBoth)->toHaveKey('premiumContent')
                ->and($arrayOnlyRole)->not->toHaveKey('premiumContent')
                ->and($arrayOnlySub)->not->toHaveKey('premiumContent');
        });
    });

    describe('Context Chaining', function(): void {
        it('merges context from multiple withContext calls', function(): void {
            $dto = new ContextDto23('John', 'admin@example.com', '/admin');

            $array = $dto
                ->withContext(['includeEmail' => true])
                ->withContext(['role' => 'admin'])
                ->toArray();

            expect($array)->toHaveKey('email')
                ->and($array)->toHaveKey('adminPanel');
        });
    });
});
