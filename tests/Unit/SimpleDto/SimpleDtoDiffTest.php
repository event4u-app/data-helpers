<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;

class TestDiffUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {
    }
}

class TestDiffAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zip,
    ) {
    }
}

class TestDiffUserWithAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly TestDiffAddressDto $address,
    ) {
    }
}

describe('Basic Comparison', function(): void {
    it('returns empty array when comparing identical data', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        expect($diff)->toBe([]);
    });

    it('detects single property difference', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });

    it('detects multiple property differences', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'age' => 25,
        ]);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
            'email' => [
                'dto' => 'john@example.com',
                'data' => 'jane@example.com',
            ],
            'age' => [
                'dto' => 30,
                'data' => 25,
            ],
        ]);
    });

    it('detects missing keys in compare data', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($diff)->toBe([
            'age' => [
                'dto' => 30,
                'data' => null,
            ],
        ]);
    });

    it('detects new keys in compare data', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'phone' => '555-1234',
        ]);

        expect($diff)->toBe([
            'phone' => [
                'dto' => null,
                'data' => '555-1234',
            ],
        ]);
    });
});

describe('ignoreNonExistingKeys Option', function(): void {
    it('ignores missing keys when option is true', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], ignoreNonExistingKeys: true);

        expect($diff)->toBe([]);
    });

    it('ignores new keys when option is true', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'phone' => '555-1234',
        ], ignoreNonExistingKeys: true);

        expect($diff)->toBe([]);
    });

    it('still detects value changes when ignoring non-existing keys', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff([
            'name' => 'Jane Doe',
        ], ignoreNonExistingKeys: true);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });
});

describe('Different Input Formats', function(): void {
    it('compares with JSON string', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $json = json_encode([
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user->diff($json);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });

    it('compares with another Dto', function(): void {
        $user1 = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $user2 = TestDiffUserDto::fromArray([
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $diff = $user1->diff($user2);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });

    it('compares with XML string', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $xml = '<user><name>Jane Doe</name><email>john@example.com</email><age>30</age></user>';

        $diff = $user->diff($xml);

        // XML values are always strings, so age will be '30' instead of 30
        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
            'age' => [
                'dto' => 30,
                'data' => '30',
            ],
        ]);
    });

    it('compares with object having toArray method', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $object = new class {
            /** @return array<string, mixed> */
            public function toArray(): array
            {
                return [
                    'name' => 'Jane Doe',
                    'email' => 'john@example.com',
                    'age' => 30,
                ];
            }
        };

        $diff = $user->diff($object);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });

    it('compares with object using getters', function(): void {
        $user = TestDiffUserDto::fromArray([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $object = new class {
            public function getName(): string
            {
                return 'Jane Doe';
            }

            public function getEmail(): string
            {
                return 'john@example.com';
            }

            public function getAge(): int
            {
                return 30;
            }
        };

        $diff = $user->diff($object);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John Doe',
                'data' => 'Jane Doe',
            ],
        ]);
    });
});
