<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Tests\Unit;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\ReverseDataMapper;
use PHPUnit\Framework\TestCase;

class ReverseDataMapperTest extends TestCase
{
    public function test_reverse_simple_mapping(): void
    {
        // Arrange
        $user = ['firstName' => 'John', 'email' => 'john@example.com'];
        $dto = ['full_name' => null, 'contact_email' => null];
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        // Act - Forward mapping
        $forwardResult = DataMapper::map($user, $dto, $mapping);

        // Assert
        $this->assertSame('John', $forwardResult['full_name']);
        $this->assertSame('john@example.com', $forwardResult['contact_email']);

        // Act - Reverse mapping
        $dtoData = ['full_name' => 'Jane', 'contact_email' => 'jane@example.com'];
        $userData = ['firstName' => null, 'email' => null];
        $reverseResult = ReverseDataMapper::map($dtoData, $userData, $mapping);

        // Assert
        $this->assertSame('Jane', $reverseResult['firstName']);
        $this->assertSame('jane@example.com', $reverseResult['email']);
    }

    public function test_reverse_nested_mapping(): void
    {
        // Arrange
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

        // Act - Forward mapping
        $forwardResult = DataMapper::map($source, $target, $mapping);

        // Assert
        $this->assertSame('Alice', $forwardResult['profile']['fullName']);
        $this->assertSame(30, $forwardResult['profile']['years']);
        $this->assertSame('Berlin', $forwardResult['location']['town']);

        // Act - Reverse mapping
        $reverseSource = [
            'profile' => ['fullName' => 'Bob', 'years' => 25],
            'location' => ['town' => 'Munich'],
        ];
        $reverseTarget = [
            'user' => ['name' => null, 'age' => null],
            'address' => ['city' => null],
        ];
        $reverseResult = ReverseDataMapper::map($reverseSource, $reverseTarget, $mapping);

        // Assert
        $this->assertSame('Bob', $reverseResult['user']['name']);
        $this->assertSame(25, $reverseResult['user']['age']);
        $this->assertSame('Munich', $reverseResult['address']['city']);
    }

    public function test_reverse_map_from_template(): void
    {
        // Arrange
        $template = [
            'profile' => [
                'name' => '{{ user.name }}',
                'email' => '{{ user.email }}',
            ],
            'company' => [
                'name' => '{{ organization.name }}',
            ],
        ];

        // Act - Forward mapping: sources -> template structure
        $sources = [
            'user' => ['name' => 'Charlie', 'email' => 'charlie@example.com'],
            'organization' => ['name' => 'Acme Corp'],
        ];
        $forwardResult = DataMapper::mapFromTemplate($template, $sources);

        // Assert - Forward creates template structure
        $this->assertSame('Charlie', $forwardResult['profile']['name']);
        $this->assertSame('charlie@example.com', $forwardResult['profile']['email']);
        $this->assertSame('Acme Corp', $forwardResult['company']['name']);

        // Act - Reverse mapping: template structure -> sources
        // ReverseDataMapper should reverse the template and use mapToTargetsFromTemplate
        $data = [
            'profile' => ['name' => 'David', 'email' => 'david@example.com'],
            'company' => ['name' => 'Tech Inc'],
        ];
        $targets = [
            'user' => ['name' => null, 'email' => null],
            'organization' => ['name' => null],
        ];
        $reverseResult = ReverseDataMapper::mapToTargetsFromTemplate($data, $template, $targets);

        // Assert - Reverse writes to targets
        $this->assertSame('David', $reverseResult['user']['name']);
        $this->assertSame('david@example.com', $reverseResult['user']['email']);
        $this->assertSame('Tech Inc', $reverseResult['organization']['name']);
    }

    public function test_reverse_mapping_with_wildcards(): void
    {
        // Arrange
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

        // Act - Forward mapping
        $forwardResult = DataMapper::map($source, $target, $mapping);

        // Assert
        $this->assertSame(['Alice', 'Bob'], $forwardResult['names']);

        // Act - Reverse mapping
        $reverseSource = ['names' => ['Charlie', 'David']];
        $reverseTarget = ['users' => []];
        $reverseResult = ReverseDataMapper::map($reverseSource, $reverseTarget, $mapping);

        // Assert
        /** @var array<int, array<string, mixed>> $users */
        $users = $reverseResult['users'];
        $this->assertCount(2, $users);
        $this->assertSame('Charlie', $users[0]['name']);
        $this->assertSame('David', $users[1]['name']);
    }

    public function test_reverse_auto_map(): void
    {
        // Arrange
        $source = ['name' => 'John', 'email' => 'john@example.com'];
        $target = ['name' => null, 'email' => null];

        // Act - Forward mapping
        $forwardResult = DataMapper::autoMap($source, $target);

        // Assert
        $this->assertSame('John', $forwardResult['name']);
        $this->assertSame('john@example.com', $forwardResult['email']);

        // Act - Reverse mapping (should be the same as forward)
        $reverseResult = ReverseDataMapper::autoMap($source, $target);

        // Assert
        $this->assertSame('John', $reverseResult['name']);
        $this->assertSame('john@example.com', $reverseResult['email']);
    }

    public function test_bidirectional_mapping(): void
    {
        // Arrange
        $originalUser = ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@example.com'];
        $mapping = [
            'full_name' => '{{ firstName }}',
            'contact_email' => '{{ email }}',
        ];

        // Act - Forward: User -> DTO
        $dto = DataMapper::map($originalUser, [], $mapping);

        // Assert
        $this->assertSame('John', $dto['full_name']);
        $this->assertSame('john@example.com', $dto['contact_email']);

        // Act - Reverse: DTO -> User
        $reconstructedUser = ReverseDataMapper::map($dto, [], $mapping);

        // Assert - Should get back the original values
        $this->assertSame('John', $reconstructedUser['firstName']);
        $this->assertSame('john@example.com', $reconstructedUser['email']);
    }

    public function test_complex_nested_bidirectional_mapping(): void
    {
        // Arrange - Complex nested source structure
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

        // Complex nested mapping
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

        // Act - Step 1: Forward mapping (original -> transformed)
        $transformedData = DataMapper::map($originalData, [], $mapping);

        // Assert - Verify forward mapping worked
        $this->assertSame('TechCorp Solutions', $transformedData['organization']['companyName']);
        $this->assertSame(2015, $transformedData['organization']['yearFounded']);
        $this->assertSame('info@techcorp.example', $transformedData['organization']['contactInfo']['emailAddress']);
        $this->assertSame('+1-555-0100', $transformedData['organization']['contactInfo']['phoneNumber']);

        /** @var array<int, array<string, mixed>> $divisions */
        $divisions = $transformedData['divisions'];
        $this->assertCount(2, $divisions);
        $this->assertSame('Engineering', $divisions[0]['divisionName']);
        $this->assertSame('ENG', $divisions[0]['divisionCode']);
        $this->assertSame(5000000.00, $divisions[0]['annualBudget']);

        /** @var array<int, array<string, mixed>> $staff */
        $staff = $transformedData['staff'];
        $this->assertCount(3, $staff);
        $this->assertSame('Alice Johnson', $staff[0]['fullName']);
        $this->assertSame('Senior Developer', $staff[0]['position']);
        $this->assertSame('Engineering', $staff[0]['dept']);

        /** @var array<int, array<string, mixed>> $initiatives */
        $initiatives = $transformedData['initiatives'];
        $this->assertCount(2, $initiatives);
        $this->assertSame('Cloud Migration', $initiatives[0]['projectTitle']);
        $this->assertSame('active', $initiatives[0]['projectStatus']);
        $this->assertSame(500000, $initiatives[0]['projectBudget']);

        // Act - Step 2: Reverse mapping (transformed -> reconstructed)
        $reconstructedData = ReverseDataMapper::map($transformedData, [], $mapping);

        // Assert - Compare original and reconstructed data
        // They should be identical!
        $this->assertSame($originalData['company']['name'], $reconstructedData['company']['name']);
        $this->assertSame($originalData['company']['founded'], $reconstructedData['company']['founded']);
        $this->assertSame(
            $originalData['company']['contact']['email'],
            $reconstructedData['company']['contact']['email']
        );
        $this->assertSame(
            $originalData['company']['contact']['phone'],
            $reconstructedData['company']['contact']['phone']
        );

        /** @var array<int, array<string, mixed>> $reconstructedDepartments */
        $reconstructedDepartments = $reconstructedData['departments'];
        $this->assertCount(count($originalData['departments']), $reconstructedDepartments);
        $this->assertSame($originalData['departments'][0]['name'], $reconstructedDepartments[0]['name']);
        $this->assertSame($originalData['departments'][0]['code'], $reconstructedDepartments[0]['code']);
        $this->assertSame($originalData['departments'][0]['budget'], $reconstructedDepartments[0]['budget']);
        $this->assertSame($originalData['departments'][1]['name'], $reconstructedDepartments[1]['name']);

        /** @var array<int, array<string, mixed>> $reconstructedEmployees */
        $reconstructedEmployees = $reconstructedData['employees'];
        $this->assertCount(count($originalData['employees']), $reconstructedEmployees);
        $this->assertSame($originalData['employees'][0]['name'], $reconstructedEmployees[0]['name']);
        $this->assertSame($originalData['employees'][0]['role'], $reconstructedEmployees[0]['role']);
        $this->assertSame($originalData['employees'][0]['department'], $reconstructedEmployees[0]['department']);
        $this->assertSame($originalData['employees'][1]['name'], $reconstructedEmployees[1]['name']);
        $this->assertSame($originalData['employees'][2]['name'], $reconstructedEmployees[2]['name']);

        /** @var array<int, array<string, mixed>> $reconstructedProjects */
        $reconstructedProjects = $reconstructedData['projects'];
        $this->assertCount(count($originalData['projects']), $reconstructedProjects);
        $this->assertSame($originalData['projects'][0]['title'], $reconstructedProjects[0]['title']);
        $this->assertSame($originalData['projects'][0]['status'], $reconstructedProjects[0]['status']);
        $this->assertSame($originalData['projects'][0]['budget'], $reconstructedProjects[0]['budget']);
        $this->assertSame($originalData['projects'][1]['title'], $reconstructedProjects[1]['title']);

        // Act - Step 3: Forward mapping again (reconstructed -> final)
        $finalTransformedData = DataMapper::map($reconstructedData, [], $mapping);

        // Assert - Compare first transformed and final transformed data
        // They should be identical!
        $this->assertEquals($transformedData, $finalTransformedData);

        // Final assertion: The complete round-trip should preserve all data
        $this->assertSame(
            $originalData['company']['name'],
            $reconstructedData['company']['name'],
            'Company name should be preserved through round-trip'
        );
        $this->assertSame(
            $originalData['departments'][0]['name'],
            $reconstructedData['departments'][0]['name'],
            'Department name should be preserved through round-trip'
        );
        $this->assertSame(
            $originalData['employees'][0]['name'],
            $reconstructedData['employees'][0]['name'],
            'Employee name should be preserved through round-trip'
        );
        $this->assertSame(
            $originalData['projects'][0]['title'],
            $reconstructedData['projects'][0]['title'],
            'Project title should be preserved through round-trip'
        );
    }
}

