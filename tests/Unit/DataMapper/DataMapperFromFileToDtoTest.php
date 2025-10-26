<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Tests\Utils\DTOs\CompanyDto;
use Tests\Utils\DTOs\DepartmentDto;
use Tests\Utils\DTOs\ProjectDto;

describe('DataMapper to DTO', function(): void {
    describe('Automatic nested DTO mapping', function(): void {
        it('maps JSON file to Company DTO with nested DTOs', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';

            // Map everything in one go - DataMapper automatically creates nested DTOs!
            $company = new CompanyDto();
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

            // Verify Company DTO data
            expect($result)->toBeInstanceOf(CompanyDto::class);
            /** @var CompanyDto $companyDto */
            $companyDto = $result;
            expect($companyDto->name)->toBe('TechCorp Solutions');
            expect($companyDto->registration_number)->toBe('REG-2024-001');
            expect($companyDto->email)->toBe('info@techcorp.example');
            expect($companyDto->phone)->toBe('+1-555-0123');
            expect($companyDto->founded_year)->toBe(2015);
            expect($companyDto->employee_count)->toBe(250);
            expect($companyDto->annual_revenue)->toBe(15750000.50);
            expect($companyDto->is_active)->toBe(true);

            // Verify departments array (DTOs with typed properties automatically create nested DTOs!)
            expect($companyDto->departments)->toBeArray();
            expect($companyDto->departments)->toHaveCount(3);

            $dept0 = $companyDto->departments[0] ?? null;
            expect($dept0)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $dept0Dto */
            $dept0Dto = $dept0;
            expect($dept0Dto->name)->toBe('Engineering');
            expect($dept0Dto->code)->toBe('ENG');
            expect($dept0Dto->budget)->toBe(5000000.00);
            expect($dept0Dto->employee_count)->toBe(120);
            expect($dept0Dto->manager_name)->toBe('Alice Johnson');

            $dept1 = $companyDto->departments[1] ?? null;
            expect($dept1)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $dept1Dto */
            $dept1Dto = $dept1;
            expect($dept1Dto->name)->toBe('Sales');
            expect($dept1Dto->code)->toBe('SAL');
            expect($dept1Dto->budget)->toBe(3000000.00);

            $dept2 = $companyDto->departments[2] ?? null;
            expect($dept2)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $dept2Dto */
            $dept2Dto = $dept2;
            expect($dept2Dto->name)->toBe('Human Resources');
            expect($dept2Dto->code)->toBe('HR');
            expect($dept2Dto->budget)->toBe(1500000.00);

            // Verify projects array
            expect($companyDto->projects)->toBeArray();
            expect($companyDto->projects)->toHaveCount(2);

            $proj0 = $companyDto->projects[0] ?? null;
            expect($proj0)->toBeInstanceOf(ProjectDto::class);
            /** @var ProjectDto $proj0Dto */
            $proj0Dto = $proj0;
            expect($proj0Dto->name)->toBe('Cloud Migration');
            expect($proj0Dto->code)->toBe('PROJ-001');
            expect($proj0Dto->budget)->toBe(2500000.00);
            expect($proj0Dto->start_date)->toBe('2024-01-01');
            expect($proj0Dto->end_date)->toBe('2024-12-31');
            expect($proj0Dto->status)->toBe('active');

            $proj1 = $companyDto->projects[1] ?? null;
            expect($proj1)->toBeInstanceOf(ProjectDto::class);
            /** @var ProjectDto $proj1Dto */
            $proj1Dto = $proj1;
            expect($proj1Dto->name)->toBe('Mobile App Development');
            expect($proj1Dto->code)->toBe('PROJ-002');
            expect($proj1Dto->budget)->toBe(1800000.00);
        });

        it('maps XML file to Company DTO with nested DTOs', function(): void {
            $xmlFile = __DIR__ . '/../../Utils/xml/data_mapper_from_file_test.xml';

            $company = new CompanyDto();
            $mapping = [
                'name' => '{{ name }}',
                'registration_number' => '{{ registration_number }}',
                'email' => '{{ email }}',
                'phone' => '{{ phone }}',
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

            // Verify Company DTO data
            expect($result)->toBeInstanceOf(CompanyDto::class);
            /** @var CompanyDto $companyDto */
            $companyDto = $result;
            expect($companyDto->name)->toBe('TechCorp Solutions');
            expect($companyDto->registration_number)->toBe('REG-2024-001');
            expect($companyDto->email)->toBe('info@techcorp.example');
            expect($companyDto->phone)->toBe('+1-555-0123');
            expect($companyDto->founded_year)->toBe(2015);
            expect($companyDto->employee_count)->toBe(250);
            expect($companyDto->annual_revenue)->toBe(15750000.50);
            expect($companyDto->is_active)->toBe(true);

            // Verify departments (automatically created as DepartmentDto instances)
            expect($companyDto->departments)->toBeArray();
            expect($companyDto->departments)->toHaveCount(3);

            $dept0 = $companyDto->departments[0] ?? null;
            expect($dept0)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $dept0Dto */
            $dept0Dto = $dept0;
            expect($dept0Dto->name)->toBe('Engineering');
            expect($dept0Dto->code)->toBe('ENG');
            expect($dept0Dto->budget)->toBe(5000000.00);

            // Verify projects (automatically created as ProjectDto instances)
            expect($companyDto->projects)->toBeArray();
            expect($companyDto->projects)->toHaveCount(2);

            $proj0 = $companyDto->projects[0] ?? null;
            expect($proj0)->toBeInstanceOf(ProjectDto::class);
            /** @var ProjectDto $proj0Dto */
            $proj0Dto = $proj0;
            expect($proj0Dto->name)->toBe('Cloud Migration');
            expect($proj0Dto->code)->toBe('PROJ-001');
        });
    });

    describe('DTO comparison', function(): void {
        it('maps JSON vs XML to DTOs with identical results', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../Utils/xml/data_mapper_from_file_test.xml';

            // JSON mapping
            $jsonCompany = new CompanyDto();
            $jsonMapping = [
                'name' => '{{ company.name }}',
                'email' => '{{ company.email }}',
                'founded_year' => '{{ company.founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ company.departments.*.name }}',
                        'code' => '{{ company.departments.*.code }}',
                    ],
                ],
            ];
            $jsonResult = DataMapper::sourceFile($jsonFile)->target($jsonCompany)->template(
                $jsonMapping
            )->map()->getTarget();

            // XML mapping
            $xmlCompany = new CompanyDto();
            $xmlMapping = [
                'name' => '{{ name }}',
                'email' => '{{ email }}',
                'founded_year' => '{{ founded_year }}',
                'departments' => [
                    '*' => [
                        'name' => '{{ departments.department.*.name }}',
                        'code' => '{{ departments.department.*.code }}',
                    ],
                ],
            ];
            $xmlResult = DataMapper::sourceFile($xmlFile)->target($xmlCompany)->template(
                $xmlMapping
            )->map()->getTarget();

            /** @var CompanyDto $jsonCompanyDto */
            $jsonCompanyDto = $jsonResult;
            /** @var CompanyDto $xmlCompanyDto */
            $xmlCompanyDto = $xmlResult;

            // Compare results
            expect($jsonCompanyDto->name)->toBe($xmlCompanyDto->name);
            expect($jsonCompanyDto->email)->toBe($xmlCompanyDto->email);
            expect($jsonCompanyDto->founded_year)->toBe($xmlCompanyDto->founded_year);
            expect(count($jsonCompanyDto->departments))->toBe(count($xmlCompanyDto->departments));

            $jsonDept0 = $jsonCompanyDto->departments[0] ?? null;
            expect($jsonDept0)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $jsonDept0Dto */
            $jsonDept0Dto = $jsonDept0;

            $xmlDept0 = $xmlCompanyDto->departments[0] ?? null;
            expect($xmlDept0)->toBeInstanceOf(DepartmentDto::class);
            /** @var DepartmentDto $xmlDept0Dto */
            $xmlDept0Dto = $xmlDept0;

            expect($jsonDept0Dto->name)->toBe($xmlDept0Dto->name);
            expect($jsonDept0Dto->code)->toBe($xmlDept0Dto->code);
        });

        it('maps departments to Department DTOs', function(): void {
            $jsonFile = __DIR__ . '/../../Utils/json/data_mapper_from_file_test.json';

            $departments = [];
            $mapping = [
                '*' => [
                    'name' => '{{ company.departments.*.name }}',
                    'code' => '{{ company.departments.*.code }}',
                    'budget' => '{{ company.departments.*.budget }}',
                    'employee_count' => '{{ company.departments.*.employee_count }}',
                    'manager_name' => '{{ company.departments.*.manager_name }}',
                ],
            ];

            $result = DataMapper::sourceFile($jsonFile)->target($departments)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
            expect($result)->toHaveCount(3);
            expect($result[0]['name'])->toBe('Engineering');
            expect($result[0]['code'])->toBe('ENG');
            expect($result[0]['budget'])->toBe(5000000.00);
            expect($result[1]['name'])->toBe('Sales');
            expect($result[2]['name'])->toBe('Human Resources');
        });
    });
});
