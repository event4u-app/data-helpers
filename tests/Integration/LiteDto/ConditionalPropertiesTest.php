<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenCallback;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenContext;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenContextEquals;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenContextIn;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenContextNotNull;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenEquals;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenFalse;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenIn;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenInstanceOf;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenNotNull;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenNull;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenTrue;
use event4u\DataHelpers\LiteDto\Attributes\Conditional\WhenValue;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\SimpleDto\Enums\ComparisonOperator;
use stdClass;

// Test DTOs
class ConditionalPropsLiteDto1 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
        public readonly ?string $badge = null,
    ) {}
}

class ConditionalPropsLiteDto2 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenNull]
        public readonly ?string $deletedAt = null,
    ) {}
}

class ConditionalPropsLiteDto3 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenNotNull]
        public readonly ?string $phone = null,
    ) {}
}

class ConditionalPropsLiteDto4 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenTrue]
        public readonly bool $isPremium = false,
    ) {}
}

class ConditionalPropsLiteDto5 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenFalse]
        public readonly bool $isDeleted = false,
    ) {}
}

class ConditionalPropsLiteDto6 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenEquals('active')]
        public readonly ?string $status = null,
    ) {}
}

class ConditionalPropsLiteDto7 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenIn(['active', 'pending'])]
        public readonly ?string $status = null,
    ) {}
}

class ConditionalPropsLiteDto8 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenInstanceOf(stdClass::class)]
        public readonly mixed $data = null,
    ) {}
}

class ConditionalPropsLiteDto9 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        #[WhenCallback([self::class, 'checkAge'])]
        public readonly ?string $adultContent = null,
    ) {}

    public static function checkAge(mixed $value, object $dto, array $context): bool
    {
        return 18 <= $dto->age;
    }
}

class ConditionalPropsLiteDto10 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenContextEquals('role', 'admin')]
        public readonly ?string $adminPanel = null,
    ) {}
}

class ConditionalPropsLiteDto11 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenContextIn('role', ['admin', 'moderator'])]
        public readonly ?string $moderationPanel = null,
    ) {}
}

class ConditionalPropsLiteDto12 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenContextNotNull('user')]
        public readonly ?string $welcomeMessage = null,
    ) {}
}

class ConditionalPropsLiteDto13 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[WhenContext('level', ComparisonOperator::GreaterThanOrEqual, 5)]
        public readonly ?string $advancedFeature = null,
    ) {}
}

#[UltraFast]
class ConditionalPropsLiteDtoUltraFast extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
        public readonly ?string $badge = null,
        #[WhenNull]
        public readonly ?string $deletedAt = null,
    ) {}
}

class ConditionalPropsLiteDto14 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $premium,
        #[WhenValue('age', ComparisonOperator::GreaterThanOrEqual, 18)]
        #[WhenValue('premium', ComparisonOperator::StrictEqual, true)]
        public readonly ?string $specialFeature = null,
    ) {}
}

// Tests
test('WhenValue includes property when condition is met', function(): void {
    $dto = ConditionalPropsLiteDto1::from(['name' => 'Product', 'price' => 150.0, 'badge' => 'Premium']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('badge')
        ->and($array['badge'])->toBe('Premium');
});

test('WhenValue excludes property when condition is not met', function(): void {
    $dto = ConditionalPropsLiteDto1::from(['name' => 'Product', 'price' => 50.0, 'badge' => 'Premium']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('badge');
});

test('WhenNull includes property when value is null', function(): void {
    $dto = ConditionalPropsLiteDto2::from(['name' => 'User', 'deletedAt' => null]);
    $array = $dto->toArray();

    expect($array)->toHaveKey('deletedAt')
        ->and($array['deletedAt'])->toBeNull();
});

test('WhenNull excludes property when value is not null', function(): void {
    $dto = ConditionalPropsLiteDto2::from(['name' => 'User', 'deletedAt' => '2024-01-01']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('deletedAt');
});

test('WhenNotNull includes property when value is not null', function(): void {
    $dto = ConditionalPropsLiteDto3::from(['name' => 'User', 'phone' => '123-456-7890']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('phone')
        ->and($array['phone'])->toBe('123-456-7890');
});

test('WhenNotNull excludes property when value is null', function(): void {
    $dto = ConditionalPropsLiteDto3::from(['name' => 'User', 'phone' => null]);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('phone');
});

test('WhenTrue includes property when value is true', function(): void {
    $dto = ConditionalPropsLiteDto4::from(['name' => 'User', 'isPremium' => true]);
    $array = $dto->toArray();

    expect($array)->toHaveKey('isPremium')
        ->and($array['isPremium'])->toBeTrue();
});

test('WhenTrue excludes property when value is false', function(): void {
    $dto = ConditionalPropsLiteDto4::from(['name' => 'User', 'isPremium' => false]);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('isPremium');
});

test('WhenFalse includes property when value is false', function(): void {
    $dto = ConditionalPropsLiteDto5::from(['name' => 'User', 'isDeleted' => false]);
    $array = $dto->toArray();

    expect($array)->toHaveKey('isDeleted')
        ->and($array['isDeleted'])->toBeFalse();
});

test('WhenFalse excludes property when value is true', function(): void {
    $dto = ConditionalPropsLiteDto5::from(['name' => 'User', 'isDeleted' => true]);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('isDeleted');
});

test('WhenEquals includes property when value equals target', function(): void {
    $dto = ConditionalPropsLiteDto6::from(['name' => 'User', 'status' => 'active']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('status')
        ->and($array['status'])->toBe('active');
});

test('WhenEquals excludes property when value does not equal target', function(): void {
    $dto = ConditionalPropsLiteDto6::from(['name' => 'User', 'status' => 'inactive']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('status');
});

test('WhenIn includes property when value is in list', function(): void {
    $dto = ConditionalPropsLiteDto7::from(['name' => 'User', 'status' => 'active']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('status')
        ->and($array['status'])->toBe('active');
});

test('WhenIn excludes property when value is not in list', function(): void {
    $dto = ConditionalPropsLiteDto7::from(['name' => 'User', 'status' => 'inactive']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('status');
});

test('WhenInstanceOf includes property when value is instance of class', function(): void {
    $dto = ConditionalPropsLiteDto8::from(['name' => 'User', 'data' => new stdClass()]);
    $array = $dto->toArray();

    expect($array)->toHaveKey('data')
        ->and($array['data'])->toBeInstanceOf(stdClass::class);
});

test('WhenInstanceOf excludes property when value is not instance of class', function(): void {
    $dto = ConditionalPropsLiteDto8::from(['name' => 'User', 'data' => 'string']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('data');
});

test('WhenCallback includes property when callback returns true', function(): void {
    $dto = ConditionalPropsLiteDto9::from(['name' => 'User', 'age' => 25, 'adultContent' => 'Adult content']);
    $array = $dto->toArray();

    expect($array)->toHaveKey('adultContent')
        ->and($array['adultContent'])->toBe('Adult content');
});

test('WhenCallback excludes property when callback returns false', function(): void {
    $dto = ConditionalPropsLiteDto9::from(['name' => 'User', 'age' => 15, 'adultContent' => 'Adult content']);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('adultContent');
});

test('WhenContextEquals includes property when context matches', function(): void {
    $dto = ConditionalPropsLiteDto10::from(['name' => 'User', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['role' => 'admin']);

    expect($array)->toHaveKey('adminPanel')
        ->and($array['adminPanel'])->toBe('/admin');
});

test('WhenContextEquals excludes property when context does not match', function(): void {
    $dto = ConditionalPropsLiteDto10::from(['name' => 'User', 'adminPanel' => '/admin']);
    $array = $dto->toArray(['role' => 'user']);

    expect($array)->not->toHaveKey('adminPanel');
});

test('WhenContextIn includes property when context is in list', function(): void {
    $dto = ConditionalPropsLiteDto11::from(['name' => 'User', 'moderationPanel' => '/moderation']);
    $array = $dto->toArray(['role' => 'admin']);

    expect($array)->toHaveKey('moderationPanel')
        ->and($array['moderationPanel'])->toBe('/moderation');
});

test('WhenContextIn excludes property when context is not in list', function(): void {
    $dto = ConditionalPropsLiteDto11::from(['name' => 'User', 'moderationPanel' => '/moderation']);
    $array = $dto->toArray(['role' => 'user']);

    expect($array)->not->toHaveKey('moderationPanel');
});

test('WhenContextNotNull includes property when context key exists and is not null', function(): void {
    $dto = ConditionalPropsLiteDto12::from(['name' => 'User', 'welcomeMessage' => 'Welcome back!']);
    $array = $dto->toArray(['user' => new stdClass()]);

    expect($array)->toHaveKey('welcomeMessage')
        ->and($array['welcomeMessage'])->toBe('Welcome back!');
});

test('WhenContextNotNull excludes property when context key is null', function(): void {
    $dto = ConditionalPropsLiteDto12::from(['name' => 'User', 'welcomeMessage' => 'Welcome back!']);
    $array = $dto->toArray(['user' => null]);

    expect($array)->not->toHaveKey('welcomeMessage');
});

test('WhenContext includes property when context comparison is true', function(): void {
    $dto = ConditionalPropsLiteDto13::from(['name' => 'User', 'advancedFeature' => 'Advanced']);
    $array = $dto->toArray(['level' => 10]);

    expect($array)->toHaveKey('advancedFeature')
        ->and($array['advancedFeature'])->toBe('Advanced');
});

test('WhenContext excludes property when context comparison is false', function(): void {
    $dto = ConditionalPropsLiteDto13::from(['name' => 'User', 'advancedFeature' => 'Advanced']);
    $array = $dto->toArray(['level' => 3]);

    expect($array)->not->toHaveKey('advancedFeature');
});

test('UltraFast mode works with conditional properties', function(): void {
    $dto = ConditionalPropsLiteDtoUltraFast::from([
        'name' => 'Product',
        'price' => 150.0,
        'badge' => 'Premium',
        'deletedAt' => null,
    ]);
    $array = $dto->toArray();

    expect($array)->toHaveKey('badge')
        ->and($array['badge'])->toBe('Premium')
        ->and($array)->toHaveKey('deletedAt')
        ->and($array['deletedAt'])->toBeNull();
});

test('UltraFast mode excludes properties when conditions are not met', function(): void {
    $dto = ConditionalPropsLiteDtoUltraFast::from([
        'name' => 'Product',
        'price' => 50.0,
        'badge' => 'Premium',
        'deletedAt' => '2024-01-01',
    ]);
    $array = $dto->toArray();

    expect($array)->not->toHaveKey('badge')
        ->and($array)->not->toHaveKey('deletedAt');
});

test('toJson respects conditional properties', function(): void {
    $dto = ConditionalPropsLiteDto1::from(['name' => 'Product', 'price' => 150.0, 'badge' => 'Premium']);
    $json = $dto->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('badge')
        ->and($decoded['badge'])->toBe('Premium');
});

test('toJson with context respects conditional properties', function(): void {
    $dto = ConditionalPropsLiteDto10::from(['name' => 'User', 'adminPanel' => '/admin']);
    $json = $dto->toJson(['role' => 'admin']);
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('adminPanel')
        ->and($decoded['adminPanel'])->toBe('/admin');
});

test('multiple conditional attributes use AND logic', function(): void {
    $dto = ConditionalPropsLiteDto14::from([
        'name' => 'User', 'age' => 25, 'premium' => true, 'specialFeature' => 'Special']
    );
    $array = $dto->toArray();
    expect($array)->toHaveKey('specialFeature');
});

test('multiple conditional attributes exclude when one fails', function(): void {
    $dto = ConditionalPropsLiteDto14::from([
        'name' => 'User', 'age' => 15, 'premium' => true, 'specialFeature' => 'Special']
    );
    $array = $dto->toArray();
    expect($array)->not->toHaveKey('specialFeature');
});
