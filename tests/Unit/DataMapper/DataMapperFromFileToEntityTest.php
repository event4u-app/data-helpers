<?php

declare(strict_types=1);

use Doctrine\Common\Collections\Collection;
use event4u\DataHelpers\DataMapper;
use Tests\Utils\Entities\Company;
use Tests\Utils\Entities\Department;
use Tests\Utils\Entities\Project;

describe('DataMapper to Doctrine Entity', function(): void {
    describe('Automatic relation mapping', function(): void {
        it('maps JSON file to Company entity with automatic relation mapping', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';

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

            $result = DataMapper::sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();

            // Verify Company data
            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->getName())->toBe('TechCorp Solutions');
            expect($company->getRegistrationNumber())->toBe('REG-2024-001');
            expect($company->getEmail())->toBe('info@techcorp.example');
            expect($company->getPhone())->toBe('+1-555-0123');
            expect($company->getFoundedYear())->toBe(2015);
            expect($company->getEmployeeCount())->toBe(250);
            expect($company->getAnnualRevenue())->toBe(15750000.50);
            expect($company->getIsActive())->toBe(true);

            // Verify departments relation (OneToMany)
            expect($company->getDepartments())->toBeInstanceOf(Collection::class);
            expect($company->getDepartments())->toHaveCount(3);

            $dept0 = $company->getDepartments()[0] ?? null;
            expect($dept0)->toBeInstanceOf(Department::class);
            /** @var Department $dept0Entity */
            $dept0Entity = $dept0;
            expect($dept0Entity->getName())->toBe('Engineering');
            expect($dept0Entity->getCode())->toBe('ENG');
            expect($dept0Entity->getBudget())->toBe(5000000.00);
            expect($dept0Entity->getEmployeeCount())->toBe(120);
            expect($dept0Entity->getManagerName())->toBe('Alice Johnson');

            $dept1 = $company->getDepartments()[1] ?? null;
            expect($dept1)->toBeInstanceOf(Department::class);
            /** @var Department $dept1Entity */
            $dept1Entity = $dept1;
            expect($dept1Entity->getName())->toBe('Sales');
            expect($dept1Entity->getCode())->toBe('SAL');
            expect($dept1Entity->getBudget())->toBe(3000000.00);

            $dept2 = $company->getDepartments()[2] ?? null;
            expect($dept2)->toBeInstanceOf(Department::class);
            /** @var Department $dept2Entity */
            $dept2Entity = $dept2;
            expect($dept2Entity->getName())->toBe('Human Resources');
            expect($dept2Entity->getCode())->toBe('HR');
            expect($dept2Entity->getBudget())->toBe(1500000.00);

            // Verify projects relation (OneToMany)
            expect($company->getProjects())->toBeInstanceOf(Collection::class);
            expect($company->getProjects())->toHaveCount(2);

            $proj0 = $company->getProjects()[0] ?? null;
            expect($proj0)->toBeInstanceOf(Project::class);
            /** @var Project $proj0Entity */
            $proj0Entity = $proj0;
            expect($proj0Entity->getName())->toBe('Cloud Migration');
            expect($proj0Entity->getCode())->toBe('PROJ-001');
            expect($proj0Entity->getBudget())->toBe(2500000.00);
            expect($proj0Entity->getStartDate())->toBe('2024-01-01');
            expect($proj0Entity->getEndDate())->toBe('2024-12-31');
            expect($proj0Entity->getStatus())->toBe('active');

            $proj1 = $company->getProjects()[1] ?? null;
            expect($proj1)->toBeInstanceOf(Project::class);
            /** @var Project $proj1Entity */
            $proj1Entity = $proj1;
            expect($proj1Entity->getName())->toBe('Mobile App Development');
            expect($proj1Entity->getBudget())->toBe(1800000.00);

            // Additional assertions: Verify types
            expect($dept0Entity->getBudget())->toBeFloat();
            expect($dept0Entity->getEmployeeCount())->toBeInt();
            expect($company->getFoundedYear())->toBeInt();
            expect($company->getAnnualRevenue())->toBeFloat();
            expect($company->getIsActive())->toBeBool();
            expect($proj0Entity->getBudget())->toBeFloat();
        });

        it('maps XML file to Company entity with automatic relation mapping', function(): void {
            $xmlFile = __DIR__ . '/../../Utils/xml/data_mapper_from_file_test.xml';

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

            $result = DataMapper::sourceFile($xmlFile)->target($company)->template($mapping)->map()->getTarget();

            // Verify Company data (XML values are strings, but setters should convert them)
            expect($result)->toBeInstanceOf(Company::class);
            /** @var Company $company */
            $company = $result;
            expect($company->getName())->toBe('TechCorp Solutions');
            expect($company->getRegistrationNumber())->toBe('REG-2024-001');
            expect($company->getEmail())->toBe('info@techcorp.example');
            expect($company->getPhone())->toBe('+1-555-0123');
            expect($company->getFoundedYear())->toBe(2015); // Cast to int
            expect($company->getEmployeeCount())->toBe(250); // Cast to int
            expect($company->getAnnualRevenue())->toBe(15750000.50); // Cast to float
            expect($company->getIsActive())->toBe(true); // Cast to bool

            // Verify departments relation (OneToMany)
            expect($company->getDepartments())->toBeInstanceOf(Collection::class);
            expect($company->getDepartments())->toHaveCount(3);

            $dept0 = $company->getDepartments()[0] ?? null;
            expect($dept0)->toBeInstanceOf(Department::class);
            /** @var Department $dept0Entity */
            $dept0Entity = $dept0;
            expect($dept0Entity->getName())->toBe('Engineering');
            expect($dept0Entity->getCode())->toBe('ENG');
            expect($dept0Entity->getBudget())->toBe(5000000.00); // Cast to float
            expect($dept0Entity->getEmployeeCount())->toBe(120); // Cast to int
            expect($dept0Entity->getManagerName())->toBe('Alice Johnson');

            $dept1 = $company->getDepartments()[1] ?? null;
            expect($dept1)->toBeInstanceOf(Department::class);
            /** @var Department $dept1Entity */
            $dept1Entity = $dept1;
            expect($dept1Entity->getName())->toBe('Sales');
            expect($dept1Entity->getCode())->toBe('SAL');
            expect($dept1Entity->getBudget())->toBe(3000000.00);

            $dept2 = $company->getDepartments()[2] ?? null;
            expect($dept2)->toBeInstanceOf(Department::class);
            /** @var Department $dept2Entity */
            $dept2Entity = $dept2;
            expect($dept2Entity->getName())->toBe('Human Resources');
            expect($dept2Entity->getCode())->toBe('HR');
            expect($dept2Entity->getBudget())->toBe(1500000.00);

            // Verify projects relation (OneToMany)
            expect($company->getProjects())->toBeInstanceOf(Collection::class);
            expect($company->getProjects())->toHaveCount(2);

            $proj0 = $company->getProjects()[0] ?? null;
            expect($proj0)->toBeInstanceOf(Project::class);
            /** @var Project $proj0Entity */
            $proj0Entity = $proj0;
            expect($proj0Entity->getName())->toBe('Cloud Migration');
            expect($proj0Entity->getCode())->toBe('PROJ-001');
            expect($proj0Entity->getBudget())->toBe(2500000.00);
            expect($proj0Entity->getStartDate())->toBe('2024-01-01');
            expect($proj0Entity->getEndDate())->toBe('2024-12-31');
            expect($proj0Entity->getStatus())->toBe('active');

            $proj1 = $company->getProjects()[1] ?? null;
            expect($proj1)->toBeInstanceOf(Project::class);
            /** @var Project $proj1Entity */
            $proj1Entity = $proj1;
            expect($proj1Entity->getName())->toBe('Mobile App Development');
            expect($proj1Entity->getBudget())->toBe(1800000.00);

            // Additional assertions: Verify types
            expect($dept0Entity->getBudget())->toBeFloat();
            expect($dept0Entity->getEmployeeCount())->toBeInt();
            expect($company->getFoundedYear())->toBeInt();
            expect($company->getAnnualRevenue())->toBeFloat();
            expect($company->getIsActive())->toBeBool();
            expect($proj0Entity->getBudget())->toBeFloat();
        });
    })->group('doctrine');

    describe('Entity comparison', function(): void {
        it('compares JSON and XML entities with identical values', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../Utils/xml/data_mapper_from_file_test.xml';

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
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonCompany)->template(
                $jsonMapping
            )->map()->getTarget();

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
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlCompany)->template(
                $xmlMapping
            )->map()->getTarget();

            /** @var Company $jsonCompany */
            $jsonCompany = $jsonResult;
            /** @var Company $xmlCompany */
            $xmlCompany = $xmlResult;

            // Compare all fields - should be identical
            expect($jsonCompany->getName())->toBe($xmlCompany->getName());
            expect($jsonCompany->getRegistrationNumber())->toBe($xmlCompany->getRegistrationNumber());
            expect($jsonCompany->getEmail())->toBe($xmlCompany->getEmail());
            expect($jsonCompany->getPhone())->toBe($xmlCompany->getPhone());

            // These should be identical types and values
            expect($jsonCompany->getFoundedYear())->toBe($xmlCompany->getFoundedYear());
            expect($jsonCompany->getFoundedYear())->toBeInt();
            expect($xmlCompany->getFoundedYear())->toBeInt();

            expect($jsonCompany->getEmployeeCount())->toBe($xmlCompany->getEmployeeCount());
            expect($jsonCompany->getEmployeeCount())->toBeInt();
            expect($xmlCompany->getEmployeeCount())->toBeInt();

            expect($jsonCompany->getAnnualRevenue())->toBe($xmlCompany->getAnnualRevenue());
            expect($jsonCompany->getAnnualRevenue())->toBeFloat();
            expect($xmlCompany->getAnnualRevenue())->toBeFloat();

            expect($jsonCompany->getIsActive())->toBe($xmlCompany->getIsActive());
            expect($jsonCompany->getIsActive())->toBeBool();
            expect($xmlCompany->getIsActive())->toBeBool();
        });

        it('maps departments to Department entities', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../Utils/xml/data_mapper_from_file_test.xml';

            // Map JSON departments to Department entity
            $jsonDept = new Department();
            $jsonMapping = [
                'name' => '{{ company.departments.0.name }}',
                'code' => '{{ company.departments.0.code }}',
                'budget' => '{{ company.departments.0.budget }}',
                'employee_count' => '{{ company.departments.0.employee_count }}',
                'manager_name' => '{{ company.departments.0.manager_name }}',
            ];
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonDept)->template(
                $jsonMapping
            )->map()->getTarget();

            // Map XML departments to Department entity
            $xmlDept = new Department();
            $xmlMapping = [
                'name' => '{{ departments.department.0.name }}',
                'code' => '{{ departments.department.0.code }}',
                'budget' => '{{ departments.department.0.budget }}',
                'employee_count' => '{{ departments.department.0.employee_count }}',
                'manager_name' => '{{ departments.department.0.manager_name }}',
            ];
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlDept)->template($xmlMapping)->map()->getTarget();

            /** @var Department $jsonDept */
            $jsonDept = $jsonResult;
            /** @var Department $xmlDept */
            $xmlDept = $xmlResult;

            // Compare - should be identical
            expect($jsonDept->getName())->toBe($xmlDept->getName());
            expect($jsonDept->getCode())->toBe($xmlDept->getCode());
            expect($jsonDept->getManagerName())->toBe($xmlDept->getManagerName());

            // These should be identical types
            expect($jsonDept->getBudget())->toBe($xmlDept->getBudget());
            expect($jsonDept->getBudget())->toBeFloat();
            expect($xmlDept->getBudget())->toBeFloat();
            expect($jsonDept->getBudget())->toBe(5000000.00);

            expect($jsonDept->getEmployeeCount())->toBe($xmlDept->getEmployeeCount());
            expect($jsonDept->getEmployeeCount())->toBeInt();
            expect($xmlDept->getEmployeeCount())->toBeInt();
            expect($jsonDept->getEmployeeCount())->toBe(120);
        });
    })->group('doctrine');
})->group('doctrine');
