<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\ReverseDataMapper;

describe('ReverseDataMapper', function(): void {
    it('reverses simple mapping', function(): void {
        $user = ['firstName' => 'John', 'email' => 'john@example.com'];
        $dto = ['full_name' => null, 'contact_email' => null];
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        // Forward mapping
        $forwardResult = DataMapper::map($user, $dto, $mapping);

        expect($forwardResult['full_name'])->toBe('John');
        expect($forwardResult['contact_email'])->toBe('john@example.com');

        // Reverse mapping
        $dtoData = ['full_name' => 'Jane', 'contact_email' => 'jane@example.com'];
        $userData = ['firstName' => null, 'email' => null];
        $reverseResult = ReverseDataMapper::map($dtoData, $userData, $mapping);

        expect($reverseResult['firstName'])->toBe('Jane');
        expect($reverseResult['email'])->toBe('jane@example.com');
    });

    it('reverses nested mapping', function(): void {
        $source = [
            'user' => ['name' => 'Alice', 'age' => 30],
            'address' => ['city' => 'Berlin'],
        ];
        $target = [
            'profile' => ['fullName' => null, 'years' => null],
            'location' => ['town' => null],
        ];
        $mapping = [
            'profile' => [
                'fullName' => '{{ user.name }}',
                'years' => '{{ user.age }}',
            ],
            'location' => [
                'town' => '{{ address.city }}',
            ],
        ];

        // Forward mapping
        $forwardResult = DataMapper::map($source, $target, $mapping);

        expect($forwardResult['profile']['fullName'])->toBe('Alice');
        expect($forwardResult['profile']['years'])->toBe(30);
        expect($forwardResult['location']['town'])->toBe('Berlin');

        // Reverse mapping
        $reverseSource = [
            'profile' => ['fullName' => 'Bob', 'years' => 25],
            'location' => ['town' => 'Munich'],
        ];
        $reverseTarget = [
            'user' => ['name' => null, 'age' => null],
            'address' => ['city' => null],
        ];
        $reverseResult = ReverseDataMapper::map($reverseSource, $reverseTarget, $mapping);

        expect($reverseResult['user']['name'])->toBe('Bob');
        expect($reverseResult['user']['age'])->toBe(25);
        expect($reverseResult['address']['city'])->toBe('Munich');
    });

    it('reverses mapFromTemplate', function(): void {
        $template = [
            'profile' => [
                'name' => '{{ user.name }}',
                'email' => '{{ user.email }}',
            ],
            'company' => [
                'name' => '{{ organization.name }}',
            ],
        ];

        // Forward mapping: sources -> template structure
        $sources = [
            'user' => ['name' => 'Charlie', 'email' => 'charlie@example.com'],
            'organization' => ['name' => 'Acme Corp'],
        ];
        $forwardResult = DataMapper::mapFromTemplate($template, $sources);

        expect($forwardResult['profile']['name'])->toBe('Charlie');
        expect($forwardResult['profile']['email'])->toBe('charlie@example.com');
        expect($forwardResult['company']['name'])->toBe('Acme Corp');

        // Reverse mapping: template structure -> sources
        $data = [
            'profile' => ['name' => 'David', 'email' => 'david@example.com'],
            'company' => ['name' => 'Tech Inc'],
        ];
        $targets = [
            'user' => ['name' => null, 'email' => null],
            'organization' => ['name' => null],
        ];
        $reverseResult = ReverseDataMapper::mapToTargetsFromTemplate($data, $template, $targets);

        expect($reverseResult['user']['name'])->toBe('David');
        expect($reverseResult['user']['email'])->toBe('david@example.com');
        expect($reverseResult['organization']['name'])->toBe('Tech Inc');
    });

    it('reverses mapping with wildcards', function(): void {
        $source = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ],
        ];
        $target = ['names' => []];
        $mapping = [
            'names' => [
                '*' => '{{ users.*.name }}',
            ],
        ];

        // Forward mapping
        $forwardResult = DataMapper::map($source, $target, $mapping);

        expect($forwardResult['names'])->toBe(['Alice', 'Bob']);

        // Reverse mapping
        $reverseSource = ['names' => ['Charlie', 'David']];
        $reverseTarget = ['users' => []];
        $reverseResult = ReverseDataMapper::map($reverseSource, $reverseTarget, $mapping);

        expect($reverseResult['users'])->toHaveCount(2);
        expect($reverseResult['users'][0]['name'])->toBe('Charlie');
        expect($reverseResult['users'][1]['name'])->toBe('David');
    });

    it('reverses autoMap', function(): void {
        $source = ['name' => 'John', 'email' => 'john@example.com'];
        $target = ['name' => null, 'email' => null];

        // Forward mapping
        $forwardResult = DataMapper::autoMap($source, $target);

        expect($forwardResult['name'])->toBe('John');
        expect($forwardResult['email'])->toBe('john@example.com');

        // Reverse mapping (should be the same as forward)
        $reverseResult = ReverseDataMapper::autoMap($source, $target);

        expect($reverseResult['name'])->toBe('John');
        expect($reverseResult['email'])->toBe('john@example.com');
    });

    it('supports bidirectional mapping', function(): void {
        $originalUser = ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@example.com'];
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        // Forward: User -> DTO
        $dto = DataMapper::map($originalUser, [], $mapping);

        expect($dto['full_name'])->toBe('John');
        expect($dto['contact_email'])->toBe('john@example.com');

        // Reverse: DTO -> User
        $reconstructedUser = ReverseDataMapper::map($dto, [], $mapping);

        expect($reconstructedUser['firstName'])->toBe('John');
        expect($reconstructedUser['email'])->toBe('john@example.com');
    });

    it('handles complex nested bidirectional mapping', function(): void {
        $originalData = [
            'company' => [
                'name' => 'TechCorp Solutions',
                'founded' => 2015,
                'contact' => [
                    'email' => 'info@techcorp.example',
                    'phone' => '+1-555-0100',
                ],
            ],
            'departments' => [
                [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 5000000.00,
                ],
                [
                    'name' => 'Marketing',
                    'code' => 'MKT',
                    'budget' => 2000000.00,
                ],
            ],
            'employees' => [
                ['name' => 'Alice Johnson', 'role' => 'Senior Developer', 'department' => 'Engineering'],
                ['name' => 'Bob Smith', 'role' => 'Tech Lead', 'department' => 'Engineering'],
                ['name' => 'Charlie Brown', 'role' => 'Marketing Manager', 'department' => 'Marketing'],
            ],
            'projects' => [
                [
                    'title' => 'Cloud Migration',
                    'status' => 'active',
                    'budget' => 500000,
                ],
                [
                    'title' => 'Mobile App',
                    'status' => 'planning',
                    'budget' => 300000,
                ],
            ],
        ];

        $mapping = [
            'organization' => [
                'companyName' => '{{ company.name }}',
                'yearFounded' => '{{ company.founded }}',
                'contactInfo' => [
                    'emailAddress' => '{{ company.contact.email }}',
                    'phoneNumber' => '{{ company.contact.phone }}',
                ],
            ],
            'divisions' => [
                '*' => [
                    'divisionName' => '{{ departments.*.name }}',
                    'divisionCode' => '{{ departments.*.code }}',
                    'annualBudget' => '{{ departments.*.budget }}',
                ],
            ],
            'staff' => [
                '*' => [
                    'fullName' => '{{ employees.*.name }}',
                    'position' => '{{ employees.*.role }}',
                    'dept' => '{{ employees.*.department }}',
                ],
            ],
            'initiatives' => [
                '*' => [
                    'projectTitle' => '{{ projects.*.title }}',
                    'projectStatus' => '{{ projects.*.status }}',
                    'projectBudget' => '{{ projects.*.budget }}',
                ],
            ],
        ];

        // Step 1: Forward mapping (original -> transformed)
        $transformedData = DataMapper::map($originalData, [], $mapping);

        expect($transformedData['organization']['companyName'])->toBe('TechCorp Solutions');
        expect($transformedData['organization']['yearFounded'])->toBe(2015);
        expect($transformedData['organization']['contactInfo']['emailAddress'])->toBe('info@techcorp.example');
        expect($transformedData['organization']['contactInfo']['phoneNumber'])->toBe('+1-555-0100');

        expect($transformedData['divisions'])->toHaveCount(2);
        expect($transformedData['divisions'][0]['divisionName'])->toBe('Engineering');
        expect($transformedData['divisions'][0]['divisionCode'])->toBe('ENG');
        expect($transformedData['divisions'][0]['annualBudget'])->toBe(5000000.00);

        expect($transformedData['staff'])->toHaveCount(3);
        expect($transformedData['staff'][0]['fullName'])->toBe('Alice Johnson');
        expect($transformedData['staff'][0]['position'])->toBe('Senior Developer');
        expect($transformedData['staff'][0]['dept'])->toBe('Engineering');

        expect($transformedData['initiatives'])->toHaveCount(2);
        expect($transformedData['initiatives'][0]['projectTitle'])->toBe('Cloud Migration');
        expect($transformedData['initiatives'][0]['projectStatus'])->toBe('active');
        expect($transformedData['initiatives'][0]['projectBudget'])->toBe(500000);

        // Step 2: Reverse mapping (transformed -> reconstructed)
        $reconstructedData = ReverseDataMapper::map($transformedData, [], $mapping);

        expect($reconstructedData['company']['name'])->toBe($originalData['company']['name']);
        expect($reconstructedData['company']['founded'])->toBe($originalData['company']['founded']);
        expect($reconstructedData['company']['contact']['email'])->toBe($originalData['company']['contact']['email']);
        expect($reconstructedData['company']['contact']['phone'])->toBe($originalData['company']['contact']['phone']);

        expect($reconstructedData['departments'])->toHaveCount(count($originalData['departments']));
        expect($reconstructedData['departments'][0]['name'])->toBe($originalData['departments'][0]['name']);
        expect($reconstructedData['departments'][0]['code'])->toBe($originalData['departments'][0]['code']);
        expect($reconstructedData['departments'][0]['budget'])->toBe($originalData['departments'][0]['budget']);
        expect($reconstructedData['departments'][1]['name'])->toBe($originalData['departments'][1]['name']);

        expect($reconstructedData['employees'])->toHaveCount(count($originalData['employees']));
        expect($reconstructedData['employees'][0]['name'])->toBe($originalData['employees'][0]['name']);
        expect($reconstructedData['employees'][0]['role'])->toBe($originalData['employees'][0]['role']);
        expect($reconstructedData['employees'][0]['department'])->toBe($originalData['employees'][0]['department']);
        expect($reconstructedData['employees'][1]['name'])->toBe($originalData['employees'][1]['name']);
        expect($reconstructedData['employees'][2]['name'])->toBe($originalData['employees'][2]['name']);

        expect($reconstructedData['projects'])->toHaveCount(count($originalData['projects']));
        expect($reconstructedData['projects'][0]['title'])->toBe($originalData['projects'][0]['title']);
        expect($reconstructedData['projects'][0]['status'])->toBe($originalData['projects'][0]['status']);
        expect($reconstructedData['projects'][0]['budget'])->toBe($originalData['projects'][0]['budget']);
        expect($reconstructedData['projects'][1]['title'])->toBe($originalData['projects'][1]['title']);

        // Step 3: Forward mapping again (reconstructed -> final)
        $finalTransformedData = DataMapper::map($reconstructedData, [], $mapping);

        expect($finalTransformedData)->toEqual($transformedData);
    });

    it('works with pipelines', function(): void {
        $source = ['user' => ['name' => '  alice  ', 'email' => '  ALICE@EXAMPLE.COM  ']];
        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        // Forward with pipeline
        $forwardResult = DataMapper::pipe([
            new \event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings(),
        ])->map($source, [], $mapping);

        expect($forwardResult['profile']['name'])->toBe('alice');
        expect($forwardResult['profile']['email'])->toBe('ALICE@EXAMPLE.COM');

        // Reverse with pipeline
        $reverseSource = ['profile' => ['name' => '  bob  ', 'email' => '  BOB@EXAMPLE.COM  ']];
        $reverseResult = ReverseDataMapper::pipe([
            new \event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings(),
        ])->map($reverseSource, [], $mapping);

        expect($reverseResult['user']['name'])->toBe('bob');
        expect($reverseResult['user']['email'])->toBe('BOB@EXAMPLE.COM');
    });

    it('works with hooks', function(): void {
        $source = ['user' => ['name' => 'alice']];
        $mapping = ['profile.name' => '{{ user.name }}'];

        $hookCalled = false;
        $hooks = [
            'preTransform' => function($value) use (&$hookCalled) {
                $hookCalled = true;
                return is_string($value) ? strtoupper($value) : $value;
            },
        ];

        // Forward with hooks
        $forwardResult = DataMapper::map($source, [], $mapping, true, false, $hooks);

        expect($hookCalled)->toBeTrue();
        expect($forwardResult['profile']['name'])->toBe('ALICE');

        // Reverse with hooks
        $hookCalled = false;
        $reverseSource = ['profile' => ['name' => 'bob']];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping, true, false, $hooks);

        expect($hookCalled)->toBeTrue();
        expect($reverseResult['user']['name'])->toBe('BOB');
    });

    it('works with static values', function(): void {
        $source = ['user' => ['name' => 'Alice']];
        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.type' => 'premium', // Static value without __static__ marker
        ];

        // Forward mapping
        $forwardResult = DataMapper::map($source, [], $mapping);

        expect($forwardResult['profile']['name'])->toBe('Alice');
        expect($forwardResult['profile']['type'])->toBe('premium');

        // Reverse mapping - static values should be ignored
        $reverseSource = ['profile' => ['name' => 'Bob', 'type' => 'premium']];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping);

        expect($reverseResult['user']['name'])->toBe('Bob');
    });

    it('works with skipNull option', function(): void {
        $source = ['user' => ['name' => 'Alice', 'email' => null]];
        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.email' => '{{ user.email }}',
        ];

        // Forward with skipNull
        $forwardResult = DataMapper::map($source, [], $mapping, true);

        expect($forwardResult)->toBe(['profile' => ['name' => 'Alice']]);

        // Reverse with skipNull
        $reverseSource = ['profile' => ['name' => 'Bob', 'email' => null]];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping, true);

        expect($reverseResult)->toBe(['user' => ['name' => 'Bob']]);
    });

    it('works with reindexWildcard option', function(): void {
        $source = [
            'items' => [
                5 => 'alice',
                10 => 'bob',
            ],
        ];
        $mapping = [
            'users.*' => '{{ items.* }}',
        ];

        // Forward with reindexWildcard
        $forwardResult = DataMapper::map($source, [], $mapping, true, true);

        expect($forwardResult['users'])->toHaveKey(0);
        expect($forwardResult['users'])->toHaveKey(1);
        expect($forwardResult['users'][0])->toBe('alice');
        expect($forwardResult['users'][1])->toBe('bob');

        // Reverse with reindexWildcard
        $reverseSource = [
            'users' => [
                7 => 'charlie',
                12 => 'david',
            ],
        ];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping, true, true);

        expect($reverseResult['items'])->toHaveKey(0);
        expect($reverseResult['items'])->toHaveKey(1);
        expect($reverseResult['items'][0])->toBe('charlie');
        expect($reverseResult['items'][1])->toBe('david');
    });

    it('works with objects and DTOs', function(): void {
        $source = (object)['user' => (object)['name' => 'Alice']];
        $mapping = ['profile.name' => '{{ user.name }}'];

        // Forward mapping
        $forwardResult = DataMapper::map($source, [], $mapping);

        expect($forwardResult['profile']['name'])->toBe('Alice');

        // Reverse mapping
        $reverseSource = ['profile' => ['name' => 'Bob']];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping);

        expect($reverseResult['user']['name'])->toBe('Bob');
    });

    it('handles deep nested structures', function(): void {
        $source = [
            'company' => [
                'departments' => [
                    ['name' => 'Engineering', 'location' => 'Berlin'],
                    ['name' => 'Marketing', 'location' => 'Munich'],
                ],
            ],
        ];
        $mapping = [
            'org.teams.*.name' => '{{ company.departments.*.name }}',
            'org.teams.*.city' => '{{ company.departments.*.location }}',
        ];

        // Forward mapping
        $forwardResult = DataMapper::map($source, [], $mapping);

        expect($forwardResult['org']['teams'][0]['name'])->toBe('Engineering');
        expect($forwardResult['org']['teams'][0]['city'])->toBe('Berlin');
        expect($forwardResult['org']['teams'][1]['name'])->toBe('Marketing');
        expect($forwardResult['org']['teams'][1]['city'])->toBe('Munich');

        // Reverse mapping
        $reverseSource = [
            'org' => [
                'teams' => [
                    ['name' => 'Sales', 'city' => 'Hamburg'],
                    ['name' => 'Support', 'city' => 'Frankfurt'],
                ],
            ],
        ];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping);

        expect($reverseResult['company']['departments'][0]['name'])->toBe('Sales');
        expect($reverseResult['company']['departments'][0]['location'])->toBe('Hamburg');
        expect($reverseResult['company']['departments'][1]['name'])->toBe('Support');
        expect($reverseResult['company']['departments'][1]['location'])->toBe('Frankfurt');
    });

    it('handles exception handling', function(): void {
        $source = ['user' => ['name' => 'Alice']];
        $mapping = [
            'profile.name' => '{{ user.name }}',
            'profile.invalid' => '{{ user.nonexistent.deeply.nested }}',
        ];

        // Forward mapping with exception
        $forwardResult = DataMapper::map($source, [], $mapping);

        expect($forwardResult['profile']['name'])->toBe('Alice');

        // Reverse mapping with exception
        $reverseSource = ['profile' => ['name' => 'Bob']];
        $reverseResult = ReverseDataMapper::map($reverseSource, [], $mapping);

        expect($reverseResult['user']['name'])->toBe('Bob');
    });
});