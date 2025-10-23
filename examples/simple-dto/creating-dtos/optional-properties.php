<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\Support\Optional;

echo "=== Optional Properties Example ===\n\n";

// Example 1: Basic Optional Properties (Attribute Syntax)
echo "1. Basic Optional Properties (Attribute Syntax)\n";
echo str_repeat('-', 50) . "\n";

class UserDTO1 extends SimpleDTO
{
    /**
     * @param Optional<string>|string $email
     * @param Optional<int>|int $age
     */
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
        #[OptionalAttribute]
        public readonly Optional|int $age,
    ) {
        // Optional properties will be wrapped automatically
    }
}

$user1 = UserDTO1::fromArray(['name' => 'John Doe']);
echo "Missing email and age:\n";
echo sprintf('  name: %s%s', $user1->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  email present: " . ($user1->email->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  age present: " . ($user1->age->isPresent() ? 'yes' : 'no') . "\n";
echo "\n";

$user2 = UserDTO1::fromArray(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'age' => 30]);
echo "All fields present:\n";
echo sprintf('  name: %s%s', $user2->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  email: " . $user2->email->get() . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  age: " . $user2->age->get() . "\n";
echo "\n";

// Example 2: Optional vs Nullable
echo "2. Optional vs Nullable\n";
echo str_repeat('-', 50) . "\n";

class UserDTO2 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,      // Can be missing
        #[OptionalAttribute]
        public readonly Optional|string|null $bio,   // Can be missing OR null
        public readonly ?string $phone = null,       // Can be null
    ) {}
}

echo "Missing email, explicit null phone:\n";
$user3 = UserDTO2::fromArray(['name' => 'John', 'phone' => null]);
/** @phpstan-ignore-next-line unknown */
echo "  email present: " . ($user3->email->isPresent() ? 'yes' : 'no') . "\n";
echo "  phone: " . ($user3->phone ?? 'null') . "\n";
echo "\n";

echo "Explicit null bio:\n";
$user4 = UserDTO2::fromArray(['name' => 'Jane', 'phone' => '123-456', 'bio' => null]);
/** @phpstan-ignore-next-line unknown */
echo "  bio present: " . ($user4->bio->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "  bio value: " . ($user4->bio->get() ?? 'null') . "\n";
echo "\n";

// Example 3: Partial Updates
echo "3. Partial Updates (PATCH requests)\n";
echo str_repeat('-', 50) . "\n";

class UserDTO3 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        #[OptionalAttribute]
        public readonly Optional|string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
        #[OptionalAttribute]
        public readonly Optional|int $age,
    ) {}
}

echo "PATCH /users/1 with { \"email\": \"new@example.com\" }\n";
$updates = UserDTO3::fromArray(['email' => 'new@example.com']);
$partial = $updates->partial();
echo "Partial data: " . json_encode($partial) . "\n";
echo "Only email will be updated!\n";
echo "\n";

echo "PATCH /users/1 with { \"name\": \"John\", \"age\": 30 }\n";
$updates2 = UserDTO3::fromArray(['name' => 'John', 'age' => 30]);
$partial2 = $updates2->partial();
echo "Partial data: " . json_encode($partial2) . "\n";
echo "Only name and age will be updated!\n";
echo "\n";

// Example 4: Union Type Syntax
echo "4. Union Type Syntax (Modern)\n";
echo str_repeat('-', 50) . "\n";

class UserDTO4 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        public readonly Optional|string $email,  // Union type!
        public readonly Optional|int $age,       // Union type!
    ) {}
}

$user5 = UserDTO4::fromArray(['name' => 'Alice']);
echo "Missing email and age:\n";
echo sprintf('  name: %s%s', $user5->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  email present: " . ($user5->email->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  age present: " . ($user5->age->isPresent() ? 'yes' : 'no') . "\n";
echo "\n";

// Example 5: Optional Wrapper Methods
echo "5. Optional Wrapper Methods\n";
echo str_repeat('-', 50) . "\n";

$present = Optional::of('value');
$empty = Optional::empty();
$nullValue = Optional::of(null);

echo "Present value:\n";
echo "  isPresent: " . ($present->isPresent() ? 'yes' : 'no') . "\n";
echo "  get: " . $present->get() . "\n";
echo "\n";

echo "Empty value:\n";
echo "  isEmpty: " . ($empty->isEmpty() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  get with default: " . $empty->get('default') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  orElse: " . $empty->orElse('fallback') . "\n";
echo "\n";

echo "Null value (explicitly set):\n";
echo "  isPresent: " . ($nullValue->isPresent() ? 'yes' : 'no') . "\n";
echo "  get: " . ($nullValue->get() ?? 'null') . "\n";
echo "\n";

// Example 6: Map and Filter
echo "6. Map and Filter\n";
echo str_repeat('-', 50) . "\n";

$number = Optional::of(5);
/** @var DataCollection<SimpleDTO> $doubled */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$doubled = $number->map(fn($x): int => $x * 2);
/** @phpstan-ignore-next-line unknown */
echo "Map 5 * 2: " . $doubled->get() . "\n";

/** @var DataCollection<SimpleDTO> $filtered1 */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$filtered1 = $number->filter(fn($x): bool => 3 < $x);
/** @phpstan-ignore-next-line unknown */
echo "Filter 5 > 3: " . ($filtered1->isPresent() ? 'present' : 'empty') . "\n";

/** @var DataCollection<SimpleDTO> $filtered2 */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$filtered2 = $number->filter(fn($x): bool => 10 < $x);
/** @phpstan-ignore-next-line unknown */
echo "Filter 5 > 10: " . ($filtered2->isPresent() ? 'present' : 'empty') . "\n";
echo "\n";

// Example 7: toArray and JSON
echo "7. toArray and JSON Serialization\n";
echo str_repeat('-', 50) . "\n";

$user6 = UserDTO4::fromArray(['name' => 'Bob', 'email' => 'bob@example.com']);
echo "toArray: " . json_encode($user6->toArray()) . "\n";
echo "JSON: " . json_encode($user6) . "\n";
echo "\n";

$user7 = UserDTO4::fromArray(['name' => 'Charlie']);
echo "toArray (missing email): " . json_encode($user7->toArray()) . "\n";
echo "JSON (missing email): " . json_encode($user7) . "\n";
echo "\n";

// Example 8: Default Values
echo "8. Default Values\n";
echo str_repeat('-', 50) . "\n";

class UserDTO5 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute(default: 'default@example.com')]
        public readonly Optional|string $email,
    ) {}
}

$user8 = UserDTO5::fromArray(['name' => 'David']);
echo "Missing email with default:\n";
/** @phpstan-ignore-next-line unknown */
echo "  email present: " . ($user8->email->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "  email value: " . $user8->email->get() . "\n";
echo "\n";

echo "✅  All examples completed successfully!\n";
