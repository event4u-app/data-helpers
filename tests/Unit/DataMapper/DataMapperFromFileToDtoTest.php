<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use Tests\utils\DTOs\CompanyDto;
use Tests\utils\DTOs\DepartmentDto;
use Tests\utils\DTOs\ProjectDto;

describe('DataMapper::mapFromFile() to DTO', function(): void {
    describe('Automatic nested DTO mapping', function(): void {
        it('maps JSON file to Company DTO with nested DTOs', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

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

            $result = DataMapper::mapFromFile($jsonFile, $company, $mapping);

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
            expect($result->projects)->toBeArray();
            expect($result->projects)->toHaveCount(2);

            $proj0 = $result->projects[0];
            expect($proj0)->toBeInstanceOf(ProjectDto::class);
            expect($proj0->name)->toBe('Cloud Migration');
            expect($proj0->code)->toBe('PROJ-001');
            expect($proj0->budget)->toBe(2500000.00);
            expect($proj0->start_date)->toBe('2024-01-01');
            expect($proj0->end_date)->toBe('2024-12-31');
            expect($proj0->status)->toBe('active');

            $proj1 = $result->projects[1];
            expect($proj1)->toBeInstanceOf(ProjectDto::class);
            expect($proj1->name)->toBe('Mobile App Development');
            expect($proj1->code)->toBe('PROJ-002');
            expect($proj1->budget)->toBe(1800000.00);
        });

        it('maps XML file to Company DTO with nested DTOs', function(): void {
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

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

            $result = DataMapper::mapFromFile($xmlFile, $company, $mapping);

            // Verify Company DTO data
            expect($result)->toBeInstanceOf(CompanyDto::class);
            expect($result->name)->toBe('TechCorp Solutions');
            expect($result->registration_number)->toBe('REG-2024-001');
            expect($result->email)->toBe('info@techcorp.example');
            expect($result->phone)->toBe('+1-555-0123');
            expect($result->founded_year)->toBe(2015);
            expect($result->employee_count)->toBe(250);
            expect($result->annual_revenue)->toBe(15750000.50);
            expect($result->is_active)->toBe(true);

            // Verify departments (automatically created as DepartmentDto instances)
            expect($result->departments)->toBeArray();
            expect($result->departments)->toHaveCount(3);

            $dept0 = $result->departments[0];
            expect($dept0)->toBeInstanceOf(DepartmentDto::class);
            expect($dept0->name)->toBe('Engineering');
            expect($dept0->code)->toBe('ENG');
            expect($dept0->budget)->toBe(5000000.00);

            // Verify projects (automatically created as ProjectDto instances)
            expect($result->projects)->toBeArray();
            expect($result->projects)->toHaveCount(2);

            $proj0 = $result->projects[0];
            expect($proj0)->toBeInstanceOf(ProjectDto::class);
            expect($proj0->name)->toBe('Cloud Migration');
            expect($proj0->code)->toBe('PROJ-001');
        });
    });

    describe('DTO comparison', function(): void {
        it('maps JSON vs XML to DTOs with identical results', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';
            $xmlFile = __DIR__ . '/../../utils/xml/data_mapper_from_file_test.xml';

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
            $jsonResult = DataMapper::mapFromFile($jsonFile, $jsonCompany, $jsonMapping);

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
            $xmlResult = DataMapper::mapFromFile($xmlFile, $xmlCompany, $xmlMapping);

            // Compare results
            expect($jsonResult->name)->toBe($xmlResult->name);
            expect($jsonResult->email)->toBe($xmlResult->email);
            expect($jsonResult->founded_year)->toBe($xmlResult->founded_year);
            expect(count($jsonResult->departments))->toBe(count($xmlResult->departments));
            expect($jsonResult->departments[0])->toBeInstanceOf(DepartmentDto::class);
            expect($xmlResult->departments[0])->toBeInstanceOf(DepartmentDto::class);
            expect($jsonResult->departments[0]->name)->toBe($xmlResult->departments[0]->name);
            expect($jsonResult->departments[0]->code)->toBe($xmlResult->departments[0]->code);
        });

        it('maps departments to Department DTOs', function(): void {
            $jsonFile = __DIR__ . '/../../utils/json/data_mapper_from_file_test.json';

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

            $result = DataMapper::mapFromFile($jsonFile, $departments, $mapping);

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

