<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\SimpleDto;

// Test Dtos
class SimpleUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

class SimpleAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly int $zip,
    ) {}
}

class SimpleUserWithAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly SimpleAddressDto $address,
    ) {}
}

class SimpleEmailDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class SimpleUserWithEmailsDto extends SimpleDto
{
    /** @param array<int, SimpleEmailDto> $emails */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
    ) {}
}

class SimpleDepartmentDto extends SimpleDto
{
    /** @param array<int, SimpleUserDto> $employees */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

class SimpleCompanyDto extends SimpleDto
{
    /** @param array<int, SimpleDepartmentDto> $departments */
    public function __construct(
        public readonly string $name,
        public readonly array $departments,
    ) {}
}

test('getStructure() returns flat array with dot-notation for simple data', function(): void {
    $data = [
        'name' => 'John Doe',
        'age' => 30,
        'active' => true,
        'balance' => 99.99,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'active' => 'bool',
        'balance' => 'float',
    ]);
});

test('getStructure() returns flat array with dot-notation for nested data', function(): void {
    $data = [
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'city' => 'New York',
            'zip' => 10001,
            'country' => 'USA',
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'address' => 'array',
        'address.city' => 'string',
        'address.zip' => 'int',
        'address.country' => 'string',
    ]);
});

test('getStructure() handles arrays with numeric indices', function(): void {
    $data = [
        'name' => 'John Doe',
        'emails' => [
            [
                'email' => 'john@work.com',
                'type' => 'work',
                'verified' => true,
            ],
            [
                'email' => 'john@home.com',
                'type' => 'home',
                'verified' => false,
            ],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'emails' => 'array',
        'emails.*' => 'array',
        'emails.*.email' => 'string',
        'emails.*.type' => 'string',
        'emails.*.verified' => 'bool',
    ]);
});

test('getStructure() handles deeply nested structures', function(): void {
    $data = [
        'user' => [
            'profile' => [
                'name' => 'John',
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'user' => 'array',
        'user.profile' => 'array',
        'user.profile.name' => 'string',
        'user.profile.settings' => 'array',
        'user.profile.settings.theme' => 'string',
        'user.profile.settings.notifications' => 'bool',
    ]);
});

test('getStructure() handles null values', function(): void {
    $data = [
        'name' => 'John',
        'middleName' => null,
        'age' => 30,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'middleName' => 'null',
        'age' => 'int',
    ]);
});

test('getStructure() handles empty arrays', function(): void {
    $data = [
        'name' => 'John',
        'tags' => [],
        'age' => 30,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'tags' => 'array',
        'age' => 'int',
    ]);
});

test('getStructureMultidimensional() returns nested array for simple data', function(): void {
    $data = [
        'name' => 'John Doe',
        'age' => 30,
        'active' => true,
        'balance' => 99.99,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'active' => 'bool',
        'balance' => 'float',
    ]);
});

test('getStructureMultidimensional() returns nested array for nested data', function(): void {
    $data = [
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'city' => 'New York',
            'zip' => 10001,
            'country' => 'USA',
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'address' => [
            'city' => 'string',
            'zip' => 'int',
            'country' => 'string',
        ],
    ]);
});

test('getStructureMultidimensional() handles arrays with numeric indices', function(): void {
    $data = [
        'name' => 'John Doe',
        'emails' => [
            [
                'email' => 'john@work.com',
                'type' => 'work',
                'verified' => true,
            ],
            [
                'email' => 'john@home.com',
                'type' => 'home',
                'verified' => false,
            ],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'emails' => [
            '*' => [
                'email' => 'string',
                'type' => 'string',
                'verified' => 'bool',
            ],
        ],
    ]);
});

test('getStructureMultidimensional() handles deeply nested structures', function(): void {
    $data = [
        'user' => [
            'profile' => [
                'name' => 'John',
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'user' => [
            'profile' => [
                'name' => 'string',
                'settings' => [
                    'theme' => 'string',
                    'notifications' => 'bool',
                ],
            ],
        ],
    ]);
});

test('getStructureMultidimensional() handles deeply nested structures with arrays', function(): void {
    $data = [
        'user' => [
            'profile' => [
                'name' => 'John',
                'settings' => [
                    [
                        'theme' => 'dark',
                        'notifications' => true,
                    ],
                    [
                        'theme' => 'light',
                        'notifications' => false,
                    ],
                ],
            ],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'user' => [
            'profile' => [
                'name' => 'string',
                'settings' => [
                    '*' => [
                        'theme' => 'string',
                        'notifications' => 'bool',
                    ],
                ],
            ],
        ],
    ]);
});

test('getStructureMultidimensional() handles null values', function(): void {
    $data = [
        'name' => 'John',
        'middleName' => null,
        'age' => 30,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'middleName' => 'null',
        'age' => 'int',
    ]);
});

test('getStructureMultidimensional() handles empty arrays', function(): void {
    $data = [
        'name' => 'John',
        'tags' => [],
        'age' => 30,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'tags' => 'array',
        'age' => 'int',
    ]);
});

test('getStructure() handles mixed types in arrays', function(): void {
    $data = [
        'mixed' => [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => ['nested' => 'value'],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'mixed' => 'array',
        'mixed.string' => 'string',
        'mixed.int' => 'int',
        'mixed.float' => 'float',
        'mixed.bool' => 'bool',
        'mixed.null' => 'null',
        'mixed.array' => 'array',
        'mixed.array.nested' => 'string',
    ]);
});

test('getStructure() handles JSON string input', function(): void {
    $json = json_encode([
        'name' => 'John Doe',
        'age' => 30,
        'active' => true,
        'address' => [
            'city' => 'New York',
            'zip' => 10001,
        ],
    ]);

    $accessor = new DataAccessor($json);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'active' => 'bool',
        'address' => 'array',
        'address.city' => 'string',
        'address.zip' => 'int',
    ]);
});

test('getStructure() handles XML string input', function(): void {
    $xml = <<<XML
<?xml version="1.0"?>
<root>
    <name>John Doe</name>
    <age>30</age>
    <address>
        <city>New York</city>
        <zip>10001</zip>
    </address>
</root>
XML;

    $accessor = new DataAccessor($xml);
    $keys = $accessor->getStructure();

    expect($keys)->toHaveKey('name');
    expect($keys)->toHaveKey('age');
    expect($keys)->toHaveKey('address');
    expect($keys['name'])->toBe('string');
    expect($keys['age'])->toBe('string'); // XML values are strings
    expect($keys['address'])->toBe('array');
});

test('getStructure() handles stdClass objects', function(): void {
    $obj = new stdClass();
    $obj->name = 'John Doe';
    $obj->age = 30;
    $obj->active = true;
    $obj->address = new stdClass();
    $obj->address->city = 'New York';
    $obj->address->zip = 10001;

    $accessor = new DataAccessor($obj);
    $keys = $accessor->getStructure();

    // Note: stdClass objects are converted to arrays in the constructor,
    // so nested stdClass objects appear as 'array' type
    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'active' => 'bool',
        'address' => 'array',
        'address.city' => 'string',
        'address.zip' => 'int',
    ]);
});

test('getStructure() handles JsonSerializable objects', function(): void {
    $obj = new class implements JsonSerializable {
        /** @return array<string, mixed> */
        public function jsonSerialize(): array
        {
            return [
                'name' => 'John Doe',
                'age' => 30,
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ];
        }
    };

    $accessor = new DataAccessor($obj);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'settings' => 'array',
        'settings.theme' => 'string',
        'settings.notifications' => 'bool',
    ]);
});

test('getStructure() handles SimpleDto objects', function(): void {
    $dto = new SimpleUserDto(
        name: 'John Doe',
        email: 'john@example.com',
        age: 30
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'email' => 'string',
        'age' => 'int',
    ]);
});

test('getStructure() handles nested SimpleDto objects', function(): void {
    $address = new SimpleAddressDto(
        street: 'Main St',
        city: 'New York',
        zip: 10001
    );

    $dto = new SimpleUserWithAddressDto(
        name: 'John Doe',
        address: $address
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'address' => '\\SimpleAddressDto',
        'address.street' => 'string',
        'address.city' => 'string',
        'address.zip' => 'int',
    ]);
});

test('getStructure() handles SimpleDto with array of Dtos', function(): void {
    $dto = new SimpleUserWithEmailsDto(
        name: 'John Doe',
        emails: [
            new SimpleEmailDto(email: 'john@work.com', type: 'work', verified: true),
            new SimpleEmailDto(email: 'john@home.com', type: 'home', verified: false),
        ]
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'emails' => 'array',
        'emails.*' => '\\SimpleEmailDto',
        'emails.*.email' => 'string',
        'emails.*.type' => 'string',
        'emails.*.verified' => 'bool',
    ]);
});

test('getStructureMultidimensional() handles JSON string input', function(): void {
    $json = json_encode([
        'name' => 'John Doe',
        'age' => 30,
        'address' => [
            'city' => 'New York',
            'zip' => 10001,
        ],
    ]);

    $accessor = new DataAccessor($json);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'address' => [
            'city' => 'string',
            'zip' => 'int',
        ],
    ]);
});

test('getStructureMultidimensional() handles XML string input', function(): void {
    $xml = <<<XML
<?xml version="1.0"?>
<root>
    <name>John Doe</name>
    <age>30</age>
    <address>
        <city>New York</city>
        <zip>10001</zip>
    </address>
</root>
XML;

    $accessor = new DataAccessor($xml);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toHaveKey('name');
    expect($keys)->toHaveKey('age');
    expect($keys)->toHaveKey('address');
    expect($keys['name'])->toBe('string');
    expect($keys['address'])->toBeArray();
});

test('getStructureMultidimensional() handles stdClass objects', function(): void {
    $obj = new stdClass();
    $obj->name = 'John Doe';
    $obj->age = 30;
    $obj->address = new stdClass();
    $obj->address->city = 'New York';
    $obj->address->zip = 10001;

    $accessor = new DataAccessor($obj);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'age' => 'int',
        'address' => [
            'city' => 'string',
            'zip' => 'int',
        ],
    ]);
});

test('getStructureMultidimensional() handles SimpleDto objects', function(): void {
    $dto = new SimpleUserDto(
        name: 'John Doe',
        email: 'john@example.com',
        age: 30
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'email' => 'string',
        'age' => 'int',
    ]);
});

test('getStructureMultidimensional() handles nested SimpleDto objects', function(): void {
    $address = new SimpleAddressDto(
        street: 'Main St',
        city: 'New York',
        zip: 10001
    );

    $dto = new SimpleUserWithAddressDto(
        name: 'John Doe',
        address: $address
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'address' => '\\SimpleAddressDto',
    ]);
});

test('getStructureMultidimensional() handles SimpleDto with array of Dtos', function(): void {
    $dto = new SimpleUserWithEmailsDto(
        name: 'John Doe',
        emails: [
            new SimpleEmailDto(email: 'john@work.com', type: 'work', verified: true),
            new SimpleEmailDto(email: 'john@home.com', type: 'home', verified: false),
        ]
    );

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'emails' => [
            '*' => '\\SimpleEmailDto',
        ],
    ]);
});

test('getStructure() handles deeply nested SimpleDto structures', function(): void {
    $company = new SimpleCompanyDto(
        name: 'Tech Corp',
        departments: [
            new SimpleDepartmentDto(
                name: 'Engineering',
                employees: [
                    new SimpleUserDto(name: 'Alice', email: 'alice@tech.com', age: 30),
                    new SimpleUserDto(name: 'Bob', email: 'bob@tech.com', age: 25),
                ]
            ),
            new SimpleDepartmentDto(
                name: 'Sales',
                employees: [
                    new SimpleUserDto(name: 'Charlie', email: 'charlie@tech.com', age: 35),
                ]
            ),
        ]
    );

    $accessor = new DataAccessor($company);
    $keys = $accessor->getStructure();

    expect($keys)->toHaveKey('name');
    expect($keys)->toHaveKey('departments');
    expect($keys)->toHaveKey('departments.*');
    expect($keys)->toHaveKey('departments.*.name');
    expect($keys)->toHaveKey('departments.*.employees');
    expect($keys)->toHaveKey('departments.*.employees.*');
    expect($keys)->toHaveKey('departments.*.employees.*.name');
    expect($keys)->toHaveKey('departments.*.employees.*.email');
    expect($keys)->toHaveKey('departments.*.employees.*.age');

    expect($keys['name'])->toBe('string');
    expect($keys['departments'])->toBe('array');
    expect($keys['departments.*'])->toBe('\\SimpleDepartmentDto');
    expect($keys['departments.*.name'])->toBe('string');
    expect($keys['departments.*.employees'])->toBe('array');
    expect($keys['departments.*.employees.*'])->toBe('\\SimpleUserDto');
    expect($keys['departments.*.employees.*.name'])->toBe('string');
    expect($keys['departments.*.employees.*.email'])->toBe('string');
    expect($keys['departments.*.employees.*.age'])->toBe('int');
});

test('getStructureMultidimensional() handles deeply nested SimpleDto structures', function(): void {
    $company = new SimpleCompanyDto(
        name: 'Tech Corp',
        departments: [
            new SimpleDepartmentDto(
                name: 'Engineering',
                employees: [
                    new SimpleUserDto(name: 'Alice', email: 'alice@tech.com', age: 30),
                ]
            ),
        ]
    );

    $accessor = new DataAccessor($company);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([
        'name' => 'string',
        'departments' => [
            '*' => '\\SimpleDepartmentDto',
        ],
    ]);
});

test('getStructure() handles complex JSON with nested arrays', function(): void {
    $json = json_encode([
        'company' => [
            'name' => 'Tech Corp',
            'employees' => [
                [
                    'name' => 'Alice',
                    'skills' => ['PHP', 'JavaScript'],
                ],
                [
                    'name' => 'Bob',
                    'skills' => ['Python', 'Go'],
                ],
            ],
        ],
    ]);

    $accessor = new DataAccessor($json);
    $keys = $accessor->getStructure();

    expect($keys)->toHaveKey('company');
    expect($keys)->toHaveKey('company.name');
    expect($keys)->toHaveKey('company.employees');
    expect($keys)->toHaveKey('company.employees.*');
    expect($keys)->toHaveKey('company.employees.*.name');
    expect($keys)->toHaveKey('company.employees.*.skills');
    expect($keys)->toHaveKey('company.employees.*.skills.*');
});

test('getStructure() handles XML with attributes', function(): void {
    $xml = <<<XML
<?xml version="1.0"?>
<root>
    <user id="1" active="true">
        <name>John Doe</name>
        <email>john@example.com</email>
    </user>
</root>
XML;

    $accessor = new DataAccessor($xml);
    $keys = $accessor->getStructure();

    expect($keys)->toHaveKey('user');
    expect($keys['user'])->toBe('array');
});

test('getStructure() handles empty SimpleDto', function(): void {
    $dto = new class extends SimpleDto
    {
    };

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([]);
});

test('getStructureMultidimensional() handles empty SimpleDto', function(): void {
    $dto = new class extends SimpleDto
    {
    };

    $accessor = new DataAccessor($dto);
    $keys = $accessor->getStructureMultidimensional();

    expect($keys)->toBe([]);
});

test('getStructure() handles mixed nested structures with objects and arrays', function(): void {
    $obj = new stdClass();
    $obj->name = 'Company';
    $obj->departments = [
        [
            'name' => 'Engineering',
            'budget' => 100000.50,
        ],
        [
            'name' => 'Sales',
            'budget' => 75000.25,
        ],
    ];

    $accessor = new DataAccessor($obj);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'name' => 'string',
        'departments' => 'array',
        'departments.*' => 'array',
        'departments.*.name' => 'string',
        'departments.*.budget' => 'float',
    ]);
});

test('getStructure() detects custom class types', function(): void {
    $customObj = new class {
        public string $name = 'Test';
        public int $value = 42;
    };

    $data = [
        'item' => $customObj,
        'count' => 5,
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toHaveKey('item');
    expect($keys)->toHaveKey('count');
    expect($keys['count'])->toBe('int');
});

test('getStructure() handles union types for mixed array elements', function(): void {
    $data = [
        'values' => [
            'string value',
            42,
            null,
            true,
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'values' => 'array',
        'values.*' => 'bool|int|null|string',
    ]);
});

test('getStructure() handles union types for mixed nested structures', function(): void {
    $data = [
        'items' => [
            ['name' => 'Item 1', 'price' => 10.5],
            ['name' => 'Item 2', 'price' => 20],
            ['name' => 'Item 3', 'price' => null],
        ],
    ];

    $accessor = new DataAccessor($data);
    $keys = $accessor->getStructure();

    expect($keys)->toBe([
        'items' => 'array',
        'items.*' => 'array',
        'items.*.name' => 'string',
        'items.*.price' => 'float|int|null',
    ]);
});
