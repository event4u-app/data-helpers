<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use event4u\DataHelpers\DataMapper;
use Tests\utils\Entities\Company;
use Tests\utils\Entities\Department;
use Tests\utils\Entities\Project;

describe('DataMapper::mapFromFile() to Doctrine Entity', function(): void {
    describe('Automatic relation mapping', function(): void {
        it('maps JSON file to Company entity with automatic relation mapping', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

            // Map everything in one go - DataMapper automatically detects and maps the relation!
            $company = new Company();
            $mapping = [
                'name' => '{{ company.name }}',
                'registration_number' => '{{ company.registration_number }}',
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
                'address' => '{{ company.address }}',
                'city' => '{{ company.city }}',
                'country' => '{{ company.country }}',
                'founded_year' => '{{ company.founded_year }}',
                'employee_count' => '{{ company.employee_count }}',
                'annual_revenue' => '{{ company.annual_revenue }}',
                'is_active' => '{{ company.is_active }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                        'budget' => '{{ company.departments.*.budget }}',
                        'employee_count' => '{{ company.departments.*.employee_count }}',
                        'manager_name' => '{{ company.departments.*.manager_name }}',
                    ],
                ],
                'projects' => [
                    '*' => [
                        'name' => '{{ company.projects.*.name }}',
                        'code' => '{{ company.projects.*.code }}',
                        'budget' => '{{ company.projects.*.budget }}',
                        'start_date' => '{{ company.projects.*.start_date }}',
                        'end_date' => '{{ company.projects.*.end_date }}',
                        'status' => '{{ company.projects.*.status }}',
                    ],
                ],
            ];

            $result = DataMapper::mapFromFile($jsonFile, $company, $mapping);

            // Verify Company data
            expect($result)->toBeInstanceOf(Company::class);
            expect($result->getName())->toBe('TechCorp Solutions');
            expect($result->getRegistrationNumber())->toBe('REG-2024-001');
            expect($result->getEmail())->toBe('info@techcorp.example');
            expect($result->getPhone())->toBe('+1-555-0123');
            expect($result->getFoundedYear())->toBe(2015);
            expect($result->getEmployeeCount())->toBe(250);
            expect($result->getAnnualRevenue())->toBe(15750000.50);
            expect($result->getIsActive())->toBe(true);

            // Verify departments relation (OneToMany)
            expect($result->getDepartments())->toBeInstanceOf(Collection::class);
            expect($result->getDepartments())->toHaveCount(3);

            $dept0 = $result->getDepartments()[0];
            expect($dept0)->toBeInstanceOf(Department::class);
            expect($dept0->getName())->toBe('Engineering');
            expect($dept0->getCode())->toBe('ENG');
            expect($dept0->getBudget())->toBe(5000000.00);
            expect($dept0->getEmployeeCount())->toBe(120);
            expect($dept0->getManagerName())->toBe('Alice Johnson');

            $dept1 = $result->getDepartments()[1];
            expect($dept1)->toBeInstanceOf(Department::class);
            expect($dept1->getName())->toBe('Sales');
            expect($dept1->getCode())->toBe('SAL');
            expect($dept1->getBudget())->toBe(3000000.00);

            $dept2 = $result->getDepartments()[2];
            expect($dept2)->toBeInstanceOf(Department::class);
            expect($dept2->getName())->toBe('Human Resources');
            expect($dept2->getCode())->toBe('HR');
            expect($dept2->getBudget())->toBe(1500000.00);

            // Verify projects relation (OneToMany)
            expect($result->getProjects())->toBeInstanceOf(Collection::class);
            expect($result->getProjects())->toHaveCount(2);

            $proj0 = $result->getProjects()[0];
            expect($proj0)->toBeInstanceOf(Project::class);
            expect($proj0->getName())->toBe('Cloud Migration');
            expect($proj0->getCode())->toBe('PROJ-001');
            expect($proj0->getBudget())->toBe(2500000.00);
            expect($proj0->getStartDate())->toBe('2024-01-01');
            expect($proj0->getEndDate())->toBe('2024-12-31');
            expect($proj0->getStatus())->toBe('active');

            $proj1 = $result->getProjects()[1];
            expect($proj1)->toBeInstanceOf(Project::class);
            expect($proj1->getName())->toBe('Mobile App Development');
            expect($proj1->getBudget())->toBe(1800000.00);

            // Additional assertions: Verify types
            expect($dept0->getBudget())->toBeFloat();
            expect($dept0->getEmployeeCount())->toBeInt();
            expect($result->getFoundedYear())->toBeInt();
            expect($result->getAnnualRevenue())->toBeFloat();
            expect($result->getIsActive())->toBeBool();
            expect($proj0->getBudget())->toBeFloat();
        });

        it('maps XML file to Company entity with automatic relation mapping', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map everything in one go - DataMapper automatically detects and maps the relation!
            $company = new Company();
            $mapping = [
                'name' => '{{ name }}',
                'registration_number' => '{{ registration_number }}',
                'email' => '{{ email }}',
                'phone' => '{{ phone }}',
                'address' => '{{ address }}',
                'city' => '{{ city }}',
                'country' => '{{ country }}',
                'founded_year' => '{{ founded_year }}',
                'employee_count' => '{{ employee_count }}',
                'annual_revenue' => '{{ annual_revenue }}',
                'is_active' => '{{ is_active }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                        'budget' => '{{ departments.department.*.budget }}',
                        'employee_count' => '{{ departments.department.*.employee_count }}',
                        'manager_name' => '{{ departments.department.*.manager_name }}',
                    ],
                ],
                'projects' => [
                    '*' => [
                        'name' => '{{ projects.project.*.name }}',
                        'code' => '{{ projects.project.*.code }}',
                        'budget' => '{{ projects.project.*.budget }}',
                        'start_date' => '{{ projects.project.*.start_date }}',
                        'end_date' => '{{ projects.project.*.end_date }}',
                        'status' => '{{ projects.project.*.status }}',
                    ],
                ],
            ];

            $result = DataMapper::mapFromFile($xmlFile, $company, $mapping);

            // Verify Company data (XML values are strings, but setters should convert them)
            expect($result)->toBeInstanceOf(Company::class);
            expect($result->getName())->toBe('TechCorp Solutions');
            expect($result->getRegistrationNumber())->toBe('REG-2024-001');
            expect($result->getEmail())->toBe('info@techcorp.example');
            expect($result->getPhone())->toBe('+1-555-0123');
            expect($result->getFoundedYear())->toBe(2015); // Cast to int
            expect($result->getEmployeeCount())->toBe(250); // Cast to int
            expect($result->getAnnualRevenue())->toBe(15750000.50); // Cast to float
            expect($result->getIsActive())->toBe(true); // Cast to bool

            // Verify departments relation (OneToMany)
            expect($result->getDepartments())->toBeInstanceOf(Collection::class);
            expect($result->getDepartments())->toHaveCount(3);

            $dept0 = $result->getDepartments()[0];
            expect($dept0)->toBeInstanceOf(Department::class);
            expect($dept0->getName())->toBe('Engineering');
            expect($dept0->getCode())->toBe('ENG');
            expect($dept0->getBudget())->toBe(5000000.00); // Cast to float
            expect($dept0->getEmployeeCount())->toBe(120); // Cast to int
            expect($dept0->getManagerName())->toBe('Alice Johnson');

            $dept1 = $result->getDepartments()[1];
            expect($dept1)->toBeInstanceOf(Department::class);
            expect($dept1->getName())->toBe('Sales');
            expect($dept1->getCode())->toBe('SAL');
            expect($dept1->getBudget())->toBe(3000000.00);

            $dept2 = $result->getDepartments()[2];
            expect($dept2)->toBeInstanceOf(Department::class);
            expect($dept2->getName())->toBe('Human Resources');
            expect($dept2->getCode())->toBe('HR');
            expect($dept2->getBudget())->toBe(1500000.00);

            // Verify projects relation (OneToMany)
            expect($result->getProjects())->toBeInstanceOf(Collection::class);
            expect($result->getProjects())->toHaveCount(2);

            $proj0 = $result->getProjects()[0];
            expect($proj0)->toBeInstanceOf(Project::class);
            expect($proj0->getName())->toBe('Cloud Migration');
            expect($proj0->getCode())->toBe('PROJ-001');
            expect($proj0->getBudget())->toBe(2500000.00);
            expect($proj0->getStartDate())->toBe('2024-01-01');
            expect($proj0->getEndDate())->toBe('2024-12-31');
            expect($proj0->getStatus())->toBe('active');

            $proj1 = $result->getProjects()[1];
            expect($proj1)->toBeInstanceOf(Project::class);
            expect($proj1->getName())->toBe('Mobile App Development');
            expect($proj1->getBudget())->toBe(1800000.00);

            // Additional assertions: Verify types
            expect($dept0->getBudget())->toBeFloat();
            expect($dept0->getEmployeeCount())->toBeInt();
            expect($result->getFoundedYear())->toBeInt();
            expect($result->getAnnualRevenue())->toBeFloat();
            expect($result->getIsActive())->toBeBool();
            expect($proj0->getBudget())->toBeFloat();
        });
    });

    describe('Entity comparison', function(): void {
        it('compares JSON and XML entities with identical values', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map JSON to Company entity
            $jsonCompany = new Company();
            $jsonMapping = [
                'name' => '{{ company.name }}',
                'registration_number' => '{{ company.registration_number }}',
                'email' => '{{ company.email }}',
                'phone' => '{{ company.phone }}',
                'founded_year' => '{{ company.founded_year }}',
                'employee_count' => '{{ company.employee_count }}',
                'annual_revenue' => '{{ company.annual_revenue }}',
                'is_active' => '{{ company.is_active }}',
            ];
            $jsonResult = DataMapper::mapFromFile($jsonFile, $jsonCompany, $jsonMapping);

            // Map XML to Company entity
            $xmlCompany = new Company();
            $xmlMapping = [
                'name' => '{{ name }}',
                'registration_number' => '{{ registration_number }}',
                'email' => '{{ email }}',
                'phone' => '{{ phone }}',
                'founded_year' => '{{ founded_year }}',
                'employee_count' => '{{ employee_count }}',
                'annual_revenue' => '{{ annual_revenue }}',
                'is_active' => '{{ is_active }}',
            ];
            $xmlResult = DataMapper::mapFromFile($xmlFile, $xmlCompany, $xmlMapping);

            // Compare all fields - should be identical
            expect($jsonResult->getName())->toBe($xmlResult->getName());
            expect($jsonResult->getRegistrationNumber())->toBe($xmlResult->getRegistrationNumber());
            expect($jsonResult->getEmail())->toBe($xmlResult->getEmail());
            expect($jsonResult->getPhone())->toBe($xmlResult->getPhone());

            // These should be identical types and values
            expect($jsonResult->getFoundedYear())->toBe($xmlResult->getFoundedYear());
            expect($jsonResult->getFoundedYear())->toBeInt();
            expect($xmlResult->getFoundedYear())->toBeInt();

            expect($jsonResult->getEmployeeCount())->toBe($xmlResult->getEmployeeCount());
            expect($jsonResult->getEmployeeCount())->toBeInt();
            expect($xmlResult->getEmployeeCount())->toBeInt();

            expect($jsonResult->getAnnualRevenue())->toBe($xmlResult->getAnnualRevenue());
            expect($jsonResult->getAnnualRevenue())->toBeFloat();
            expect($xmlResult->getAnnualRevenue())->toBeFloat();

            expect($jsonResult->getIsActive())->toBe($xmlResult->getIsActive());
            expect($jsonResult->getIsActive())->toBeBool();
            expect($xmlResult->getIsActive())->toBeBool();
        });

        it('maps departments to Department entities', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

            // Map JSON departments to Department entity
            $jsonDept = new Department();
            $jsonMapping = [
                'name' => '{{ company.departments.0.name }}',
                'code' => '{{ company.departments.0.code }}',
                'budget' => '{{ company.departments.0.budget }}',
                'employee_count' => '{{ company.departments.0.employee_count }}',
                'manager_name' => '{{ company.departments.0.manager_name }}',
            ];
            $jsonResult = DataMapper::mapFromFile($jsonFile, $jsonDept, $jsonMapping);

            // Map XML departments to Department entity
            $xmlDept = new Department();
            $xmlMapping = [
                'name' => '{{ departments.department.0.name }}',
                'code' => '{{ departments.department.0.code }}',
                'budget' => '{{ departments.department.0.budget }}',
                'employee_count' => '{{ departments.department.0.employee_count }}',
                'manager_name' => '{{ departments.department.0.manager_name }}',
            ];
            $xmlResult = DataMapper::mapFromFile($xmlFile, $xmlDept, $xmlMapping);

            // Compare - should be identical
            expect($jsonResult->getName())->toBe($xmlResult->getName());
            expect($jsonResult->getCode())->toBe($xmlResult->getCode());
            expect($jsonResult->getManagerName())->toBe($xmlResult->getManagerName());

            // These should be identical types
            expect($jsonResult->getBudget())->toBe($xmlResult->getBudget());
            expect($jsonResult->getBudget())->toBeFloat();
            expect($xmlResult->getBudget())->toBeFloat();
            expect($jsonResult->getBudget())->toBe(5000000.00);

            expect($jsonResult->getEmployeeCount())->toBe($xmlResult->getEmployeeCount());
            expect($jsonResult->getEmployeeCount())->toBeInt();
            expect($xmlResult->getEmployeeCount())->toBeInt();
            expect($jsonResult->getEmployeeCount())->toBe(120);
        });
    });
});

