<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('DataMapper to Array', function(): void {
    describe('Basic array mapping', function(): void {
        it('loads identical data from JSON and XML files', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map from JSON
            $jsonTarget = [];
            $jsonMapping = [
                'company_name' => '{{ company.name }}',
                'company_reg' => '{{ company.registration_number }}',
                'company_email' => '{{ company.email }}',
                'company_phone' => '{{ company.phone }}',
            ];
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonTarget)->template(
                $jsonMapping
            )->map()->getTarget();

            // Map from XML (different structure)
            $xmlTarget = [];
            $xmlMapping = [
                'company_name' => '{{ name }}',
                'company_reg' => '{{ registration_number }}',
                'company_email' => '{{ email }}',
                'company_phone' => '{{ phone }}',
            ];
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlTarget)->template(
                $xmlMapping
            )->map()->getTarget();

            // Compare string values - should be identical
            expect($jsonResult['company_name'])->toBe($xmlResult['company_name']);
            expect($jsonResult['company_reg'])->toBe($xmlResult['company_reg']);
            expect($jsonResult['company_email'])->toBe($xmlResult['company_email']);
            expect($jsonResult['company_phone'])->toBe($xmlResult['company_phone']);

            // Verify specific values
            expect($jsonResult['company_name'])->toBe('TechCorp Solutions');
            expect($jsonResult['company_reg'])->toBe('REG-2024-001');
            expect($jsonResult['company_email'])->toBe('info@techcorp.example');
            expect($jsonResult['company_phone'])->toBe('+1-555-0123');
        });

        it('works with array target', function(): void {
            $jsonFile = sys_get_temp_dir() . '/test_array.json';
            $jsonData = [
                'name' => 'Test',
                'value' => 123,
            ];
            file_put_contents($jsonFile, json_encode($jsonData));

            $target = [];
            $mapping = [
                'name' => '{{ name }}',
                'value' => '{{ value }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Test');
            expect($result['value'])->toBe(123);

            // Cleanup
            unlink($jsonFile);
        });
    });

    describe('Nested array mapping', function(): void {
        it('maps complete Company with Departments in single DataMapper call from JSON', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Map everything in one go - Company fields + Department arrays
            $target = [];
            $mapping = [
                'company_name' => '{{ company.name }}',
                'company_reg' => '{{ company.registration_number }}',
                'company_email' => '{{ company.email }}',
                'company_phone' => '{{ company.phone }}',
                'company_founded_year' => '{{ company.founded_year }}',
                'company_employee_count' => '{{ company.employee_count }}',
                'company_annual_revenue' => '{{ company.annual_revenue }}',
                'company_is_active' => '{{ company.is_active }}',
                'department_names' => '{{ company.departments.*.name }}',
                'department_codes' => '{{ company.departments.*.code }}',
                'department_budgets' => '{{ company.departments.*.budget }}',
                'department_employee_counts' => '{{ company.departments.*.employee_count }}',
                'department_manager_names' => '{{ company.departments.*.manager_name }}',
            ];
            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify Company data
            expect($result)->toBeArray();
            expect($result['company_name'])->toBe('TechCorp Solutions');
            expect($result['company_reg'])->toBe('REG-2024-001');
            expect($result['company_email'])->toBe('info@techcorp.example');
            expect($result['company_phone'])->toBe('+1-555-0123');
            expect($result['company_founded_year'])->toBe(2015);
            expect($result['company_employee_count'])->toBe(250);
            expect($result['company_annual_revenue'])->toBe(15750000.50);
            expect($result['company_is_active'])->toBe(true);

            // Verify Department arrays
            expect($result['department_names'])->toBeArray();
            expect($result['department_names'])->toHaveCount(3);
            expect($result['department_names'])->toBe(['Engineering', 'Sales', 'Human Resources']);

            expect($result['department_codes'])->toBeArray();
            expect($result['department_codes'])->toHaveCount(3);
            expect($result['department_codes'])->toBe(['ENG', 'SAL', 'HR']);

            expect($result['department_budgets'])->toBeArray();
            expect($result['department_budgets'])->toHaveCount(3);
            expect($result['department_budgets'])->toBe([5000000.00, 3000000.00, 1500000.00]);

            expect($result['department_employee_counts'])->toBeArray();
            expect($result['department_employee_counts'])->toHaveCount(3);
            expect($result['department_employee_counts'])->toBe([120, 80, 50]);

            expect($result['department_manager_names'])->toBeArray();
            expect($result['department_manager_names'])->toHaveCount(3);
            expect($result['department_manager_names'])->toBe(['Alice Johnson', 'Bob Smith', 'Carol Williams']);
        });

        it('maps complete Company with Departments in single DataMapper call from XML', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map everything in one go - Company fields + Department arrays
            $target = [];
            $mapping = [
                'company_name' => '{{ name }}',
                'company_reg' => '{{ registration_number }}',
                'company_email' => '{{ email }}',
                'company_phone' => '{{ phone }}',
                'company_founded_year' => '{{ founded_year }}',
                'company_employee_count' => '{{ employee_count }}',
                'company_annual_revenue' => '{{ annual_revenue }}',
                'company_is_active' => '{{ is_active }}',
                'department_names' => '{{ departments.department.*.name }}',
                'department_codes' => '{{ departments.department.*.code }}',
                'department_budgets' => '{{ departments.department.*.budget }}',
                'department_employee_counts' => '{{ departments.department.*.employee_count }}',
                'department_manager_names' => '{{ departments.department.*.manager_name }}',
            ];
            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify Company data (XML values are strings, but casts should convert them)
            expect($result)->toBeArray();
            expect($result['company_name'])->toBe('TechCorp Solutions');
            expect($result['company_reg'])->toBe('REG-2024-001');
            expect($result['company_email'])->toBe('info@techcorp.example');
            expect($result['company_phone'])->toBe('+1-555-0123');
            expect($result['company_founded_year'])->toBe('2015'); // String from XML
            expect($result['company_employee_count'])->toBe('250'); // String from XML
            expect($result['company_annual_revenue'])->toBe('15750000.50'); // String from XML
            expect($result['company_is_active'])->toBe('true'); // String from XML

            // Verify Department arrays (all strings from XML)
            expect($result['department_names'])->toBeArray();
            expect($result['department_names'])->toHaveCount(3);
            expect($result['department_names'])->toBe(['Engineering', 'Sales', 'Human Resources']);

            expect($result['department_codes'])->toBeArray();
            expect($result['department_codes'])->toHaveCount(3);
            expect($result['department_codes'])->toBe(['ENG', 'SAL', 'HR']);

            expect($result['department_budgets'])->toBeArray();
            expect($result['department_budgets'])->toHaveCount(3);
            expect($result['department_budgets'])->toBe(['5000000.00', '3000000.00', '1500000.00']);

            expect($result['department_employee_counts'])->toBeArray();
            expect($result['department_employee_counts'])->toHaveCount(3);
            expect($result['department_employee_counts'])->toBe(['120', '80', '50']);

            expect($result['department_manager_names'])->toBeArray();
            expect($result['department_manager_names'])->toHaveCount(3);
            expect($result['department_manager_names'])->toBe(['Alice Johnson', 'Bob Smith', 'Carol Williams']);
        });

        it('maps nested departments from JSON to array', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];

            // Map the entire departments array
            $mapping = [
                'all_departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                        'manager_name' => '{{ company.departments.*.manager_name }}',
                    ],
                ],
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['all_departments'])->toBeArray();
            expect($result['all_departments'])->toHaveCount(3);

            // Verify first department
            expect($result['all_departments'][0])->toBeArray();
            expect($result['all_departments'][0]['name'])->toBe('Engineering');
            expect($result['all_departments'][0]['code'])->toBe('ENG');
            expect($result['all_departments'][0]['budget'])->toBe(5000000.00);
            expect($result['all_departments'][0]['employee_count'])->toBe(120);
            expect($result['all_departments'][0]['manager_name'])->toBe('Alice Johnson');
        });

        it('maps nested departments from XML to array', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $target = [];

            // Map the entire departments array
            $mapping = [
                'all_departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                        'employee_count' => '{{ departments.department.*.employee_count }}',
                        'manager_name' => '{{ departments.department.*.manager_name }}',
                    ],
                ],
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['all_departments'])->toBeArray();
            expect($result['all_departments'])->toHaveCount(3);

            // Verify first department (XML values are strings)
            expect($result['all_departments'][0])->toBeArray();
            expect($result['all_departments'][0]['name'])->toBe('Engineering');
            expect($result['all_departments'][0]['code'])->toBe('ENG');
            expect($result['all_departments'][0]['budget'])->toBe('5000000.00');
            expect($result['all_departments'][0]['employee_count'])->toBe('120');
            expect($result['all_departments'][0]['manager_name'])->toBe('Alice Johnson');
        });
    });

    describe('Wildcard mapping', function(): void {
        it('maps wildcard paths to arrays from JSON', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];

            $mapping = [
                'dept_names' => '{{ company.departments.*.name }}',
                'dept_codes' => '{{ company.departments.*.code }}',
                'dept_budgets' => '{{ company.departments.*.budget }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify all arrays have 3 elements
            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'])->toHaveCount(3);
            expect($result['dept_names'][0])->toBe('Engineering');
            expect($result['dept_names'][1])->toBe('Sales');
            expect($result['dept_names'][2])->toBe('Human Resources');

            expect($result['dept_codes'])->toBeArray();
            expect($result['dept_codes'])->toHaveCount(3);
            expect($result['dept_codes'][0])->toBe('ENG');
            expect($result['dept_codes'][1])->toBe('SAL');
            expect($result['dept_codes'][2])->toBe('HR');

            expect($result['dept_budgets'])->toBeArray();
            expect($result['dept_budgets'])->toHaveCount(3);
            expect($result['dept_budgets'][0])->toBe(5000000.00);
            expect($result['dept_budgets'][1])->toBe(3000000.00);
            expect($result['dept_budgets'][2])->toBe(1500000.00);
        });

        it('maps wildcard paths to arrays from XML', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $target = [];

            $mapping = [
                'dept_names' => '{{ departments.department.*.name }}',
                'dept_codes' => '{{ departments.department.*.code }}',
                'dept_budgets' => '{{ departments.department.*.budget }}',
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify all arrays have 3 elements
            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'])->toHaveCount(3);
            expect($result['dept_names'][0])->toBe('Engineering');
            expect($result['dept_names'][1])->toBe('Sales');
            expect($result['dept_names'][2])->toBe('Human Resources');

            expect($result['dept_codes'])->toBeArray();
            expect($result['dept_codes'])->toHaveCount(3);
            expect($result['dept_codes'][0])->toBe('ENG');
            expect($result['dept_codes'][1])->toBe('SAL');
            expect($result['dept_codes'][2])->toBe('HR');

            expect($result['dept_budgets'])->toBeArray();
            expect($result['dept_budgets'])->toHaveCount(3);
            expect($result['dept_budgets'][0])->toBe('5000000.00'); // XML returns strings
            expect($result['dept_budgets'][1])->toBe('3000000.00');
            expect($result['dept_budgets'][2])->toBe('1500000.00');
        });

        it('maps individual indexed paths from JSON', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];

            $mapping = [
                'dept0_name' => '{{ company.departments.0.name }}',
                'dept1_name' => '{{ company.departments.1.name }}',
                'dept2_name' => '{{ company.departments.2.name }}',
                'dept0_code' => '{{ company.departments.0.code }}',
                'dept1_code' => '{{ company.departments.1.code }}',
                'dept2_code' => '{{ company.departments.2.code }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify individual access
            expect($result['dept0_name'])->toBe('Engineering');
            expect($result['dept1_name'])->toBe('Sales');
            expect($result['dept2_name'])->toBe('Human Resources');

            expect($result['dept0_code'])->toBe('ENG');
            expect($result['dept1_code'])->toBe('SAL');
            expect($result['dept2_code'])->toBe('HR');
        });

        it('maps individual indexed paths from XML', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            $target = [];

            $mapping = [
                'dept0_name' => '{{ departments.department.0.name }}',
                'dept1_name' => '{{ departments.department.1.name }}',
                'dept2_name' => '{{ departments.department.2.name }}',
                'dept0_code' => '{{ departments.department.0.code }}',
                'dept1_code' => '{{ departments.department.1.code }}',
                'dept2_code' => '{{ departments.department.2.code }}',
            ];

            $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();

            // Verify individual access
            expect($result['dept0_name'])->toBe('Engineering');
            expect($result['dept1_name'])->toBe('Sales');
            expect($result['dept2_name'])->toBe('Human Resources');

            expect($result['dept0_code'])->toBe('ENG');
            expect($result['dept1_code'])->toBe('SAL');
            expect($result['dept2_code'])->toBe('HR');
        });

        it('compares wildcard results from JSON and XML', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map from JSON
            $jsonTarget = [];
            $jsonMapping = [
                'names' => '{{ company.departments.*.name }}',
                'codes' => '{{ company.departments.*.code }}',
            ];
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonTarget)->template(
                $jsonMapping
            )->map()->getTarget();

            // Map from XML
            $xmlTarget = [];
            $xmlMapping = [
                'names' => '{{ departments.department.*.name }}',
                'codes' => '{{ departments.department.*.code }}',
            ];
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlTarget)->template(
                $xmlMapping
            )->map()->getTarget();

            // Compare - should be identical
            expect($jsonResult['names'])->toEqual($xmlResult['names']);
            expect($jsonResult['codes'])->toEqual($xmlResult['codes']);

            // Verify structure
            expect($jsonResult['names'])->toBeArray();
            expect($jsonResult['names'])->toHaveCount(3);
            expect($jsonResult['codes'])->toBeArray();
            expect($jsonResult['codes'])->toHaveCount(3);
        });

        it('maps wildcard paths with reindexWildcard from JSON', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];

            $mapping = [
                'dept_names' => '{{ company.departments.*.name }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->reindexWildcard(
                true
            )->map()->getTarget(); // reindexWildcard = true

            // Verify array is reindexed (0, 1, 2)
            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'])->toHaveCount(3);
            /** @var array<int|string, mixed> $deptNames */
            $deptNames = $result['dept_names'];
            expect(array_keys($deptNames))->toBe([0, 1, 2]);
            expect($result['dept_names'][0])->toBe('Engineering');
            expect($result['dept_names'][1])->toBe('Sales');
            expect($result['dept_names'][2])->toBe('Human Resources');
        });

        it('maps wildcard paths without reindexWildcard from JSON', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            $target = [];

            $mapping = [
                'dept_names' => '{{ company.departments.*.name }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->reindexWildcard(
                false
            )->map()->getTarget(); // reindexWildcard = false

            // Verify array keeps original indices (0, 1, 2)
            expect($result['dept_names'])->toBeArray();
            expect($result['dept_names'])->toHaveCount(3);
            /** @var array<int|string, mixed> $deptNames */
            $deptNames = $result['dept_names'];
            expect(array_keys($deptNames))->toBe([0, 1, 2]);
            expect($result['dept_names'][0])->toBe('Engineering');
            expect($result['dept_names'][1])->toBe('Sales');
            expect($result['dept_names'][2])->toBe('Human Resources');
        });

        it('compares complete nested structure from JSON and XML', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map complete structure from JSON
            $jsonTarget = [];
            $jsonMapping = [
                'company_name' => '{{ company.name }}',
                'departments' => '{{ company.departments }}',
                'first_dept' => '{{ company.departments.0.name }}',
                'dept_count' => '{{ company.employee_count }}',
            ];
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonTarget)->template(
                $jsonMapping
            )->map()->getTarget();

            // Map complete structure from XML
            $xmlTarget = [];
            $xmlMapping = [
                'company_name' => '{{ name }}',
                'departments' => '{{ departments.department }}',
                'first_dept' => '{{ departments.department.0.name }}',
                'dept_count' => '{{ employee_count }}',
            ];
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlTarget)->template(
                $xmlMapping
            )->map()->getTarget();

            // Compare company names
            expect($jsonResult['company_name'])->toBe($xmlResult['company_name']);
            expect($jsonResult['company_name'])->toBe('TechCorp Solutions');

            // Compare first department name
            expect($jsonResult['first_dept'])->toBe($xmlResult['first_dept']);
            expect($jsonResult['first_dept'])->toBe('Engineering');

            // Verify departments arrays exist and have same count
            expect($jsonResult['departments'])->toBeArray();
            expect($xmlResult['departments'])->toBeArray();
            expect($jsonResult['departments'])->toHaveCount(3);
            expect($xmlResult['departments'])->toHaveCount(3);

            // Verify department names match
            expect($jsonResult['departments'][0]['name'])->toBe($xmlResult['departments'][0]['name']);
            expect($jsonResult['departments'][1]['name'])->toBe($xmlResult['departments'][1]['name']);
            expect($jsonResult['departments'][2]['name'])->toBe($xmlResult['departments'][2]['name']);

            // Verify total budget sum (convert XML strings to floats)
            /** @var array<int|string, mixed> $jsonDepartments */
            $jsonDepartments = $jsonResult['departments'];
            /** @var array<int|string, mixed> $xmlDepartments */
            $xmlDepartments = $xmlResult['departments'];

            $jsonBudgets = array_column($jsonDepartments, 'budget');
            $xmlBudgets = array_map('floatval', array_column($xmlDepartments, 'budget'));

            $jsonBudgetSum = array_sum($jsonBudgets);
            $xmlBudgetSum = array_sum($xmlBudgets);

            expect($jsonBudgetSum)->toBe(9500000.0);
            expect($xmlBudgetSum)->toBe(9500000.0);
        });
    });

    describe('File handling and exceptions', function(): void {
        it('loads and maps from JSON file', function(): void {
            // Create a temporary JSON file for testing
            $jsonFile = sys_get_temp_dir() . '/test_project.json';
            $jsonData = [
                'project_number' => 'PRJ-12345',
                'project_title' => 'Test Project',
                'total_amount' => 50000.50,
            ];
            file_put_contents($jsonFile, json_encode($jsonData));

            $target = [];

            $mapping = [
                'number' => '{{ project_number }}',
                'title' => '{{ project_title }}',
                'amount' => '{{ total_amount }}',
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result['number'])->toBe('PRJ-12345');
            expect($result['title'])->toBe('Test Project');
            expect($result['amount'])->toBe(50000.50);

            // Cleanup
            unlink($jsonFile);
        });

        it('throws exception for non-existent file', function(): void {
            $target = [];
            $mapping = ['number' => 'number'];

            $result = DataMapper::sourceFile('/non/existent/file.xml')->target($target)->template(
                $mapping
            )->map()->getTarget();
            expect($result)->toBeArray();
        })->throws(InvalidArgumentException::class, 'File not found');

        it('throws exception for unsupported file format', function(): void {
            // Create a temporary file with unsupported extension
            $txtFile = sys_get_temp_dir() . '/test.txt';
            file_put_contents($txtFile, 'test content');

            $target = [];
            $mapping = ['number' => 'number'];

            try {
                $result = DataMapper::sourceFile($txtFile)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeArray();
            } finally {
                unlink($txtFile);
            }
        })->throws(InvalidArgumentException::class, 'Unsupported file format');

        it('throws exception for invalid XML file', function(): void {
            // Create a temporary file with invalid XML
            $xmlFile = sys_get_temp_dir() . '/invalid.xml';
            file_put_contents($xmlFile, '<invalid><xml>');

            $target = [];
            $mapping = ['number' => 'number'];

            try {
                $result = DataMapper::sourceFile($xmlFile)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeArray();
            } finally {
                unlink($xmlFile);
            }
        })->throws(InvalidArgumentException::class, 'Failed to parse XML');

        it('throws exception for invalid JSON file', function(): void {
            // Create a temporary file with invalid JSON
            $jsonFile = sys_get_temp_dir() . '/invalid.json';
            file_put_contents($jsonFile, '{invalid json}');

            $target = [];
            $mapping = ['number' => 'number'];

            try {
                $result = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeArray();
            } finally {
                unlink($jsonFile);
            }
        })->throws(InvalidArgumentException::class, 'Failed to parse JSON');

        it('supports skipNull parameter', function(): void {
            $jsonFile = sys_get_temp_dir() . '/test_null.json';
            $jsonData = [
                'name' => 'Test',
                'description' => null,
                'amount' => 100,
            ];
            file_put_contents($jsonFile, json_encode($jsonData));

            $target = ['description' => 'existing value'];

            $mapping = [
                'name' => '{{ name }}',
                'description' => '{{ description }}',
                'amount' => '{{ amount }}',
            ];

            // With skipNull = true (default)
            $result1 = DataMapper::sourceFile($jsonFile)->target($target)->template($mapping)->skipNull(
                true
            )->map()->getTarget();
            expect($result1['description'])->toBe('existing value'); // Not overwritten

            // With skipNull = false
            $target2 = ['description' => 'existing value'];
            $result2 = DataMapper::sourceFile($jsonFile)->target($target2)->template($mapping)->skipNull(
                false
            )->map()->getTarget();
            expect($result2['description'])->toBeNull(); // Overwritten with null

            // Cleanup
            unlink($jsonFile);
        });
    });
});
